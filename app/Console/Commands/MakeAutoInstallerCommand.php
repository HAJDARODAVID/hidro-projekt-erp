<?php

namespace App\Console\Commands;

use App\Services\AutoInstallationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeAutoInstallerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-install:make
                            {name : Installer name (example: setup-payment-gateway)}
                            {--type=php : Installer type (php or json)}
                            {--force : Overwrite file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new auto-installer file from stubs';

    public function __construct(protected AutoInstallationService $autoInstallationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $type = strtolower((string) $this->option('type'));
        $name = (string) $this->argument('name');

        if (!in_array($type, ['php', 'json'], true)) {
            $this->error('Invalid --type. Allowed values: php, json');

            return self::FAILURE;
        }

        $targetDirectory = $this->autoInstallationService->getInstallationPath();
        File::ensureDirectoryExists($targetDirectory);

        $fileName = $this->buildFileName($name, $type);
        $filePath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (File::exists($filePath) && !$this->option('force')) {
            $this->error('Installer already exists: ' . $fileName);
            $this->line('Use --force to overwrite the file.');

            return self::FAILURE;
        }

        $stubPath = base_path('stubs/auto-installation.' . $type . '.stub');
        if (!File::exists($stubPath)) {
            $this->error('Stub not found: ' . $stubPath);

            return self::FAILURE;
        }

        $content = $this->buildContent($stubPath, $name, $type);
        File::put($filePath, $content);

        $this->info('Auto-installer created successfully.');
        $this->line('Path: ' . $filePath);

        return self::SUCCESS;
    }

    protected function buildFileName(string $name, string $type): string
    {
        $timestamp = now()->format('Y_m_d_His');
        $normalizedName = Str::snake(str_replace(['-', ' '], '_', $name));

        return $timestamp . '_' . trim($normalizedName, '_') . '.' . $type;
    }

    protected function buildContent(string $stubPath, string $name, string $type): string
    {
        $stub = File::get($stubPath);
        $slug = Str::snake(str_replace(['-', ' '], '_', $name));

        if ($type === 'php') {
            return str_replace(
                ['{{ slug }}', '{{ title }}'],
                [$slug, Str::headline($slug)],
                $stub
            );
        }

        return str_replace('{{ slug }}', $slug, $stub);
    }
}
