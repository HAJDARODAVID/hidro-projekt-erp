<?php

namespace App\Services\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

/**
 * Class AppTranslationsService.
 * The app translations (app_translations, read by translator()).
 *
 * Flow:
 *  - the allowed languages come from config/translations.php (TRANSLATION_LANGUAGES in .env, default hr),
 *  - every deploy runs `php artisan translations:scan`: each translator('...') string of the code is added
 *    for each allowed language with an empty (NULL) translation, existing rows are never changed,
 *  - the translations are filled in per environment in the App control center - Translator,
 *  - bulk translating (optional, manual): translations:export -> edit the JSON -> translations:import.
 *
 * Translations are NOT synced between environments on deploy.
 */
class AppTranslationsService
{
    /**Length of app_translations.value / .translation */
    const MAX_LENGTH = 255;

    /** Where the code is scanned (*.php, blade included) */
    private const SCAN_PATHS = ['app', 'resources/views', 'routes', 'config'];

    /**
     * @return array<string> allowed language codes, the first one is the default
     */
    public static function languages(): array
    {
        $languages = config('translations.languages');
        return is_array($languages) && $languages ? array_values($languages) : ['hr'];
    }

    public static function defaultLanguage(): string
    {
        return self::languages()[0];
    }

    public static function isAllowedLanguage(string $lang): bool
    {
        return in_array($lang, self::languages(), TRUE);
    }

    /** The JSON file translations:export writes and translations:import reads */
    public static function filePath(string $lang): string
    {
        return base_path('database/translations/' . $lang . '.json');
    }

    /* ----------------------------------------------------------------------------------------------
     | Scan
     * --------------------------------------------------------------------------------------------*/

    /**
     * Find every translator('...') / translator("...") string in the code.
     *
     * @return array ['strings' => [value => [files]], 'dynamic' => ["file:line" of translator($variable) calls]]
     */
    public static function scanCode(): array
    {
        $paths = array_values(array_filter(array_map('base_path', self::SCAN_PATHS), 'is_dir'));
        $finder = (new Finder)->files()->in($paths)->name('*.php')->sortByName();

        $strings = [];
        $dynamic = [];
        // translator('text' or translator("text" (a double quoted string with $ in it is dynamic)
        $pattern = '/\btranslator\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\$]|\\\\.)*)")\s*[,)]/s';
        // translator($x), translator(self::X), translator("text $x")
        $dynamicPattern = '/\btranslator\(\s*(?:(?![\'"])[^)\s]|"(?:[^"\\\\]|\\\\.)*?(?<!\\\\)\$)/';
        $ownFiles = [realpath(__FILE__), realpath(app_path('Console/Commands/ScanTranslationsCommand.php'))];

        foreach ($finder as $file) {
            if (in_array($file->getRealPath(), $ownFiles, TRUE)) continue; // their own examples
            $content = $file->getContents();
            $relative = str_replace('\\', '/', substr($file->getRealPath(), strlen(base_path()) + 1));

            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $value = isset($m[2]) && $m[2] !== ''
                        ? stripcslashes($m[2])                              // "..." : PHP escapes
                        : str_replace(["\\'", '\\\\'], ["'", '\\'], $m[1]); // '...' : only \' and \\
                    if (trim($value) === '') continue;
                    $strings[$value][$relative] = TRUE;
                }
            }

            if (preg_match_all($dynamicPattern, $content, $calls, PREG_OFFSET_CAPTURE)) {
                foreach ($calls[0] as [, $offset]) {
                    // skip the helper's own definition
                    if (preg_match('/function\s+$/', substr($content, max(0, $offset - 20), 20))) continue;
                    $dynamic[] = $relative . ':' . (substr_count(substr($content, 0, $offset), "\n") + 1);
                }
            }
        }

        return ['strings' => array_map('array_keys', $strings), 'dynamic' => $dynamic];
    }

    /**
     * Scan the code and add the strings that are missing in app_translations, for every allowed language,
     * with an empty (NULL) translation. Existing rows (translated or not) are never changed or deleted.
     *
     * @return array ['strings' => int, 'added' => [lang => int], 'untranslated' => [lang => int],
     *                'too_long' => [values], 'unused' => [lang => int], 'dynamic' => [locations]]
     */
    public static function addMissingStrings(): array
    {
        $scan = self::scanCode();
        $stats = ['strings' => count($scan['strings']), 'added' => [], 'untranslated' => [], 'too_long' => [], 'unused' => [], 'dynamic' => $scan['dynamic']];

        $values = [];
        foreach (array_keys($scan['strings']) as $value) {
            $value = (string) $value;
            if (mb_strlen($value) > self::MAX_LENGTH) {
                $stats['too_long'][] = $value;
                continue;
            }
            $values[$value] = TRUE;
        }

        foreach (self::languages() as $lang) {
            $existing = array_flip(DB::table('app_translations')->where('lang', $lang)->pluck('value')->map(fn ($v) => (string) $v)->all());

            $rows = [];
            foreach (array_keys($values) as $value) {
                if (!isset($existing[(string) $value])) $rows[] = ['value' => (string) $value, 'lang' => $lang, 'translation' => NULL];
            }
            // insertOrIgnore: the unique (value, lang) index keeps parallel runs from creating doubles
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('app_translations')->insertOrIgnore($chunk);
            }

            $stats['added'][$lang] = count($rows);
            $stats['untranslated'][$lang] = DB::table('app_translations')->where('lang', $lang)->where(fn ($q) => $q->whereNull('translation')->orWhere('translation', ''))->count();
            $stats['unused'][$lang] = count(array_diff_key($existing, $values));
        }

        return $stats;
    }

    /* ----------------------------------------------------------------------------------------------
     | JSON files (manual bulk translating): { "value": "translation" }
     * --------------------------------------------------------------------------------------------*/

    /**
     * Write every string of a language into a JSON file, untranslated ones with "".
     *
     * @return array ['translations' => int, 'untranslated' => int, 'path' => string]
     */
    public static function exportFile(string $lang, ?string $path = NULL): array
    {
        self::assertLanguage($lang);
        $path ??= self::filePath($lang);

        $translations = [];
        foreach (DB::table('app_translations')->where('lang', $lang)->orderByRaw('value COLLATE utf8mb4_unicode_ci')->get(['value', 'translation']) as $row) {
            $translations[$row->value] = (string) $row->translation;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode((object) $translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");

        return [
            'translations' => count($translations),
            'untranslated' => count(array_filter($translations, fn ($t) => trim($t) === '')),
            'path'         => $path,
        ];
    }

    /**
     * Import a JSON file of a language: the filled in entries create / overwrite the translation, "" is skipped.
     * $onlyEmpty: only fill strings that have no translation yet.
     *
     * @throws \Exception
     */
    public static function importFile(string $lang, ?string $path = NULL, bool $onlyEmpty = FALSE): array
    {
        self::assertLanguage($lang);
        $path ??= self::filePath($lang);
        if (!File::exists($path)) throw new \Exception("The file does not exist: $path (run translations:export first)");

        $translations = json_decode(File::get($path), TRUE);
        if (!is_array($translations)) throw new \Exception("The file is not valid JSON: $path");

        return self::import($lang, $translations, $onlyEmpty);
    }

    /**
     * Create / overwrite translations: a row is matched by its exact value + lang, empty translations are
     * skipped, nothing is deleted.
     *
     * @param array $translations [value => translation]
     * @param bool $onlyEmpty TRUE: only fill rows that have no translation yet (keep the ones made on this environment)
     * @return array ['created' => int, 'updated' => int, 'unchanged' => int, 'skipped_empty' => int, 'kept' => int]
     * @throws \Exception
     */
    public static function import(string $lang, array $translations, bool $onlyEmpty = FALSE): array
    {
        if (!preg_match('/^[a-z]{2,5}$/', $lang)) throw new \Exception('Invalid language code: ' . json_encode($lang));

        $stats = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped_empty' => 0, 'kept' => 0];

        DB::transaction(function () use ($lang, $translations, $onlyEmpty, &$stats) {
            // Exact (case sensitive) map of what is in the database now
            $existing = [];
            foreach (DB::table('app_translations')->where('lang', $lang)->get(['value', 'translation']) as $row) {
                $existing[$row->value] = $row->translation;
            }

            $rows = [];
            foreach ($translations as $value => $translation) {
                // Not trimmed: fragments like "Payroll info for: " are joined in the code, their spaces matter
                $value = (string) $value;
                $translation = (string) $translation;
                if (trim($value) === '' || trim($translation) === '') {
                    $stats['skipped_empty']++;
                    continue;
                }
                if (mb_strlen($value) > self::MAX_LENGTH || mb_strlen($translation) > self::MAX_LENGTH) {
                    throw new \Exception('Longer than ' . self::MAX_LENGTH . " characters ($lang): " . mb_substr($value, 0, 80));
                }

                $current = $existing[$value] ?? NULL;
                if (!array_key_exists($value, $existing)) {
                    $stats['created']++;
                } elseif ($current === $translation) {
                    $stats['unchanged']++;
                    continue;
                } elseif ($onlyEmpty && $current !== NULL && $current !== '') {
                    $stats['kept']++;
                    continue;
                } else {
                    $stats['updated']++;
                }
                $rows[] = ['value' => $value, 'lang' => $lang, 'translation' => $translation];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('app_translations')->upsert($chunk, ['value', 'lang'], ['translation']);
            }
        });

        return $stats;
    }

    private static function assertLanguage(string $lang): void
    {
        if (!self::isAllowedLanguage($lang)) {
            throw new \Exception("The language \"$lang\" is not allowed. Allowed: " . implode(', ', self::languages()) . ' (TRANSLATION_LANGUAGES in .env)');
        }
    }
}
