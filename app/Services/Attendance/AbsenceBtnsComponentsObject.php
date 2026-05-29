<?php

namespace App\Services\Attendance;

use App\Models\Employees\AttendanceAbsenceType;
use App\Services\BaseService;

/**
 * This class will define all the necessary information for AbsenceBtns::class components.
 * The class is called in the constructor. 
 */
class AbsenceBtnsComponentsObject extends BaseService
{
    private array $typesObj = [];

    public function __construct(?array $att)
    {
        foreach (AttendanceAbsenceType::init()->getAvailableTypes() as $type) {
            $this->typesObj[$type] = new AbsenceBtnObject($type, $att);
        }
    }

    public function getTypes()
    {
        return $this->typesObj;
    }
}
