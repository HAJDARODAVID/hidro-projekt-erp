<?php

namespace App\View\Components\Ui\Employees;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * The month grid of a worker's attendance calendar: the day names on top, the week
 * number on the side and the worked hours or absence reason in every day cell.
 * Build the weeks with the AttendanceCalendar::class livewire component.
 */
class AttendanceCalendarGrid extends Component
{
    /**One entry per week row: ['number' => week number, 'days' => 7 day cells] */
    public array $weeks;

    /**Short day names of the header, monday first */
    public array $dayNames;

    /**
     * Create a new component instance.
     */
    public function __construct(array $weeks = [], array $dayNames = [])
    {
        $this->weeks = $weeks;
        $this->dayNames = $dayNames;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.employees.attendance-calendar-grid');
    }
}
