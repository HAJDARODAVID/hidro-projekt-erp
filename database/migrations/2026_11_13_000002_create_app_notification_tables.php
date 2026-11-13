<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New notification system (runs next to the old notifications / notification_seen tables).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_notification', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->comment('Notification type, see NotificationTypes (e.g. sys_error)');
            $table->string('level', 10)->default('info')->comment('info, success, warning, error, critical');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable()->comment('Type specific payload (e.g. exception, trace, request)');

            // Grouping: the same fingerprint (per type + company) is one row with a counter
            $table->string('fingerprint', 64)->nullable();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            // Who caused it / who may see it
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->comment('User that caused / created it');
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->cascadeOnDelete()->comment('Direct notification for one user');
            $table->string('permission')->nullable()->comment('ACL permission needed to see it (NULL = everybody)');
            // TODO: add the foreign key once the companies table exists
            $table->unsignedBigInteger('company_id')->nullable();

            $table->string('url', 2048)->nullable()->comment('Link to open');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'fingerprint', 'company_id']);
            $table->index(['type', 'last_seen_at']);
        });

        Schema::create('app_notification_read', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('app_notification')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();

            $table->unique(['notification_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_notification_read');
        Schema::dropIfExists('app_notification');
    }
};
