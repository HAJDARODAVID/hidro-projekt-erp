<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use App\Exceptions\ErrorMessage;
use App\Livewire\LivewireController;
use App\Models\Employees\AttendanceAbsenceType;
use App\Models\Employees\Worker;
use App\Services\Attendance\AttendanceUpdateService;
use App\Services\Attendance\DeleteAttendanceService;
use App\Services\Attendance\GetWorkerMonthlyAttendanceService;
use App\Services\Months;
use App\Services\WorkdayDiary\Types as AttendanceType;
use App\Services\Years;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

class MonthlyOverviewCard extends LivewireController
{
    public $months = [];
    public $years = [];

    #[Url(as: 'month', keep: true)]
    public $selectedMonth = NULL;

    #[Url(as: 'year', keep: true)]
    public $selectedYear = NULL;

    /** Gives the component the worker ID for the data */
    public $workerID = NULL;

    /** Store the attendance data here */
    public $attendance = [];

    /**Attendance type for select */
    public $attTypes = [0 => ''];

    /**Store the save indicator here */
    public $saved = [];

    public function mount()
    {
        $this->attTypes = array_merge($this->attTypes, AttendanceType::getTypes());
        $this->months = Months::MONTHS_HR;
        $this->selectedMonth =  $this->selectedMonth == NULL ? date('n') :  $this->selectedMonth;

        $this->years = Years::getYearsList();
        $this->selectedYear =  $this->selectedYear == NULL ? date('Y') :  $this->selectedYear;

        if ($this->workerID) $this->getAttendanceDataAction();
    }

    /**
     * Run when the month is changed and get the attendance data
     *
     * @return void
     */
    public function updatedSelectedMonth(): void
    {
        $this->getAttendanceDataAction();
    }

    /**
     * Run when the year is changed and get the attendance data
     *
     * @return void
     */
    public function updatedSelectedYear(): void
    {
        $this->getAttendanceDataAction();
    }

    /**
     * Run and fined if the year/month have been changed
     */
    public function updatedAttendance($value, $key)
    {
        $this->saved = [];
        $column = NULL;
        //Error handling for some strange error
        if (is_array($value)) {
            $column = array_keys($value)[0];
            list($dataSet, $attID) = explode('.', $key);
        } else {
            list($dataSet, $attID, $column) = explode('.', $key);
        }

        $service = NULL;
        if ($dataSet == 'per-day') {
            try {
                $service = AttendanceUpdateService::worker($attID);
                switch ($column) {
                    case 'hours':
                        $service->updateWorkHours($value);
                        break;
                    case 'type':
                        $service->updateType($value);
                        break;
                    case 'abs-sl':
                        $service->updateAbsence(AttendanceAbsenceType::setTypeSickLeave());
                        break;
                    case 'abs-pl':
                        $service->updateAbsence(AttendanceAbsenceType::setTypePaidLeave());
                        break;
                    case 'abs-hd':
                        $service->updateAbsence(AttendanceAbsenceType::setTypeHoliday());
                        break;
                }
            } catch (\Throwable $th) {
                return $this->showException($th->getMessage());
            }
        }
        if ($service->getResponseStatus()) {
            $this->getAttendanceDataAction();
            $this->saved['attendance.per-day.' . $attID . '.' . $column] = [];
            $this->dispatch('$refresh');
        }
    }

    /**
     * This will call the service to get the data.
     * Throw error message if there is one.
     * 
     * @return MonthlyOverviewCard
     */
    private function getAttendanceDataAction()
    {
        try {
            $service = (new GetWorkerMonthlyAttendanceService(
                Worker::find($this->workerID),
                $this->selectedMonth,
                $this->selectedYear
            ))->execute();
            $response = $service->getResponse();
            if ($response['success']) {
                $this->attendance = $response['data'];
            } else {
                throw new ErrorMessage($response['message']);
            }
        } catch (\Throwable $th) {
            $this->showException($th->getMessage());
        }
        return $this;
    }

    /**
     * Delete attendance action
     * 
     * @return void
     */
    public function deleteAttendanceAction($id): void
    {
        try {
            $service = (DeleteAttendanceService::byID($id))->execute();
            $response = $service->getResponse();
            if ($response['success']) {
                $this->getAttendanceDataAction();
                $this->notifyMe($response['message']);
            } else {
                throw new ErrorMessage($response['message']);
            }
            $this->updateDOMKey();
        } catch (\Throwable $th) {
            $this->showException($th->getMessage());
        }
        return;
    }

    /**
     * Call the getAttendanceDataAction method, to refresh the data
     * 
     * @return void
     */
    #[On('refresh-attendance-data')]
    public function refreshData(): void
    {
        $this->getAttendanceDataAction();
    }

    /**
     * Dispatch a event to the EditWorkDiaryOnAttendanceModal::class to open the modal
     * 
     * @param $attID the attendance ID
     * @return void 
     */
    public function openEditDiaryModalAction($attID): void
    {
        $this->dispatch('open-edit-work-diary-on-attendance-modal', $attID);
    }

    /**
     * Dispatch a event to the NewAttendanceModal::class to open the modal
     * 
     * @return void 
     */
    public function openNewAttendanceModalAction(): void
    {
        $this->dispatch('open-new-attendance-modal', ['worker_id' => $this->workerID, 'date' => '2026-03-02'])->to(NewAttendanceModal::class);
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.monthly-overview-card');
    }
}
