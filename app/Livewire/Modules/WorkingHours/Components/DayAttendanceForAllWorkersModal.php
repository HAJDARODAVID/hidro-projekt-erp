<?php

namespace App\Livewire\Modules\WorkingHours\Components;

use DateTime;
use App\Livewire\LivewireController;
use App\Models\Employees\AttendanceAbsenceType;
use App\Services\Attendance\GetAttendanceService;
use App\Services\Attendance\DeleteAttendanceService;
use App\Services\Attendance\MassAbsenceAssignmentService;
use App\Livewire\Modules\WorkingHours\Index as AttendanceReport;

class DayAttendanceForAllWorkersModal extends LivewireController
{
    /**Params passed in from the global modal (see config/global-modal.php) */
    public array $params = [];

    public $date;
    public $workers;

    public $absenceType = [];

    public $showDeleteAtt = FALSE;

    /**
     * The global modal mounts a fresh instance of this component every time
     * it's opened, so this is where the day/workers data is loaded for the
     * date that was passed in as a param.
     */
    public function mount()
    {
        try {
            $this->date = $this->params['date'] ?? null;
            $this->workers = $this->params['workers'] ?? [];
            $this->setAbsenceTypeProperty();
            $this->showDeleteAtt = GetAttendanceService::byDate((new DateTime())->setTimestamp($this->date))->myEmployees()->countAtt() > 0 ? TRUE : FALSE;
        } catch (\Throwable $th) {
            $this->showException($th->getMessage());
        }
    }

    /**
     * Set all the data needed for displaying absence types.
     * 
     * @return void
     */
    private function setAbsenceTypeProperty(): void
    {
        $output = [];
        foreach (AttendanceAbsenceType::init()->getMassAssignable() as $type) {
            $type = AttendanceAbsenceType::setByType($type);
            $output[$type->code()] = [
                'description' => $type->description(),
                'short-text' => $type->shortDesc(),
            ];
        }
        $this->absenceType = $output;
    }

    /**
     * Close the global modal without modifying its shared component.
     * The global modal already closes on Escape (see global-modal.blade.php),
     * so a synthetic Escape keypress reuses that existing listener.
     *
     * @return void
     */
    private function closeGlobalModal(): void
    {
        $this->js("window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))");
    }

    /**
     * Btn click action that will run the MassAbsenceAssignment service.
     * When used, this will add a absence type to all workers in the report.
     * 
     * @param $typeCode Provided by the btn, defines the type code for the absence.
     */
    public function applyAbsenceAction($typeCode)
    {
        $service = NULL;
        try {
            $dateTimeObject = (new DateTime())->setTimestamp($this->date);
            $service = (new MassAbsenceAssignmentService)
                ->execute(
                    $dateTimeObject,
                    /**TODO: create some kind of DTO for workers */
                    $this->workers,
                    AttendanceAbsenceType::setByType($typeCode)
                )
                ->getResponse();
        } catch (\Throwable $th) {
            return $this->dispatch('show-exception-modal', $th->getMessage());
        }

        $this->closeGlobalModal();
        $this->dispatch('refresh-attendance-report')->to(AttendanceReport::class);
        return $service['message'] != NULL ? $this->notifyMe($service['message'], $service['success'] ? 'success' : 'danger') : NULL;
    }

    /**
     * Btn click action that will run the DeleteAttendanceService service.
     * This will delete all the attendance for this day.
     */
    public function deleteAllAttendanceAction()
    {
        $service = NULL;
        try {
            $service = new DeleteAttendanceService(GetAttendanceService::byDate((new DateTime())->setTimestamp($this->date))->myEmployees()->getAtt());
            $service = $service->execute()->getResponse();
        } catch (\Throwable $th) {
            return $this->dispatch('show-exception-modal', $th->getMessage());
        }

        $this->closeGlobalModal();
        $this->dispatch('refresh-attendance-report')->to(AttendanceReport::class);
        return $service['message'] != NULL ? $this->notifyMe($service['message'], $service['success'] ? 'success' : 'danger') : NULL;
    }

    public function render()
    {
        return view('livewire.modules.working-hours.components.day-attendance-for-all-workers-modal');
    }
}
