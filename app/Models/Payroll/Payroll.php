<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payroll';

    protected $attributes = [
        'locked' => FALSE,
    ];

    protected $casts = [
        'month'  => 'integer',
        'year'   => 'integer',
        'locked' => 'boolean',
    ];

    protected $fillable = [
        'month',
        'year',
        'locked',
    ];

    public function getPayrollItems(): HasMany
    {
        return $this->hasMany(Items::class, 'payroll_id', 'id');
    }

    public function getDeductions(): HasMany
    {
        return $this->hasMany(Deduction::class, 'payroll_id', 'id');
    }

    public function getBasicInfo(): HasMany
    {
        return $this->hasMany(BasicInfo::class, 'payroll_id', 'id');
    }

    /**
     * Limit the query to the payroll of one month/year.
     */
    public function scopeForPeriod(Builder $query, int $month, int $year): Builder
    {
        return $query->where('month', $month)->where('year', $year);
    }
}
