<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Livewire\LivewireController;
use App\Models\Employees\Attendance;
use App\Models\Employees\AttendanceAbsenceType;
use App\Services\Attendance\AbsenceBtnObject;
use App\Services\Days;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

/**
 * Class AttendanceCalendar.
 * Monthly calendar of one worker's attendance: every day of the month shows the worked
 * hours or the absence reason of that day, laid out in week rows.
 */
class AttendanceCalendar extends LivewireController
{
    /**Gives the component the worker ID for the data */
    public $workerID = NULL;

    public $selectedMonth = NULL;
    public $selectedYear = NULL;

    /**Short day names of the calendar header, monday first */
    public array $dayNames = [];

    /**
     * One entry per week row of the month:
     * ['number' => week number, 'days' => 7 day cells (monday first)]
     */
    public array $weeks = [];

    public function mount($month = NULL, $year = NULL)
    {
        $this->dayNames = Days::DAY_NAME_SHORT_HR;
        $this->selectedMonth = $month ?? date('n');
        $this->selectedYear = $year ?? date('Y');

        $this->buildCalendar();
    }

    /**
     * Run when the month is changed and rebuild the calendar
     *
     * @return void
     */
    public function updatedSelectedMonth(): void
    {
        $this->buildCalendar();
    }

    /**
     * Run when the year is changed and rebuild the calendar
     *
     * @return void
     */
    public function updatedSelectedYear(): void
    {
        $this->buildCalendar();
    }

    /**
     * Rebuild the calendar, e.g. after an attendance was changed elsewhere on the page
     *
     * @return void
     */
    #[On('refresh-attendance-data')]
    public function refreshData(): void
    {
        $this->buildCalendar();
    }

    /**
     * Build the week rows of the selected period. The grid starts on the monday of the
     * week the month begins in and ends on the sunday of the week it ends in, so the
     * days around the month are shown as empty cells.
     *
     * @return void
     */
    private function buildCalendar(): void
    {
        $attendance = $this->getAttendanceByDate();

        $firstOfMonth = Carbon::createFromDate((int) $this->selectedYear, (int) $this->selectedMonth, 1)->startOfDay();
        $cursor = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $lastCell = $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $this->weeks = [];
        while ($cursor <= $lastCell) {
            $week = ['number' => $cursor->isoWeek(), 'days' => []];
            for ($day = 0; $day < 7; $day++) {
                $week['days'][] = $this->buildDay($cursor, $firstOfMonth, $attendance);
                $cursor->addDay();
            }
            $this->weeks[] = $week;
        }
    }

    /**
     * Build one day cell of the calendar.
     *
     * @param Carbon $date
     * @param Carbon $firstOfMonth
     * @param array $attendance Attendance of the period keyed by date
     * @return array
     */
    private function buildDay(Carbon $date, Carbon $firstOfMonth, array $attendance): array
    {
        $entry = $attendance[$date->format('Y-m-d')] ?? [];
        $absence = $entry['absence'] ?? NULL;

        return [
            'day'       => $date->day,
            'inMonth'   => $date->month === $firstOfMonth->month,
            'isWeekend' => $date->isWeekend(),
            'hours'     => $entry['hours'] ?? NULL,
            'absence'   => $absence ? AttendanceAbsenceType::ABSENCE_TYPE_SHT[$absence] : NULL,
            'color'     => $absence ? AbsenceBtnObject::getBackgroundColorForType(AttendanceAbsenceType::setByType($absence)) : NULL,
        ];
    }

    /**
     * The worker's attendance of the selected period keyed by date. A day with more than
     * one entry gets its hours summed and keeps the first absence reason of that day.
     *
     * @return array
     */
    private function getAttendanceByDate(): array
    {
        if (!$this->workerID) return [];

        $output = [];
        $attendance = Attendance::where('worker_id', $this->workerID)
            ->whereMonth('date', $this->selectedMonth)
            ->whereYear('date', $this->selectedYear)
            ->get();

        foreach ($attendance as $att) {
            $date = Carbon::parse($att->date)->format('Y-m-d');

            $output[$date]['hours'] = ($output[$date]['hours'] ?? 0) + (float) $att->work_hours;
            if ($att->absence_reason && !isset($output[$date]['absence'])) {
                $output[$date]['absence'] = (int) $att->absence_reason;
            }
        }
        return $output;
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.attendance-calendar');
    }
}
