<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceModel extends Model
{
    use HasFactory;

    const ABSENCE_REASON_SICK_LEAVE = 10;
    const ABSENCE_REASON_PAID_LEAVE = 20;
    const ABSENCE_REASON_HOLIDAY    = 30;

    const ABSENCE_REASON = array(
        self::ABSENCE_REASON_SICK_LEAVE => 'Sick leave',
        self::ABSENCE_REASON_PAID_LEAVE => 'Paid leave',
        self::ABSENCE_REASON_HOLIDAY    => 'Holiday',
    );

    const ABSENCE_REASON_SHT_TXT = array(
        self::ABSENCE_REASON_SICK_LEAVE => 'BO',
        self::ABSENCE_REASON_PAID_LEAVE => 'GO',
        self::ABSENCE_REASON_HOLIDAY    => 'BL',
    );

    protected $table = 'attendance';

    protected $fillable = [
        'worker_id',
        'working_day_record_id',
        'type',
        'work_hours',
        'absence_reason',
        'date',
    ];

    public function getWorkerInfo(): HasOne
    {
        return $this->hasOne(WorkerModel::class, 'id', 'worker_id');
    }

    public function getWDRInfo(): HasOne
    {
        return $this->hasOne(WorkingDayRecordModel::class, 'id', 'working_day_record_id');
    }
}
