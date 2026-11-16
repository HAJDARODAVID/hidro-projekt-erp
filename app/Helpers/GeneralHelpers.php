<?php

use App\Models\Translation\AppTranslation;
use Illuminate\Support\Facades\Session;

if (!function_exists('translator')) {
    /**
     * Use for translating stuff.
     *
     * @param string $value The word to be translated
     * @param string $lang Set the language to translate to | If NULL it will check the session 
     * @return string
     */
    function translator(string $value, string|NULL $lang = NULL): string
    {
        $output = $value;

        /**Check if the Session has the lang key, if the lang argument is null */
        if ($lang == NULL) $lang = Session::get('lang', NULL);

        if ($lang !== NULL) {
            /**NULL / empty translation = not translated yet (added by translations:scan): show the original text */
            $translation = AppTranslation::where('value', $value)->where('lang', $lang)->value('translation');
            if ($translation !== NULL && $translation !== '') $output = $translation;
        }

        return $output;
    }
}
