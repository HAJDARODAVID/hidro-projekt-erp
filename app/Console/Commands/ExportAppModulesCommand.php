<?php

namespace App\Console\Commands;

use App\Services\Application\AppModulesSyncService;
use Illuminate\Console\Command;

class ExportAppModulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app-modules:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export app_modules and app_module_routes into an auto-installer, so the next deploy syncs them to the server';

    public function handle(): int
    {
        $export = AppModulesSyncService::exportToFile();

        foreach ($export['warnings'] as $warning) {
            $this->warn($warning);
        }

        $this->info('Exported ' . $export['modules'] . ' modules and ' . $export['routes'] . ' routes.');
        $this->line('Path: ' . AppModulesSyncService::filePath());
        $this->line('Review the git diff of the file, then commit it. The next deploy installs it (auto-install:check).');

        return self::SUCCESS;
    }
}
