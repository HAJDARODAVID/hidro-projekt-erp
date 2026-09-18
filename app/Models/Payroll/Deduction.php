<?php

namespace App\Models\Payroll;

use App\Models\Employees\Worker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deduction extends Model
{
    use HasFactory;

    protected $table = 'payroll_deductions';

    protected $casts = [
        'payroll_id' => 'integer',
        'worker_id'  => 'integer',
        'amount'     => 'float',
    ];

    protected $fillable = [
        'payroll_id',
        'worker_id',
        'amount',
        'reason',
    ];

    public function getPayroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

    public function getWorker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id', 'id');
    }
}
