<?php

namespace App\Console\Commands;

use App\Services\Application\AppTranslationsService;
use Illuminate\Console\Command;

/**
 * Finds every translator('...') string in the code and adds the missing ones to app_translations, for every
 * allowed language (config/translations.php), with an empty (NULL) translation. Runs on every deploy.
 * Existing rows are never changed. translator($variable) calls can not be found this way: they are listed with -v.
 */
class ScanTranslationsCommand extends Command
{
    protected $signature = 'translations:scan';

    protected $description = 'Add the translator() strings of the code to app_translations (empty translation) for every allowed language';

    public function handle(): int
    {
        $stats = AppTranslationsService::addMissingStrings();

        $this->info('Strings in the code: ' . $stats['strings'] . ' | languages: ' . implode(', ', AppTranslationsService::languages()));
        foreach ($stats['added'] as $lang => $added) {
            $this->line("  $lang: $added added, {$stats['untranslated'][$lang]} untranslated"
                . ($stats['unused'][$lang] ? ", {$stats['unused'][$lang]} no longer in the code (kept)" : ''));
        }

        foreach ($stats['too_long'] as $value) {
            $this->warn('Longer than ' . AppTranslationsService::MAX_LENGTH . ' characters, not added: ' . mb_substr($value, 0, 80) . '…');
        }
        if ($stats['dynamic']) {
            $this->line(count($stats['dynamic']) . ' translator() calls with a variable can not be scanned (see them with -v), add those texts in the Translator.');
            if ($this->getOutput()->isVerbose()) {
                foreach ($stats['dynamic'] as $location) $this->line('  ' . $location);
            }
        }

        return self::SUCCESS;
    }
}
