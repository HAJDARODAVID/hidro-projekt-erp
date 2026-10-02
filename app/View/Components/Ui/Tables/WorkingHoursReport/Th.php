<?php

namespace App\View\Components\Ui\Tables\WorkingHoursReport;

use App\Services\Attendance\WorkingDayReportStyleService;
use Closure;
use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use App\Services\ConvertArrayToStyleString;

class Th extends Component
{
    private $styleObject;
    public $style = [];
    public $class;

    public $lwAction = NULL;
    public $lwActionAtt = NULL;
    /**
     * Create a new component instance.
     */
    public function __construct($att = NULL, $day = NULL, $lwAction = NULL, $lwActionAtt = NULL)
    {
        $this->lwAction = $lwAction;
        $this->lwActionAtt = $lwActionAtt;
        $this->styleObject = new WorkingDayReportStyleService();
        $att = explode('.', $att ?? '');
        foreach ($att as $item) {
            $itemExploded = explode(':', $item);
            $method = $itemExploded[0] ?? NULL;
            $attribute = $itemExploded[1] ?? NULL;
            if (method_exists(get_class($this), $method)) $this->$method($attribute);
        }
        if ($day > 5) $this->style = array_merge($this->style, $this->styleObject->weekendStyle());
        if ($lwAction) $this->style = array_merge($this->style, $this->styleObject->clickableField());
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
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $this->style = ConvertArrayToStyleString::array($this->style);
        return view('components.ui.tables.working-hours-report.th');
    }
}
