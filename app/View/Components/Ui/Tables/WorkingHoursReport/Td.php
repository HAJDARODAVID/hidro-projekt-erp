<?php

namespace App\View\Components\Ui\Tables\WorkingHoursReport;

use Closure;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use App\Services\ConvertArrayToStyleString;
use App\Models\Employees\AttendanceAbsenceType;
use App\Services\Attendance\WorkingDayReportStyleService;

class Td extends Component
{
    private $styleObject;
    public $style = ['text-align' => 'center'];
    public $attendance = null;
    public $action = null;
    public $actionParam = null;

    /**
     * Create a new component instance.
     */
    public function __construct($att = null, $date = null, $attendance = null, array|null $action = null)
    {
        $this->attendance = $attendance;
        $this->styleObject = new WorkingDayReportStyleService();
        $this->setAction($action);
        if ($date->format('N') > 5 && $attendance == null) {
            $this->styleSetUp($this->styleObject->weekendStyle());
        } else {
            /**Are hours set */
            if (is_numeric($attendance)) $this->styleSetUp($this->styleObject->checkIfOver($attendance, 12)->good());
            /**Missing attendance style */
            if ($attendance == null && now() > $date) $this->styleSetUp($this->styleObject->attendanceMissing());
            /**Error style */
            if ($attendance == 'ERR') $this->styleSetUp($this->styleObject->error());
            /**Other absence */
            if (in_array($attendance, AttendanceAbsenceType::ABSENCE_TYPE_SHT)) $this->styleSetUp($this->styleObject->otherAbsence($attendance));
        }
        $att = explode('.', $att);
        foreach ($att as $item) {
            $itemExploded = explode(':', $item);
            $method = $itemExploded[0] ?? null;
            $attribute = $itemExploded[1] ?? null;
            if (method_exists(get_class($this), $method)) $this->$method($attribute);
        }
    }

    /**
     * Set up additional style
     * 
     * @return void
     */
    private function styleSetUp($style): void
    {
        $this->style = array_merge($this->style, $style);
    }

    /**
     * Text alignment
     * 
     * @return void
     */
    private function text($att): void
    {
        $this->style['text-align'] = $att;
        return;
    }

    /**
     * Width setter
     * 
     * @return void
     */
    private function width($att): void
    {
        $this->style['width'] = $att;
        return;
    }

    /**
     * Width setter
     * 
     * @return void
     */
    private function border($att): void
    {
        $att = explode('-', $att);
        $this->style['border-' . $att[0]] = $output = $att[1] * 1 . "px " . $att[2] . " " . $att[3] . ' !Important';
        //dd($this->style);
        return;
    }

    private function setAction(array|null $action): void
    {
        /**Set action name */
        if (isset($action[0])) {
            $this->action = $action[0];
            unset($action[0]);
            /**Set action params */
            if (!empty($action)) $this->actionParam = json_encode($action);
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->style = ConvertArrayToStyleString::array($this->style);
        return view('components.ui.tables.working-hours-report.td');
    }
}
