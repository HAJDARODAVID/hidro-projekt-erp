<?php

namespace App\Models\Payroll;

use App\Models\Employees\Worker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicInfo extends Model
{
    use HasFactory;

    protected $table = 'payroll_basic_info';

    protected $attributes = [
        'travel_exp' => 0,
        'phone_exp'  => 0,
        'bonus'      => FALSE,
    ];

    protected $casts = [
        'worker_id'  => 'integer',
        'h_rate'     => 'float',
        'fix_rate'   => 'float',
        'travel_exp' => 'float',
        'phone_exp'  => 'float',
        'bonus'      => 'boolean',
    ];

    protected $fillable = [
        'worker_id',
        'h_rate',
        'fix_rate',
        'travel_exp',
        'phone_exp',
        'bonus',
    ];

    public function getWorker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id', 'id');
    }
}
