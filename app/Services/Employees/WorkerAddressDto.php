<?php

namespace App\Services\Employees;

use App\Services\BaseDTO;
use App\Models\Employees\WorkerAddress;

/**
 * Class WorkerAddressDto.
 * Holds the address of one worker (street, town, zip, county).
 */
class WorkerAddressDto extends BaseDTO
{
    /**Worker ID */
    protected $workerID;

    protected $street = NULL;

    protected $town = NULL;

    protected $zip = NULL;

    protected $county = NULL;

    /**TRUE when an address record exists for the worker */
    protected $hasAddress = FALSE;

    /**
     * Create a new instance from the worker address model.
     *
     * @param WorkerAddress $model
     * @return self
     */
    public static function fromModel(WorkerAddress $model): self
    {
        return (new self())
            ->setWorkerID($model->worker_id)
            ->setStreet($model->street)
            ->setTown($model->town)
            ->setZip($model->zip)
            ->setCounty($model->county)
            ->setHasAddress(TRUE);
    }

    /**
     * Get the value of workerID
     */
    public function getWorkerID()
    {
        return $this->workerID;
    }

    /**
     * Set the value of workerID
     *
     * @return  self
     */
    public function setWorkerID($workerID)
    {
        $this->workerID = $workerID;

        return $this;
    }

    /**
     * Get the value of street
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set the value of street
     *
     * @return  self
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get the value of town
     */
    public function getTown()
    {
        return $this->town;
    }

    /**
     * Set the value of town
     *
     * @return  self
     */
    public function setTown($town)
    {
        $this->town = $town;

        return $this;
    }

    /**
     * Get the value of zip
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set the value of zip
     *
     * @return  self
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get the value of county
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Set the value of county
     *
     * @return  self
     */
    public function setCounty($county)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * Get the value of hasAddress
     */
    public function getHasAddress()
    {
        return $this->hasAddress;
    }

    /**
     * Set the value of hasAddress
     *
     * @return  self
     */
    public function setHasAddress($hasAddress)
    {
        $this->hasAddress = $hasAddress;

        return $this;
    }
}
