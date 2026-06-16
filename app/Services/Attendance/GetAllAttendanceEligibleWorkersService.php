<?php

namespace App\Services\Attendance;

use App\Models\Employees\Worker;
use App\Models\Employees\WorkerStatus;
use App\Models\Employees\WorkerType;
use App\Services\BaseService;

/**
 * Class GetAllAttendanceEligibleWorkersService
 * 
 * Retrieves all workers who are eligible to have attendance records.
 * This includes active and long-sick-leave workers with is_worker flag set to 1.
 */
class GetAllAttendanceEligibleWorkersService extends BaseService
{
    /**
     * Execute the service and retrieve all eligible workers
     * 
     * @return self
     */
    public function execute(): self
    {
        try {
            $workerStatuses = WorkerStatus::init()->areEmployed();
            $workerType = WorkerType::init()->getTypesForAttendance();

            $workers = Worker::whereIn('status', $workerStatuses)
                ->whereIn('type', $workerType)
                ->orderBy('firstName', 'asc')
                ->orderBy('lastName', 'asc')
                ->get();

            if ($workers->isEmpty()) {
                $this->setErrorMessage('No eligible workers found for attendance');
                return $this;
            }

            $this->setSuccessMessage('Eligible workers retrieved successfully', $workers);
            return $this;
        } catch (\Exception $e) {
            $this->setErrorMessage('Error retrieving eligible workers: ' . $e->getMessage());
            return $this;
        }
    }

    /**
     * Get only specific workers by their IDs
     * 
     * @param array $workerIds
     * @return self
     */
    public function executeForSpecificWorkers(array $workerIds): self
    {
        try {
            $workerStatuses = WorkerStatus::init()->areEmployed();
            $workerType = WorkerType::init()->getTypesForAttendance();

            $workers = Worker::whereIn('status', $workerStatuses)
                ->whereIn('type', $workerType)
                ->whereIn('id', $workerIds)
                ->orderBy('firstName', 'asc')
                ->orderBy('lastName', 'asc')
                ->get();

            if ($workers->isEmpty()) {
                $this->setErrorMessage('No eligible workers found with the provided IDs');
                return $this;
            }

            $this->setSuccessMessage('Eligible workers retrieved successfully', $workers);
            return $this;
        } catch (\Exception $e) {
            $this->setErrorMessage('Error retrieving eligible workers: ' . $e->getMessage());
            return $this;
        }
    }

    /**
     * Get workers with additional formatting for selection dropdowns
     * 
     * @return self
     */
    public function executeForDropdown(): self
    {
        try {
            $this->execute();

            if (!$this->getResponseStatus()) {
                return $this;
            }

            $workers = $this->data;
            $formattedWorkers = [];

            foreach ($workers as $worker) {
                $formattedWorkers[$worker->id] = $worker->firstName . ' ' . $worker->lastName;
            }

            $this->setData($formattedWorkers);
            return $this;
        } catch (\Exception $e) {
            $this->setErrorMessage('Error retrieving workers for dropdown: ' . $e->getMessage());
            return $this;
        }
    }
}
