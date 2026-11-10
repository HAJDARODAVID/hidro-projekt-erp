<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Workers get a real link to their workplace (workers.workplace_id).
     * Until now the workplace was only a text (workers.working_place). For every text that has
     * no workplace yet, one is created (active, key made from the name), and the workers are linked.
     * workers.working_place stays and keeps being filled (old screens and the PDFs read it).
     */
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->unsignedBigInteger('workplace_id')->nullable()->after('working_place');
            $table->foreign('workplace_id')->references('id')->on('workplaces')->nullOnDelete();
        });

        // Workplaces that exist (matched by name, case and spaces ignored)
        $byName = [];
        foreach (DB::table('workplaces')->get(['id', 'name']) as $workplace) {
            $byName[$this->normalize($workplace->name)] = $workplace->id;
        }
        $usedKeys = DB::table('workplaces')->whereNotNull('key')->pluck('key')->map(fn ($key) => strtoupper($key))->all();

        // Give existing workplaces without a key one
        foreach (DB::table('workplaces')->whereNull('key')->get(['id', 'name']) as $workplace) {
            $key = $this->uniqueKey((string) $workplace->name, $usedKeys);
            DB::table('workplaces')->where('id', $workplace->id)->update(['key' => $key]);
        }

        $names = DB::table('workers')->whereNotNull('working_place')->where('working_place', '!=', '')->distinct()->pluck('working_place');
        foreach ($names as $name) {
            $normalized = $this->normalize($name);
            if (!isset($byName[$normalized])) {
                $byName[$normalized] = DB::table('workplaces')->insertGetId([
                    'key'        => $this->uniqueKey($name, $usedKeys),
                    'name'       => trim(preg_replace('/\s+/', ' ', $name)),
                    'status'     => TRUE,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('workers')->where('working_place', $name)->update(['workplace_id' => $byName[$normalized]]);
        }
    }

    /**
     * Reverse the migrations. The created workplaces stay (they may have been edited since).
     */
    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropForeign(['workplace_id']);
            $table->dropColumn('workplace_id');
        });
    }

    private function normalize(?string $name): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $name)));
    }

    /**
     * Key from the name: ASCII, uppercase, words split by "-", at most 50 chars, unique (-2, -3, ...).
     */
    private function uniqueKey(string $name, array &$usedKeys): string
    {
        $base = strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '-', Str::ascii($name)), '-'));
        $base = substr($base === '' ? 'RM' : $base, 0, 45);
        $key = $base;
        for ($i = 2; in_array($key, $usedKeys, TRUE); $i++) $key = $base . '-' . $i;
        $usedKeys[] = $key;
        return $key;
    }
};
