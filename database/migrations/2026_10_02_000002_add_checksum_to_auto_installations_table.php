<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds the checksum of the installed data, so installers that are meant to be
     * updated over time (e.g. app_modules_sync) run again when their data changes.
     */
    public function up(): void
    {
        Schema::table('auto_installations', function (Blueprint $table) {
            $table->string('checksum', 64)->nullable()->after('data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auto_installations', function (Blueprint $table) {
            $table->dropColumn('checksum');
        });
    }
};
