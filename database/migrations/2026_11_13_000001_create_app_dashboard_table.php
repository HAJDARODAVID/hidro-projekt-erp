<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_dashboard', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->comment('Which dashboard the layout is for (e.g. app-settings, home)');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('Owner of the layout');
            // TODO: add the foreign key once the companies table exists
            $table->unsignedBigInteger('company_id')->nullable()->comment('Company of the owner; global layouts are shared within the company');
            $table->string('name');
            $table->string('visibility', 10)->default('private')->comment('private = owner only, global = everybody in the same company');
            $table->json('layout')->nullable()->comment('Tiles: id, widget, title, w, h, settings');
            $table->timestamps();

            $table->index(['type', 'user_id']);
            $table->index(['type', 'visibility', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_dashboard');
    }
};
