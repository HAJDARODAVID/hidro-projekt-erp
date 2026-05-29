<?php

namespace App\View\Components\Ui\Calendar;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CalendarDay extends Component
{
    public array $info;
    public array $calendarClass = [];
    public array $dayClass = [];
    public string $method;

    /**
     * Create a new component instance.
     */
    public function __construct(array $info, ?string $method = 'selectDate')
    {
        $this->info = $info;
        $this->method = $method;
        $this->setCalendarClass()->setDayClass();
        dd($this);
    }

    private function setCalendarClass()
    {
        $this->calendarClass[] = 'calendar-day';
        if ($this->info['today']) $this->calendarClass[] = 'today';
        if ($this->info['month'] != $this->info['setMonth']) $this->calendarClass[] = 'empty';
        if ($this->info['day'] == $this->info['selectedDate']->format('j') && $this->info['month'] == $this->info['selectedDate']->format('n')) $this->calendarClass[] = 'selected';
        return $this;
    }

    private function setDayClass()
    {
        $this->dayClass[] = 'day-number';
        if ($this->info['month'] != $this->info['setMonth']) $this->dayClass[] = 'empty';
        return $this;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.calendar.calendar-day');
    }
}
