<?php

namespace App\Console\Commands;

use App\Services\Application\AppTranslationsSyncService;
use Illuminate\Console\Command;

class ImportTranslationsCommand extends Command
{
    protected $signature = 'translations:import {--lang=hr : Language of the file database/translations/{lang}.json}';

    protected $description = 'Import the translations of database/translations/{lang}.json into app_translations (filled in entries only)';

    public function handle(): int
    {
        $lang = strtolower(trim((string) $this->option('lang')));

        try {
            $stats = AppTranslationsSyncService::importWorkFile($lang);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return self::FAILURE;
        }

        $this->info("Imported ($lang): {$stats['created']} created, {$stats['updated']} updated, {$stats['unchanged']} unchanged, {$stats['skipped_empty']} empty skipped.");
        $this->line('Run translations:export to put them into the sync file for the other environments.');

        return self::SUCCESS;
    }
}
