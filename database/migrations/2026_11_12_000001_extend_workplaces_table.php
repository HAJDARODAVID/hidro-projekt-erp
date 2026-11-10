<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Workplaces (contracts): every field of the catalog
     * (App\Services\Config\WorkplaceFieldsConfigService::CATALOG) gets a column.
     * Which of them a company uses is decided by customize/{company}/workplace_fields.json.
     * `name` and `status` (= active) already exist.
     */
    public function up(): void
    {
        Schema::table('workplaces', function (Blueprint $table) {
            // Basic
            $table->string('key', 50)->nullable()->unique()->after('id');
            $table->string('department')->nullable()->after('name');
            $table->unsignedBigInteger('reports_to_id')->nullable()->after('department');
            $table->string('nkz', 20)->nullable()->after('reports_to_id')->comment('Occupation code (Nacionalna klasifikacija zanimanja)');
            $table->text('description')->nullable()->after('nkz');
            $table->unsignedSmallInteger('headcount')->nullable()->after('description')->comment('Planned headcount');

            // Contract
            $table->string('contract_type')->nullable();
            $table->unsignedSmallInteger('duration_months')->nullable()->comment('Default duration of a fixed-term contract');
            $table->unsignedTinyInteger('probation_months')->nullable();
            $table->unsignedSmallInteger('notice_days')->nullable();
            $table->decimal('weekly_hours', 4, 1)->nullable();
            $table->string('schedule')->nullable();
            $table->string('location')->nullable();
            $table->boolean('special_conditions')->default(FALSE)->comment('Poslovi s posebnim uvjetima rada');

            // Requirements
            $table->string('education')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->json('licences')->nullable();
            $table->boolean('safety_training')->default(FALSE);
            $table->unsignedSmallInteger('medical_check_months')->nullable()->comment('0 / NULL = not required');
            $table->json('ppe')->nullable()->comment('Protective equipment');

            // Pay (defaults for the payroll of the workers on this workplace)
            $table->string('pay_grade', 20)->nullable();
            $table->decimal('hour_rate_min', 8, 2)->nullable();
            $table->decimal('hour_rate_max', 8, 2)->nullable();
            $table->decimal('fix_rate', 10, 2)->nullable();
            $table->boolean('field_bonus')->default(FALSE);
            $table->boolean('home_bonus')->default(FALSE);
            $table->decimal('travel_expense', 8, 2)->nullable();
            $table->decimal('phone_expense', 8, 2)->nullable();
            $table->boolean('monthly_bonus')->default(FALSE);
            $table->boolean('overtime')->default(FALSE);

            // The company's own fields ("custom" in the customize file)
            $table->json('custom_fields')->nullable();

            $table->timestamps();

            $table->foreign('reports_to_id')->references('id')->on('workplaces')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workplaces', function (Blueprint $table) {
            $table->dropForeign(['reports_to_id']);
            $table->dropUnique(['key']);
            $table->dropColumn([
                'key', 'department', 'reports_to_id', 'nkz', 'description', 'headcount',
                'contract_type', 'duration_months', 'probation_months', 'notice_days', 'weekly_hours', 'schedule', 'location', 'special_conditions',
                'education', 'experience_years', 'licences', 'safety_training', 'medical_check_months', 'ppe',
                'pay_grade', 'hour_rate_min', 'hour_rate_max', 'fix_rate', 'field_bonus', 'home_bonus', 'travel_expense', 'phone_expense', 'monthly_bonus', 'overtime',
                'custom_fields', 'created_at', 'updated_at',
            ]);
        });
    }
};
