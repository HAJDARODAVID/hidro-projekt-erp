<?php

namespace App\Services\Attendance;

use DateTime;
use App\Services\Months;
use App\Models\Employees\AttendanceAbsenceType;

/**
 * Class WorkerHoursDataObject.
 */
class WorkerHoursDataObject
{
    /** @var array */
    protected $data;

    protected $worker;
    protected $date;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Set the worker id on the instance.
     * 
     * @return WorkerHoursDataObject
     */
    public function worker(int $id)
    {
        $this->worker = $id;
        return $this;
    }

    /**
     * Set the date on the instance.
     * 
     * @return WorkerHoursDataObject
     */
    public function date(DateTime $date)
    {
        $this->date = $date;
        return $this;
    }

    public function attendance()
    {
        if (!isset($this->data[$this->worker])) return NULL;
        if (!isset($this->data[$this->worker]['attendance'])) return NULL;
        $att = $this->data[$this->worker]['attendance'][$this->date->format('Y-m-d')] ?? NULL;
        if ($att) {
            if ($att['error']) return 'ERR';
            if ($att['hours']) return $att['hours'];
            if (count($att['absence']) > 0) return AttendanceAbsenceType::setByType($att['absence'][0])->shortDesc();
        } else {
            return NULL;
        }
        return NULL;
    }

    /**
     * Get all the workers ID | name from the attendance.
     * 
     * @return array
     */
    public function getWorkers()
    {
        $output = [];
        foreach ($this->data as $workerID => $info) {
            if ($workerID != 'info') $output[$workerID] = $info['worker-info'];
        }
        return $output;
    }

    /**
     * Get all dates for the given month/year.
     * 
     * @return array
     */
    public function getDates()
    {
        $dates = $this->data['info']['date'];
        return Months::daysOfMonth($dates['month'], $dates['year']);
    }

    /**
     * Get a monthly summary (total worked hours and absence day counts) for a worker.
     *
     * @param int $id Worker ID
     * @return array
     */
    public function monthlySummary(int $id)
    {
        $summary = ['hours' => 0, 'SL' => 0, 'PL' => 0, 'HD' => 0, 'ERR' => 0];
        foreach ($this->getDates() as $date) {
            $value = $this->date($date)->worker($id)->attendance();
            if ($value === NULL) continue;
            if (is_numeric($value)) {
                $summary['hours'] += (float) $value;
            } elseif (key_exists($value, $summary)) {
                $summary[$value]++;
            }
        }
        return $summary;
    }

    /**
     * Get the month from the data.
     * 
     * @return int
     */
    public function getMonth()
    {
        return $this->data['info']['date']['month'];
    }

    /**
     * Get the year from the data.
     * 
     * @return int
     */
    public function getYear()
    {
        return $this->data['info']['date']['year'];
    }
}
