<?php

namespace App\Models\Payroll;

use App\Models\Employees\Worker;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Items extends Model
{
    use HasFactory;

    protected $table = 'payroll_items';

    /**payroll_data is a JSON column, the cast handles the encoding/decoding */
    protected $casts = [
        'payroll_id'   => 'integer',
        'worker_id'    => 'integer',
        'payroll_data' => 'array',
    ];

    protected $fillable = [
        'payroll_id',
        'worker_id',
        'payroll_data',
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
