<?php

namespace App\Services\Employees;

use App\Models\Employees\Worker;

/**
 * Class GetWorkerInfo.
 */
class GetWorkerInfo
{

    /**Worker ID */
    private $workerID = NULL;

    /**
     * Worker info model
     * 
     * @var Worker
     */
    private $workerInfo = NULL;

    public function __construct($workerID)
    {
        $this->workerInfo = Worker::with('getWorkerAddress', 'getWorkerContact')->find($workerID);
    }

    /**
     * Returns a array from the model
     * 
     * @return array
     */
    public function toArray()
    {
        $output = [];
        if ($this->workerInfo) {
            $output = [
                'name'      => $this->workerInfo->firstName,
                'surname'   => $this->workerInfo->lastName,
                'oib'       => $this->workerInfo->OIB,
                'phone'     => $this->workerInfo->getWorkerContact->mob ?? NULL,
                'email'     => $this->workerInfo->getWorkerContact->email ?? NULL,
                'doe'       => $this->workerInfo->doe,
                'ced'       => $this->workerInfo->ced,
                'workplace' => $this->workerInfo->working_place,
                'status'    => $this->workerInfo->statusDescriptionCro,
                'street'    => $this->workerInfo->getWorkerAddress->street ?? NULL,
                'town'      => $this->workerInfo->getWorkerAddress->town ?? NULL,
                'zip'       => $this->workerInfo->getWorkerAddress->zip ?? NULL,
                'county'    => $this->workerInfo->getWorkerAddress->county ?? NULL,
                'comment'    => $this->workerInfo->comment ?? NULL,
            ];
        }
        return $output;
    }
}
