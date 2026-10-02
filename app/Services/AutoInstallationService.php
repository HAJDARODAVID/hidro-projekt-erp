<?php

namespace App\Services;

use App\Models\AutoInstallation;
use App\Services\Application\AppModulesSyncService;
use App\Services\Config\BaseConfigService;
use App\Services\Config\AppConfigDto;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AutoInstallationService extends BaseConfigService
{
    /**JSON types meant to be updated over time: run again when their data changes */
    const RERUN_ON_CHANGE_TYPES = [
        AppModulesSyncService::INSTALLATION_TYPE,
    ];

    /**
     * Path where installation files are stored
     */
    protected string $installationPath;

    public function __construct()
    {
        $this->installationPath = base_path('installers/auto-installations');
    }

    /**
     * Check and run all pending installations
     */
    public function runPendingInstallations(): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        if (!File::exists($this->installationPath)) {
            Log::info('Auto-installation path does not exist: ' . $this->installationPath);
            return $results;
        }

        $files = collect(File::files($this->installationPath))
            ->sortBy(fn($file) => $file->getFilename())
            ->values()
            ->all();

        foreach ($files as $file) {
            $fileName = $file->getFilename();

            // Skip non-PHP and non-JSON files
            if (!in_array($file->getExtension(), ['php', 'json'])) {
                continue;
            }

            try {
                if ($this->isUpToDate($file, $fileName)) {
                    $results['skipped'][] = $fileName;
                    continue;
                }

                $this->executeInstallation($file, $fileName, $results);
            } catch (\Throwable $e) {
                $this->recordFailedInstallation($fileName, $e->getMessage());
                $results['failed'][] = [
                    'file' => $fileName,
                    'error' => $e->getMessage(),
                ];
                Log::error('Auto-installation failed for ' . $fileName . ': ' . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Run one installation file now, even if it was already installed, and record the run.
     *
     * @param string $fileName file in the installation path
     * @return array the summary returned by the installation handler (empty if it returns none)
     * @throws \Throwable
     */
    public function installFile(string $fileName): array
    {
        $path = $this->installationPath . DIRECTORY_SEPARATOR . $fileName;
        if (!File::exists($path)) throw new \Exception("Installation file not found: $fileName");

        $results = ['success' => [], 'failed' => [], 'skipped' => [], 'summaries' => []];
        try {
            $this->executeInstallation(new \SplFileInfo($path), $fileName, $results);
        } catch (\Throwable $e) {
            $this->recordFailedInstallation($fileName, $e->getMessage());
            Log::error('Auto-installation failed for ' . $fileName . ': ' . $e->getMessage());
            throw $e;
        }
        return $results['summaries'][$fileName] ?? [];
    }

    /**
     * Whether the file was already installed successfully (a failed installation is run again).
     * Types in RERUN_ON_CHANGE_TYPES are also run again when their data changed since the last run.
     */
    protected function isUpToDate($file, string $fileName): bool
    {
        $installed = AutoInstallation::where('file_name', $fileName)->where('success', true)->first();
        if (!$installed) return false;
        if ($file->getExtension() !== 'json') return true;

        $content = $this->readJSON($file);
        if (!in_array($content['type'], self::RERUN_ON_CHANGE_TYPES, true)) return true;

        return $installed->checksum === $this->checksum($content['data']);
    }

    /**
     * Read and check a JSON installation file
     */
    protected function readJSON($file): array
    {
        $content = json_decode(File::get($file->getRealPath()), true);

        if (!is_array($content) || !isset($content['type']) || !isset($content['data'])) {
            throw new \Exception('JSON installation file must contain "type" and "data" keys');
        }
        return $content;
    }

    /**
     * Checksum of the installation data, independent of the file formatting / line endings
     */
    protected function checksum($data): string
    {
        return hash('sha256', json_encode($data));
    }

    /**
     * Execute a single installation file
     */
    protected function executeInstallation($file, string $fileName, array &$results): void
    {
        $extension = $file->getExtension();

        if ($extension === 'php') {
            $this->executePHPInstallation($file, $fileName, $results);
        } elseif ($extension === 'json') {
            $this->executeJSONInstallation($file, $fileName, $results);
        }
    }

    /**
     * Execute PHP-based installation
     */
    protected function executePHPInstallation($file, string $fileName, array &$results): void
    {
        // Include the PHP file and expect it to return an installation class
        $className = require $file->getRealPath();

        if (is_object($className) && method_exists($className, 'handle')) {
            $installationType = $className->getType() ?? 'unknown';
            $data = $className->getData() ?? null;

            // Run the installation
            $className->handle();

            // Record successful installation
            AutoInstallation::updateOrCreate(['file_name' => $fileName], [
                'installation_type' => $installationType,
                'data' => $data ? json_encode($data) : null,
                'success' => true,
                'error' => null,
                'installed_at' => now(),
            ]);

            $results['success'][] = $fileName;
            Log::info('Auto-installation completed: ' . $fileName);
        } else {
            throw new \Exception('Installation file must return an object with a handle() method');
        }
    }

    /**
     * Execute JSON-based installation (for simple data seeding)
     */
    protected function executeJSONInstallation($file, string $fileName, array &$results): void
    {
        $content = $this->readJSON($file);

        $installationType = $content['type'];
        $data = $content['data'];

        // Route to appropriate handler based on type
        $summary = match ($installationType) {
            'app_config' => $this->handleAppConfig($data),
            'seed_data' => $this->handleSeedData($data),
            AppModulesSyncService::INSTALLATION_TYPE => AppModulesSyncService::import($data),
            default => throw new \Exception("Unknown installation type: $installationType"),
        };

        // Record successful installation (a re-runnable file stores only its summary, its data can be large)
        AutoInstallation::updateOrCreate(['file_name' => $fileName], [
            'installation_type' => $installationType,
            'data' => json_encode(in_array($installationType, self::RERUN_ON_CHANGE_TYPES, true) ? $summary : $data),
            'checksum' => $this->checksum($data),
            'success' => true,
            'error' => null,
            'installed_at' => now(),
        ]);

        $results['success'][] = $fileName;
        if (is_array($summary)) $results['summaries'][$fileName] = $summary;
        Log::info('Auto-installation completed: ' . $fileName . ($summary ? ' ' . json_encode($summary) : ''));
    }

    /**
     * Handle app_config type installations
     */
    protected function handleAppConfig(array $data): void
    {
        // Example implementation - you can customize this based on your needs
        foreach ($data as $key => $value) {
            // Store configuration in cache or database
            $configDto = (new AppConfigDto())
                ->setKey($key)
                ->setValue($value);

            $this->createConfig($configDto);
            Log::info("App config set: $key");
        }
    }

    /**
     * Handle seed_data type installations
     */
    protected function handleSeedData(array $data): void
    {
        // Example implementation - you can customize this based on your needs
        if (!isset($data['model']) || !isset($data['records'])) {
            throw new \Exception('seed_data must contain "model" and "records" keys');
        }

        $modelClass = 'App\\Models\\' . $data['model'];
        if (!class_exists($modelClass)) {
            throw new \Exception("Model not found: $modelClass");
        }

        foreach ($data['records'] as $record) {
            $modelClass::firstOrCreate($record);
            Log::info("Seeded record in {$data['model']}: " . json_encode($record));
        }
    }

    /**
     * Record failed installation
     */
    protected function recordFailedInstallation(string $fileName, string $error): void
    {
        // One row per file (file_name is unique), the next run tries the file again
        $installation = AutoInstallation::firstOrNew(['file_name' => $fileName]);
        $installation->installation_type ??= 'unknown';
        $installation->success = false;
        $installation->error = $error;
        $installation->save();
    }

    /**
     * Get installation history
     */
    public function getInstallationHistory(int $limit = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        return AutoInstallation::orderBy('created_at', 'desc')->paginate($limit);
    }

    /**
     * Get successful installations
     */
    public function getSuccessfulInstallations(): \Illuminate\Database\Eloquent\Builder
    {
        return AutoInstallation::where('success', true);
    }

    /**
     * Get failed installations
     */
    public function getFailedInstallations(): \Illuminate\Database\Eloquent\Builder
    {
        return AutoInstallation::where('success', false);
    }

    /**
     * Check if a file has been installed
     */
    public function isInstalled(string $fileName): bool
    {
        return AutoInstallation::where('file_name', $fileName)->where('success', true)->exists();
    }

    /**
     * Get installation installation path (for manual testing)
     */
    public function getInstallationPath(): string
    {
        return $this->installationPath;
    }
}
