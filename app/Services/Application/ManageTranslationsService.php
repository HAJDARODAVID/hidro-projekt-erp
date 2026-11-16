<?php

namespace App\Services\Application;

use App\Models\Translation\AppTranslation;
use Illuminate\Support\Facades\DB;

/**
 * Everything the Translator module changes in app_translations.
 * Invalid input throws \InvalidArgumentException with a message that can be shown to the user.
 *
 * `value` is the text passed to translator() in the code and is compared exactly (case sensitive),
 * so it can not be edited here: a changed value would no longer match the code.
 */
class ManageTranslationsService
{
    const MAX_LENGTH = AppTranslationsSyncService::MAX_LENGTH;

    /**
     * Languages offered in the module: the ones with translations, the ones of the users and hr.
     *
     * @return array<string>
     */
    public static function languages(): array
    {
        return collect(['hr'])
            ->merge(DB::table('app_translations')->distinct()->pluck('lang'))
            ->merge(DB::table('users')->whereNotNull('lang')->distinct()->pluck('lang'))
            ->map(fn ($lang) => strtolower(trim((string) $lang)))
            ->filter(fn ($lang) => preg_match('/^[a-z]{2,5}$/', $lang))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function create(string $value, string $lang, string $translation): AppTranslation
    {
        $value = $this->cleanValue($value);
        $lang = $this->cleanLang($lang);
        $translation = $this->cleanTranslation($translation);

        if (AppTranslation::where('value', $value)->where('lang', $lang)->exists()) {
            throw new \InvalidArgumentException(translator('This text already has a translation in this language') . ': ' . $value);
        }

        return AppTranslation::create(['value' => $value, 'lang' => $lang, 'translation' => $translation]);
    }

    public function update(int $id, string $translation): AppTranslation
    {
        $row = $this->find($id);
        $row->translation = $this->cleanTranslation($translation);
        $row->save();
        return $row;
    }

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

    private function cleanTranslation(string $translation): string
    {
        if (trim($translation) === '') throw new \InvalidArgumentException(translator('The translation is required. Delete the row to show the original text again.'));
        $this->assertLength($translation);
        return $translation;
    }

    private function cleanLang(string $lang): string
    {
        $lang = strtolower(trim($lang));
        if (!preg_match('/^[a-z]{2,5}$/', $lang)) throw new \InvalidArgumentException(translator('Invalid language code') . ': ' . $lang);
        return $lang;
    }

    private function assertLength(string $text): void
    {
        if (mb_strlen($text) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(str_replace(':n', (string) self::MAX_LENGTH, translator('The text can have at most :n characters.')));
        }
    }
}
