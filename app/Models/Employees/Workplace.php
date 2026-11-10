<?php

namespace App\Models\Employees;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * A workplace (contract) of the company: Employees -> Workplaces.
 * Has every field of App\Services\Config\WorkplaceFieldsConfigService::CATALOG,
 * which ones a company uses is set in customize/{company}/workplace_fields.json.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property bool $status  active
 */
class Workplace extends Model
{
    use HasFactory;

    protected $table = 'workplaces';

    protected $fillable = [
        'key', 'name', 'status', 'department', 'reports_to_id', 'nkz', 'description', 'headcount',
        'contract_type', 'duration_months', 'probation_months', 'notice_days', 'weekly_hours', 'schedule', 'location', 'special_conditions',
        'education', 'experience_years', 'licences', 'safety_training', 'medical_check_months', 'ppe',
        'pay_grade', 'hour_rate_min', 'hour_rate_max', 'fix_rate', 'field_bonus', 'home_bonus', 'travel_expense', 'phone_expense', 'monthly_bonus', 'overtime',
        'custom_fields',
    ];

    protected $casts = [
        'status'               => 'boolean',
        'reports_to_id'        => 'integer',
        'headcount'            => 'integer',
        'duration_months'      => 'integer',
        'probation_months'     => 'integer',
        'notice_days'          => 'integer',
        'weekly_hours'         => 'float',
        'special_conditions'   => 'boolean',
        'experience_years'     => 'integer',
        'licences'             => 'array',
        'safety_training'      => 'boolean',
        'medical_check_months' => 'integer',
        'ppe'                  => 'array',
        'hour_rate_min'        => 'float',
        'hour_rate_max'        => 'float',
        'fix_rate'             => 'float',
        'field_bonus'          => 'boolean',
        'home_bonus'           => 'boolean',
        'travel_expense'       => 'float',
        'phone_expense'        => 'float',
        'monthly_bonus'        => 'boolean',
        'overtime'             => 'boolean',
        'custom_fields'        => 'array',
    ];

    /**The workplace this one reports to */
    public function reportsTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to_id');
    }

    /**Workers on this workplace (any status) */
    public function workers(): HasMany
    {
        return $this->hasMany(Worker::class, 'workplace_id');
    }
}
