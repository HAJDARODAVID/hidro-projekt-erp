<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds the payroll_id column so a payroll (month/year) can be linked to the
     * worker payroll basic info rows. Nullable because the existing rows are not
     * bound to a specific payroll.
     */
    public function up(): void
    {
        Schema::table('payroll_basic_info', function (Blueprint $table) {
            $table->foreignId('payroll_id')
                ->nullable()
                ->after('worker_id')
                ->constrained('payroll')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_basic_info', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payroll_id');
        });
    }
};
