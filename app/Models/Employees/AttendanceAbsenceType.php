<?php

namespace App\Models\Employees;

class AttendanceAbsenceType
{
    private $type;

    const ABSENCE_TYPE_SICK_LEAVE = 10;
    const ABSENCE_TYPE_PAID_LEAVE = 20;
    const ABSENCE_TYPE_HOLIDAY    = 30;

    const ABSENCE_TYPE = [
        self::ABSENCE_TYPE_SICK_LEAVE => 'Sick leave',
        self::ABSENCE_TYPE_PAID_LEAVE => 'Paid leave',
        self::ABSENCE_TYPE_HOLIDAY    => 'Holiday',
    ];

    const ABSENCE_TYPE_SHT = [
        self::ABSENCE_TYPE_SICK_LEAVE => 'SL',
        self::ABSENCE_TYPE_PAID_LEAVE => 'PL',
        self::ABSENCE_TYPE_HOLIDAY    => 'HD',
    ];

    private $massAssignable = [self::ABSENCE_TYPE_PAID_LEAVE, self::ABSENCE_TYPE_HOLIDAY];

    private $available = [self::ABSENCE_TYPE_SICK_LEAVE, self::ABSENCE_TYPE_PAID_LEAVE, self::ABSENCE_TYPE_HOLIDAY];

    /** Array of types that should be excluded from other */
    private $excludedTypes = [];

    private function __construct($type)
    {
        if (key_exists($type, self::ABSENCE_TYPE)) $this->type = $type;
    }

    /**
     * Create a new empty instance
     * 
     * @return self
     */
    public static function init()
    {
        return new self(null);
    }

    /**
     * Set a new instance from a absence type
     * 
     * @param string $type Passthru the absence type
     * @return self
     */
    public static function setByType(string $type)
    {
        return key_exists($type, self::ABSENCE_TYPE) ? new self($type) : null;
    }

    /**
     * Set a new instance for sick leave
     * 
     * @return self
     */
    public static function setTypeSickLeave()
    {
        return new self(self::ABSENCE_TYPE_SICK_LEAVE);
    }

    /**
     * Set a new instance for paid leave
     * 
     * @return self
     */
    public static function setTypePaidLeave()
    {
        return new self(self::ABSENCE_TYPE_PAID_LEAVE);
    }

    /**
     * Set a new instance for holiday
     * 
     * @return self
     */
    public static function setTypeHoliday()
    {
        return new self(self::ABSENCE_TYPE_HOLIDAY);
    }

    /**
     * Get the type code
     * 
     * @return string
     */
    public function code()
    {
        return $this->type;
    }

    /**
     * Get the type description
     * 
     * @return string
     */
    public function description()
    {
        return self::ABSENCE_TYPE[$this->type];
    }

    /**
     * Get the type short form description
     * 
     * @return string
     */
    public function shortDesc()
    {
        return self::ABSENCE_TYPE_SHT[$this->type];
    }

    /**
     * Get the all the employee type
     * 
     * @return array
     */
    public function getAllType($exclude = TRUE)
    {
        $output = self::ABSENCE_TYPE;
        if ($exclude) {
            foreach ($this->excludedTypes as $type) {
                unset($output[$type]);
            }
        }
        return array_keys($output);
    }

    /**
     * Get short form for sick leave.
     * 
     * @return string
     */
    public function getSickLeaveSht()
    {
        return self::ABSENCE_TYPE_SHT[self::ABSENCE_TYPE_SICK_LEAVE];
    }

    /**
     * Get short form for paid leave.
     * 
     * @return string
     */
    public function getPaidLeaveSht()
    {
        return self::ABSENCE_TYPE_SHT[self::ABSENCE_TYPE_PAID_LEAVE];
    }

    /**
     * Get short form for holiday
     * 
     * @return string
     */
    public function getHolidaySht()
    {
        return self::ABSENCE_TYPE_SHT[self::ABSENCE_TYPE_HOLIDAY];
    }

    /**
     * Get mass assignable types
     * 
     * @return array
     */
    public function getMassAssignable()
    {
        return $this->massAssignable;
    }

    /**
     * Get all available types
     * 
     * @return array
     */
    public function getAvailableTypes()
    {
        return $this->available;
    }
}
