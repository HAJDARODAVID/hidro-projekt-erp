<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_installations', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->unique();
            $table->string('installation_type')->comment('Type of installation (e.g., app_config, seed_data)');
            $table->text('data')->nullable()->comment('Serialized data from the installation file');
            $table->boolean('success')->default(false);
            $table->text('error')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_installations');
    }
};
