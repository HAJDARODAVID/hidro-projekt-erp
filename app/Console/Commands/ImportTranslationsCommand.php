<?php

namespace App\Console\Commands;

use App\Services\Application\AppTranslationsService;
use Illuminate\Console\Command;

/**
 * Manual bulk translating: import a JSON file { "value": "translation" } written by translations:export.
 * Filled in entries create / overwrite the translation, "" is skipped, nothing is deleted.
 */
class ImportTranslationsCommand extends Command
{
    protected $signature = 'translations:import
                            {--lang= : Language (default: the first of TRANSLATION_LANGUAGES)}
                            {--path= : Input file (default: database/translations/{lang}.json)}
                            {--only-empty : Only fill strings that have no translation yet}';

    protected $description = 'Import the translations of a JSON file into app_translations (filled in entries only)';

    public function handle(): int
    {
        $lang = strtolower(trim((string) ($this->option('lang') ?: AppTranslationsService::defaultLanguage())));

        try {
            $stats = AppTranslationsService::importFile($lang, $this->option('path') ?: NULL, (bool) $this->option('only-empty'));
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return self::FAILURE;
        }

        $this->info("Imported ($lang): {$stats['created']} created, {$stats['updated']} updated, {$stats['unchanged']} unchanged, {$stats['skipped_empty']} empty skipped"
            . ($stats['kept'] ? ", {$stats['kept']} already translated kept" : '') . '.');

        return self::SUCCESS;
    }
}
