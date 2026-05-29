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
        Schema::create('app_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Configuration identifier (e.g., bg_color_primary)');
            $table->longText('value')->nullable()->comment('Current value');
            $table->longText('default_value')->nullable()->comment('Default value for reset');
            $table->string('label')->nullable()->comment('Display label');
            $table->text('description')->nullable()->comment('Configuration description');
            $table->enum('data_type', ['string', 'integer', 'boolean', 'json', 'color', 'url', 'email', 'decimal'])
                ->default('string')
                ->comment('Data type for validation');
            $table->boolean('is_public')->default(false)->comment('Is editable by end users');
            $table->boolean('is_locked')->default(false)->comment('Prevent accidental changes');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('key');
            $table->index('is_public');
            $table->index('is_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_configs');
    }
};
