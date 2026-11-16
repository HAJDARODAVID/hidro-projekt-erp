<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * app_translations.value was case-insensitive (utf8mb4_unicode_ci): translator('new') could get the
 * translation of "New", translator('key') the one of "KEY". The value is now compared exactly (utf8mb4_bin)
 * and (value, lang) is unique and indexed, so every translator() call is one index lookup.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        // Exact duplicates (same value + lang) would break the unique index: keep the newest row
        DB::statement('DELETE t1 FROM app_translations t1
            JOIN app_translations t2 ON BINARY t1.value = BINARY t2.value AND t1.lang = t2.lang AND t1.id < t2.id');

        DB::statement('ALTER TABLE app_translations
            MODIFY value VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
            MODIFY lang VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');

        Schema::table('app_translations', function ($table) {
            $table->unique(['value', 'lang'], 'app_translations_value_lang_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        Schema::table('app_translations', function ($table) {
            $table->dropUnique('app_translations_value_lang_unique');
        });

        DB::statement('ALTER TABLE app_translations
            MODIFY value VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
            MODIFY lang VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    }
};
