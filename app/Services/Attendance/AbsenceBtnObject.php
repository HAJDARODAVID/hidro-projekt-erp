<?php

namespace App\Services\Attendance;

use App\Models\Employees\AttendanceAbsenceType;
use App\Services\Attendance\AbsenceBtnClassAndStyleObject;
use App\Services\Config\AttendanceStylesConfigService;
use App\Traits\WireClickTrait;

class AbsenceBtnObject extends AbsenceBtnClassAndStyleObject
{
    use WireClickTrait;

    private int $type;
    private AttendanceAbsenceType $absenceType;

    /**Config info */
    private $absenceBtnConfig;

    public function __construct(int $type, array $att = [])
    {
        $this->type = $type;
        /**Initial mounting */
        $this->setAbsenceType()
            ->setWireMethod('selectAbsenceReasonAction') //Default method name, can be changed in component :att with key wire:click
            ->setAtt($att);

        /**Setting up the style */
        $this->setBtnBackgroundColor();
    }

    /**
     * Gets and return the absent code
     * 
     * @return string
     */
    public function code()
    {
        return $this->absenceType->code();
    }

    /**
     * Gets and return the short form of the absent type
     * 
     * @return string
     */
    public function shtDesc()
    {
        return $this->absenceType->shortDesc();
    }

    /**
     * Gets and return the description of the absent type
     * 
     * @return string
     */
    public function desc()
    {
        return $this->absenceType->description();
    }

    /**
     * Set the AttendanceAbsenceType::class for the specific type.
     * 
     * @return self
     */
    private function setAbsenceType()
    {
        $this->absenceType = AttendanceAbsenceType::setByType($this->type);
        return $this;
    }

    /**
     * This will take the attributes that you will pass thru over the components HTML syntax.
     * The attributes that will come from here will not be overwritten by the setFromConfig method. 
     * Example: <x-component-name  :att='["size"=>"sm"]'/>
     * 
     * @return self
     */
    private function setAtt(array $att)
    {
        $paramKey = array_keys($att);
        foreach ($paramKey as $key) {
            switch ($key) {
                case 'size':
                    $this->setBtnSize($att[$key]);
                    break;
                case 'wire:click':
                    $this->setWireMethod($att[$key]);
                    break;
            }
        }
        return $this;
    }

    private function setBtnBackgroundColor()
    {
        /**Check if there is a app_config */
        $attendanceStylesConfigService = new AttendanceStylesConfigService;
        if ($attendanceStylesConfigService->getBackgroundColorByType($this->absenceType)) {
            $this->addBackgroundColorStyle($attendanceStylesConfigService->getBackgroundColorByType($this->absenceType));
            return $this;
        }
        /**If there is no  app_config use the default */
        if (array_key_exists($this->type, $this->defaultBackgroundColor)) {
            $this->addBackgroundColorStyle($this->defaultBackgroundColor[$this->type]);
        }
        return $this;
    }

    /**
     * Get the background color value set for the Absence elements 
     * 
     * @param AttendanceAbsenceType $type Pass thru the absence type
     */
    public static function getBackgroundColorForType(AttendanceAbsenceType $type)
    {
        return (new self($type->code()))->setBtnBackgroundColor()->getStyleElement('background-color');
    }
}
