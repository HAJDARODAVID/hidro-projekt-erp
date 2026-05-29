<?php

namespace App\View\Components\Ui\Employees;

use App\Services\Attendance\AbsenceBtnsComponentsObject;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AbsenceBtns extends Component
{
    public AbsenceBtnsComponentsObject $absenceBtnObj;
    /**
     * Create a new component instance.
     */
    public function __construct(array $att = [])
    {
        $this->absenceBtnObj = new AbsenceBtnsComponentsObject($att);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.employees.absence-btns');
    }
}
