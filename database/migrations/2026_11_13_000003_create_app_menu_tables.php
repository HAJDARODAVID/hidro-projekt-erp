<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menus built with the menu builder (app control center > Menu builder).
 *
 * app_menu       one menu per key (e.g. "main" = the sidebar) and company
 * app_menu_item  the items of a menu as a tree (parent_id), ordered by position
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_menu', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50);
            $table->string('name', 100);
            // TODO: foreign key once the companies table exists (see CompanyContext)
            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('active')->default(TRUE);
            $table->timestamps();

            $table->index(['key', 'company_id']);
        });

        Schema::create('app_menu_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('app_menu')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('app_menu_item')->cascadeOnDelete();
            $table->string('type', 20);                 // link | group | header | divider
            $table->string('title', 100)->nullable();
            $table->string('icon', 60)->nullable();     // bootstrap icon name without "bi-"
            $table->string('route_name', 150)->nullable();
            $table->string('url', 2048)->nullable();
            $table->boolean('new_tab')->default(FALSE);
            $table->string('permission', 150)->nullable(); // extra ACL permission key
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(TRUE);
            $table->timestamps();

            $table->index(['menu_id', 'parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_menu_item');
        Schema::dropIfExists('app_menu');
    }
};
