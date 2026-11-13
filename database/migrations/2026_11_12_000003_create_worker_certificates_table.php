<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Medical check-ups, trainings and certificates of a worker, with their due date.
     * One row per check / certificate: a renewal is a new row, so the history stays.
     * Types: App\Models\Employees\WorkerCertificate::TYPES.
     */
    public function up(): void
    {
        Schema::create('worker_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('worker_id');
            $table->string('type', 30)->comment('WorkerCertificate::TYPES');
            $table->string('title')->comment('e.g. the licence name; for one-of-a-kind types the type label');
            $table->date('issued_at')->nullable()->comment('Done / issued on');
            $table->date('valid_until')->nullable()->comment('Due date, NULL = does not expire');
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->comment('users.id');
            $table->timestamps();

            $table->foreign('worker_id')->references('id')->on('workers')->cascadeOnDelete();
            $table->index(['worker_id', 'type', 'title']);
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_certificates');
    }
};
