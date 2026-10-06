<?php

namespace App\Models\Employees;

use App\Models\PayrollBasicInfoModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Worker extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => WorkerStatus::WORKER_STATUS_ACTIVE,
    ];

    protected $table = 'workers';

    protected $fillable = [
        'firstName',
        'lastName',
        'company',
        'OIB',
        'working_place',
        'doe',
        'ced',
        'comment',
        'print_label',
        'status',
        'is_worker',
        'type',
    ];

    public function getWorkerAddress(): HasOne
    {
        return $this->hasOne(WorkerAddress::class, 'worker_id', 'id');
    }

    public function getWorkerContact(): HasOne
    {
        return $this->hasOne(WorkerContact::class, 'worker_id', 'id');
    }

    public function getWorkerBasicPayrollInfo(): HasOne
    {
        return $this->hasOne(PayrollBasicInfoModel::class, 'worker_id', 'id');
    }

    public function getFullNameAttribute()
    {
        return $this->attributes['firstName'] . ' ' . $this->attributes['lastName'];
    }

    public function getStatusDescriptionCroAttribute()
    {
        return WorkerStatus::setByStatus($this->attributes['status'])->description();
    }
}
