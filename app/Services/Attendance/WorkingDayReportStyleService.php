<?php

namespace App\Services\Attendance;

/**
 * Class WorkingDayReportStyleService.
 */
class WorkingDayReportStyleService
{
    /**Set a flag if overtime exceeds threshold */
    private $isOverThreshold = FALSE;

    /**
     * Return the style for missing attendance.
     * 
     * @return array
     */
    public function weekendStyle(): array
    {
        return [
            'background-color' => "#c9c9c9",
        ];
    }

    /**
     * Return the style for missing attendance.
     * 
     * @return array
     */
    public function attendanceMissing(): array
    {
        return [
            'background-color' => "#FF311C",
        ];
    }

    /**
     * Return the style attribute for a ERR attendance.
     * 
     * @return array
     */
    public function error(): array
    {
        return [
            'background-color' => "#000000",
            'font-weight' => 'bold',
            'font-size' => '11px',
            'color' => 'white',
            'padding' => '8px 5px',
        ];
    }

    /**
     * Style for hours attendance.
     * Consider the overtime threshold, and set style accordingly.
     * 
     * @return array
     */
    public function good(): array
    {
        $output = ['background-color' => '#67de43'];
        if ($this->isOverThreshold) {
            $output['font-weight'] = 'bold';
            $output['color'] = 'red';
        }
        return $output;
    }

    /**
     * Check if the given value is smaller then the threshold.
     * 
     * @return WorkingDayReportStyleService
     */
    public function checkIfOver($value, $threshold): self
    {
        $this->isOverThreshold = $value >= $threshold ? TRUE : FALSE;
        return $this;
    }

    /**
     * Return the style for other absence attendance.
     * The background-color is resolved separately (redis/db configurable), see Td component.
     *
     * @return array
     */
    public function otherAbsence($absence): array
    {
        return [
            'font-weight' => 'bold',
        ];
    }

    /**
     * Return the style for cursor pointer.
     * 
     * @return array
     */
    public function clickableField()
    {
        return [
            'cursor' => 'pointer',
        ];
    }
}
