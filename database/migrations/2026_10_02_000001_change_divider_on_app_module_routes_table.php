<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Replaces the divider_after flag with a divider side (NULL, 'left' or 'right'),
     * so a route can define a vertical line on either side of its own tab
     * in the module tab bar (see x-ui.module.tab-links).
     */
    public function up(): void
    {
        Schema::table('app_module_routes', function (Blueprint $table) {
            $table->string('divider', 5)->nullable()->default(NULL)->after('position');
        });

        if (Schema::hasColumn('app_module_routes', 'divider_after')) {
            DB::table('app_module_routes')->where('divider_after', TRUE)->update(['divider' => 'right']);
            Schema::table('app_module_routes', function (Blueprint $table) {
                $table->dropColumn('divider_after');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_module_routes', function (Blueprint $table) {
            $table->boolean('divider_after')->default(FALSE)->after('position');
        });

        DB::table('app_module_routes')->where('divider', 'right')->update(['divider_after' => TRUE]);

        Schema::table('app_module_routes', function (Blueprint $table) {
            $table->dropColumn('divider');
        });
    }
};
