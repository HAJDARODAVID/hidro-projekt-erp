<?php

namespace App\Services\Application;

use App\Models\Translation\AppTranslation;

/**
 * Everything the Translator module changes in app_translations.
 * Invalid input throws \InvalidArgumentException with a message that can be shown to the user.
 *
 * `value` is the text passed to translator() in the code and is compared exactly (case sensitive),
 * so it can not be edited here: a changed value would no longer match the code.
 * An empty translation is stored as NULL = not translated, translator() shows the original text.
 */
class ManageTranslationsService
{
    const MAX_LENGTH = AppTranslationsService::MAX_LENGTH;

    /**
     * A text the scan can not find (e.g. translator($variable)), added for the language.
     */
    public function create(string $value, string $lang, string $translation): AppTranslation
    {
        $value = $this->cleanValue($value);
        $lang = $this->cleanLang($lang);

        if (AppTranslation::where('value', $value)->where('lang', $lang)->exists()) {
            throw new \InvalidArgumentException(translator('This text already exists in this language') . ': ' . $value);
        }

        return AppTranslation::create(['value' => $value, 'lang' => $lang, 'translation' => $this->cleanTranslation($translation)]);
    }

    /** An empty translation clears it (the original text is shown again) */
    public function update(int $id, string $translation): AppTranslation
    {
        $row = $this->find($id);
        $row->translation = $this->cleanTranslation($translation);
        $row->save();
        return $row;
    }

    /** A text that is still in the code is added again (untranslated) by the next scan */
    public function delete(int $id): void
    {
        $this->find($id)->delete();
    }

    private function find(int $id): AppTranslation
    {
        $row = AppTranslation::find($id);
        if (!$row) throw new \InvalidArgumentException(translator('The translation does not exist (anymore).'));
        return $row;
    }

    /** Spaces at the start / end are kept: fragments like "Payroll info for: " are joined in the code */
    private function cleanValue(string $value): string
    {
        if (trim($value) === '') throw new \InvalidArgumentException(translator('The text is required.'));
        $this->assertLength($value);
        return $value;
    }

    private function cleanTranslation(string $translation): ?string
    {
        if (trim($translation) === '') return NULL;
        $this->assertLength($translation);
        return $translation;
    }

    private function cleanLang(string $lang): string
    {
        $lang = strtolower(trim($lang));
        if (!AppTranslationsService::isAllowedLanguage($lang)) {
            throw new \InvalidArgumentException(translator('The language is not allowed') . ': ' . $lang);
        }
        return $lang;
    }

    private function assertLength(string $text): void
    {
        if (mb_strlen($text) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(str_replace(':n', (string) self::MAX_LENGTH, translator('The text can have at most :n characters.')));
        }
    }
}
