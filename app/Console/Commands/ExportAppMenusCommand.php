<?php

namespace App\Console\Commands;

use App\Services\Application\Menu\AppMenusSyncService;
use Illuminate\Console\Command;

class ExportAppMenusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app-menus:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the menus of the menu builder (app_menu, app_menu_item) into an auto-installer, so the next deploy syncs them to the server';

    public function handle(): int
    {
        $export = AppMenusSyncService::exportToFile();

        $this->info('Exported ' . $export['menus'] . ' menus with ' . $export['items'] . ' items.');
        $this->line('Path: ' . AppMenusSyncService::filePath());
        $this->line('Review the git diff of the file, then commit it. The next deploy installs it (auto-install:check).');

        return self::SUCCESS;
    }
}
