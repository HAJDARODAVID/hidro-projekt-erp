<?php

/*
|--------------------------------------------------------------------------
| App translations (translator() / app_translations)
|--------------------------------------------------------------------------
|
| languages: the allowed languages, comma separated in .env, e.g. TRANSLATION_LANGUAGES=hr,en
|            `php artisan translations:scan` (runs on every deploy) adds every translator() string of the
|            code to app_translations for each of these languages, with an empty (NULL) translation.
|            The first one is the default language of the Translator module.
|
| After changing .env run `php artisan config:cache` (the deploy does it).
|
*/

$languages = collect(explode(',', (string) env('TRANSLATION_LANGUAGES', 'hr')))
    ->map(fn ($lang) => strtolower(trim($lang)))
    ->filter(fn ($lang) => preg_match('/^[a-z]{2,5}$/', $lang))
    ->unique()
    ->values()
    ->all();

return [
    'languages' => $languages ?: ['hr'],
];
