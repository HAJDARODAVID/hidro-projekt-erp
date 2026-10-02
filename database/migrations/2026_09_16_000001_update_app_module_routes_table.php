<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds the divider_after flag: when TRUE a vertical line is shown after
     * this route's tab in the module tab bar (see x-ui.module.tab-links).
     */
    public function up(): void
    {
        Schema::table('app_module_routes', function (Blueprint $table) {
            $table->boolean('divider_after')->default(FALSE)->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_module_routes', function (Blueprint $table) {
            $table->dropColumn('divider_after');
        });
    }
};
