<?php

namespace App\Console\Commands;

use App\Services\Application\AppTranslationsSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

/**
 * Finds every translator('...') string in the code and writes the ones without a translation into a JSON file:
 * { "English text": "translation" }. Fill in the translations, then translations:import + translations:export
 * (see AppTranslationsSyncService for the whole workflow).
 *
 * Running it again keeps the translations already in the file and only adds new strings, so it is safe to repeat.
 * translator($variable) calls can not be found this way: they are listed with -v.
 */
class ScanTranslationsCommand extends Command
{
    protected $signature = 'translations:scan
                            {--lang=hr : Language code the translations are for}
                            {--all : Also list strings that already have a translation in the database}
                            {--path= : Output file (default: database/translations/{lang}.json)}';

    protected $description = 'Scan the code for translator() strings and write the untranslated ones into a JSON file';

    /** Where the code is scanned (*.php, blade included) */
    private const SCAN_PATHS = ['app', 'resources/views', 'routes', 'config'];

    private const MAX_LENGTH = AppTranslationsSyncService::MAX_LENGTH;

    public function handle(): int
    {
        $lang = strtolower(trim((string) $this->option('lang')));
        if (!preg_match('/^[a-z]{2,5}$/', $lang)) {
            $this->error('Invalid language code: ' . $lang);
            return self::FAILURE;
        }
        $path = $this->option('path') ?: AppTranslationsSyncService::workFilePath($lang);

        [$strings, $dynamic] = $this->scan();

        $translated = $this->option('all')
            ? []
            : DB::table('app_translations')->where('lang', $lang)->pluck('translation', 'value')->all();

        // Keep what is already in the file (translations filled in earlier), add the new strings with ""
        $existing = File::exists($path) ? (json_decode(File::get($path), TRUE) ?? []) : [];
        if (!is_array($existing)) $existing = [];

        $output = [];
        $added = 0;
        foreach (array_keys($strings) as $value) {
            if (isset($translated[$value])) continue;
            if (!array_key_exists($value, $existing)) $added++;
            $output[$value] = $existing[$value] ?? '';
        }
        // Strings that are gone from the code, but already translated in the file, are kept (and reported)
        $orphans = array_diff_key(array_filter($existing, fn ($t) => $t !== ''), $output, $translated);
        $output += $orphans;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode((object) $output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

        $this->info('Strings in the code: ' . count($strings));
        $this->line('Already translated in the database (' . $lang . '): ' . count(array_intersect_key($strings, $translated)));
        $this->line('In the file: ' . count($output) . ' (new: ' . $added . ', still empty: ' . count(array_filter($output, fn ($t) => $t === '')) . ')');
        $this->line('Path: ' . $path);

        foreach (array_keys($strings) as $value) {
            if (mb_strlen($value) > self::MAX_LENGTH) {
                $this->warn('Longer than ' . self::MAX_LENGTH . ' characters, can not be stored in app_translations: ' . mb_substr($value, 0, 80) . '…');
            }
        }
        if ($orphans) {
            $this->warn(count($orphans) . ' translated strings in the file are no longer in the code (kept): ' . implode(' | ', array_map(fn ($s) => mb_substr($s, 0, 40), array_keys($orphans))));
        }
        if ($dynamic) {
            $this->warn(count($dynamic) . ' translator() calls with a variable can not be scanned (see them with -v).');
            if ($this->getOutput()->isVerbose()) {
                foreach ($dynamic as $location) $this->line('  ' . $location);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array [ [string => [files]], [ "file:line" of translator($variable) calls ] ]
     */
    private function scan(): array
    {
        $paths = array_values(array_filter(array_map('base_path', self::SCAN_PATHS), 'is_dir'));
        $finder = (new Finder)->files()->in($paths)->name('*.php')->sortByName();

        $strings = [];
        $dynamic = [];
        // translator('text' or translator("text" (a double quoted string with $ in it is dynamic)
        $pattern = '/\btranslator\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\$]|\\\\.)*)")\s*[,)]/s';

        foreach ($finder as $file) {
            if ($file->getRealPath() === realpath(__FILE__)) continue; // its own examples
            $content = $file->getContents();
            $relative = str_replace('\\', '/', substr($file->getRealPath(), strlen(base_path()) + 1));

            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $value = isset($m[2]) && $m[2] !== ''
                        ? stripcslashes($m[2])                          // "..." : PHP escapes
                        : str_replace(["\\'", '\\\\'], ["'", '\\'], $m[1]); // '...' : only \' and \\
                    if (trim($value) === '') continue;
                    $strings[$value][$relative] = TRUE;
                }
            }

            // translator($x), translator(self::X), translator("text $x")
            if (preg_match_all('/\btranslator\(\s*(?:(?![\'"])[^)\s]|"(?:[^"\\\\]|\\\\.)*?(?<!\\\\)\$)/', $content, $calls, PREG_OFFSET_CAPTURE)) {
                foreach ($calls[0] as [, $offset]) {
                    // skip the helper's own definition
                    if (preg_match('/function\s+$/', substr($content, max(0, $offset - 20), 20))) continue;
                    $dynamic[] = $relative . ':' . (substr_count(substr($content, 0, $offset), "\n") + 1);
                }
            }
        }

        return [array_map('array_keys', $strings), $dynamic];
    }
}
