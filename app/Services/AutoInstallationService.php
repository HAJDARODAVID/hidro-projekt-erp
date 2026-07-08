<?php

namespace App\Services;

use App\Models\AutoInstallation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AutoInstallationService extends BaseService
{
    /**
     * Path where installation files are stored
     */
    protected string $installationPath;

    public function __construct()
    {
        $this->installationPath = storage_path('app/auto-installations');
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

        $files = File::files($this->installationPath);

        foreach ($files as $file) {
            $fileName = $file->getFilename();
            
            // Skip non-PHP and non-JSON files
            if (!in_array($file->getExtension(), ['php', 'json'])) {
                continue;
            }

            // Check if already installed
            $installed = AutoInstallation::where('file_name', $fileName)->first();
            if ($installed) {
                $results['skipped'][] = $fileName;
                continue;
            }

            try {
                $this->executeInstallation($file, $fileName, $results);
            } catch (\Exception $e) {
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
            AutoInstallation::create([
                'file_name' => $fileName,
                'installation_type' => $installationType,
                'data' => $data ? json_encode($data) : null,
                'success' => true,
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
        $content = json_decode(File::get($file->getRealPath()), true);

        if (!isset($content['type']) || !isset($content['data'])) {
            throw new \Exception('JSON installation file must contain "type" and "data" keys');
        }

        $installationType = $content['type'];
        $data = $content['data'];

        // Route to appropriate handler based on type
        match ($installationType) {
            'app_config' => $this->handleAppConfig($data),
            'seed_data' => $this->handleSeedData($data),
            default => throw new \Exception("Unknown installation type: $installationType"),
        };

        // Record successful installation
        AutoInstallation::create([
            'file_name' => $fileName,
            'installation_type' => $installationType,
            'data' => json_encode($data),
            'success' => true,
            'installed_at' => now(),
        ]);

        $results['success'][] = $fileName;
        Log::info('Auto-installation completed: ' . $fileName);
    }

    /**
     * Handle app_config type installations
     */
    protected function handleAppConfig(array $data): void
    {
        // Example implementation - you can customize this based on your needs
        foreach ($data as $key => $value) {
            // Store configuration in cache or database
            \Cache::forever('app_config_' . $key, $value);
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
        AutoInstallation::create([
            'file_name' => $fileName,
            'success' => false,
            'error' => $error,
        ]);
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
