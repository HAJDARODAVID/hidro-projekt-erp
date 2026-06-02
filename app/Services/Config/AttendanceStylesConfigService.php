<?php

namespace App\Services\Config;

use App\Models\Employees\AttendanceAbsenceType;

class AttendanceStylesConfigService extends BaseConfigService
{
    protected string|array $configKeys = 'att_styles';
    protected array|null $attStyle = [];

    protected function initConfig()
    {
        $this->attStyle = $this->getValue();
    }

    /**
     * Return the background color for the 'SL' type
     * 
     * @return string|null
     */
    public function getBackgroundColorSickLeave(): string|null
    {
        $attType = AttendanceAbsenceType::setTypePaidLeave();
        if (isset($this->attStyle[$attType->code()])) {
            return $this->attStyle[$attType->code()]['background-color'];
        }
        return null;
    }

    /**
     * Return the background color for the 'PL' type
     * 
     * @return string|null
     */
    public function getBackgroundColorPaidLeave(): string|null
    {
        $attType = AttendanceAbsenceType::setTypePaidLeave();
        if (isset($this->attStyle[$attType->code()])) {
            return $this->attStyle[$attType->code()]['background-color'] ?? null;
        }
        return null;
    }

    /**
     * Return the background color for the 'HD' type
     * 
     * @return string|null
     */
    public function getBackgroundColorHoliday(): string|null
    {
        $attType = AttendanceAbsenceType::setTypeHoliday();
        if (isset($this->attStyle[$attType->code()])) {
            return $this->attStyle[$attType->code()]['background-color'] ?? null;
        }
        return null;
    }

    /**
     * Return the background color for the 'HD' type
     * 
     * @return string|null
     */
    public function getBackgroundColorByType(AttendanceAbsenceType $attType): string|null
    {
        if (isset($this->attStyle[$attType->code()])) {
            return $this->attStyle[$attType->code()]['background-color'] ?? null;
        }
        return null;
    }
}
