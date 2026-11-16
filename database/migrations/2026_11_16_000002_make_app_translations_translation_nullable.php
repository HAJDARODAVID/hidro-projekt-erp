<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * translations:scan adds every translator() string of the code for each allowed language with an empty
 * translation (NULL = not translated yet, translator() shows the original text).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        DB::statement('ALTER TABLE app_translations MODIFY translation VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
        DB::table('app_translations')->where('translation', '')->update(['translation' => NULL]);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') return;

        DB::table('app_translations')->whereNull('translation')->delete();
        DB::statement('ALTER TABLE app_translations MODIFY translation VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL');
    }
};
