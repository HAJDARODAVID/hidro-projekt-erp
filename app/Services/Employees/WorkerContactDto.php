<?php

namespace App\Services\Employees;

use App\Services\BaseDTO;
use App\Models\Employees\WorkerContact;

/**
 * Class WorkerContactDto.
 * Holds the contact info of one worker (mobile phone, email).
 */
class WorkerContactDto extends BaseDTO
{
    /**Worker ID */
    protected $workerID;

    /**Mobile phone number */
    protected $mob = NULL;

    protected $email = NULL;

    /**TRUE when a contact record exists for the worker */
    protected $hasContact = FALSE;

    /**
     * Create a new instance from the worker contact model.
     *
     * @param WorkerContact $model
     * @return self
     */
    public static function fromModel(WorkerContact $model): self
    {
        return (new self())
            ->setWorkerID($model->worker_id)
            ->setMob($model->mob)
            ->setEmail($model->email)
            ->setHasContact(TRUE);
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
     * Get the value of mob
     */
    public function getMob()
    {
        return $this->mob;
    }

    /**
     * Set the value of mob
     *
     * @return  self
     */
    public function setMob($mob)
    {
        $this->mob = $mob;

        return $this;
    }

    /**
     * Get the value of email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of email
     *
     * @return  self
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get the value of hasContact
     */
    public function getHasContact()
    {
        return $this->hasContact;
    }

    /**
     * Set the value of hasContact
     *
     * @return  self
     */
    public function setHasContact($hasContact)
    {
        $this->hasContact = $hasContact;

        return $this;
    }
}
