<?php

namespace App\Console\Commands;

use App\Services\Application\AppTranslationsService;
use Illuminate\Console\Command;

/**
 * Manual bulk translating: write all strings of a language into a JSON file { "value": "translation" },
 * fill in the "" ones, then translations:import. Nothing runs this on deploy.
 */
class ExportTranslationsCommand extends Command
{
    protected $signature = 'translations:export
                            {--lang= : Language (default: the first of TRANSLATION_LANGUAGES)}
                            {--path= : Output file (default: database/translations/{lang}.json)}';

    protected $description = 'Write all app_translations of a language into a JSON file (untranslated ones as "") for bulk translating';

    public function handle(): int
    {
        $lang = strtolower(trim((string) ($this->option('lang') ?: AppTranslationsService::defaultLanguage())));

        try {
            $export = AppTranslationsService::exportFile($lang, $this->option('path') ?: NULL);
        } catch (\Throwable $th) {
            $this->error($th->getMessage());
            return self::FAILURE;
        }

        $this->info("Exported ($lang): {$export['translations']} strings, {$export['untranslated']} untranslated.");
        $this->line('Path: ' . $export['path']);
        $this->line('Fill in the translations, then run translations:import --lang=' . $lang);

        return self::SUCCESS;
    }
}
