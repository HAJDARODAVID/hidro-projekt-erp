<?php

namespace App\Console\Commands;

use App\Services\Application\AppTranslationsSyncService;
use Illuminate\Console\Command;

class ExportTranslationsCommand extends Command
{
    protected $signature = 'translations:export';

    protected $description = 'Export app_translations into an auto-installer, so the next deploy syncs them to the server';

    public function handle(): int
    {
        $export = AppTranslationsSyncService::exportToFile();

        $this->info('Exported ' . $export['translations'] . ' translations in ' . $export['languages'] . ' language(s).');
        $this->line('Path: ' . AppTranslationsSyncService::filePath());
        $this->line('Review the git diff of the file, then commit it. The next deploy installs it (auto-install:check).');

        return self::SUCCESS;
    }
}
