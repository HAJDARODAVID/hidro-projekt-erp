<?php

namespace App\Services\Employees;

use App\Services\BaseDTO;
use App\Models\Employees\Worker;

/**
 * Class WorkerInfoDto.
 * Holds the basic info of one worker (name, OIB, employment dates, workplace, status, type).
 */
class WorkerInfoDto extends BaseDTO
{
    /**Worker ID */
    protected $workerID;

    protected $firstName = NULL;

    protected $lastName = NULL;

    protected $oib = NULL;

    protected $company = NULL;

    /**Workplace name (working_place column) */
    protected $workplace = NULL;

    /**Date of employment */
    protected $doe = NULL;

    /**Contract end date */
    protected $ced = NULL;

    protected $comment = NULL;

    protected $printLabel = NULL;

    /**WorkerStatus::WORKER_STATUS_* */
    protected $status = NULL;

    /**WorkerType::TYPE_* */
    protected $type = NULL;

    protected $isWorker = NULL;

    /**
     * Create a new instance from the worker model.
     *
     * @param Worker $model
     * @return self
     */
    public static function fromModel(Worker $model): self
    {
        return (new self())
            ->setWorkerID($model->id)
            ->setFirstName($model->firstName)
            ->setLastName($model->lastName)
            ->setOib($model->OIB)
            ->setCompany($model->company)
            ->setWorkplace($model->working_place)
            ->setDoe($model->doe)
            ->setCed($model->ced)
            ->setComment($model->comment)
            ->setPrintLabel($model->print_label)
            ->setStatus($model->status !== NULL ? (int) $model->status : NULL)
            ->setType($model->type)
            ->setIsWorker($model->is_worker);
    }

    /**
     * First and last name.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
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
     * Get the value of firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set the value of firstName
     *
     * @return  self
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get the value of lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set the value of lastName
     *
     * @return  self
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get the value of oib
     */
    public function getOib()
    {
        return $this->oib;
    }

    /**
     * Set the value of oib
     *
     * @return  self
     */
    public function setOib($oib)
    {
        $this->oib = $oib;

        return $this;
    }

    /**
     * Get the value of company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set the value of company
     *
     * @return  self
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get the value of workplace
     */
    public function getWorkplace()
    {
        return $this->workplace;
    }

    /**
     * Set the value of workplace
     *
     * @return  self
     */
    public function setWorkplace($workplace)
    {
        $this->workplace = $workplace;

        return $this;
    }

    /**
     * Get the value of doe
     */
    public function getDoe()
    {
        return $this->doe;
    }

    /**
     * Set the value of doe
     *
     * @return  self
     */
    public function setDoe($doe)
    {
        $this->doe = $doe;

        return $this;
    }

    /**
     * Get the value of ced
     */
    public function getCed()
    {
        return $this->ced;
    }

    /**
     * Set the value of ced
     *
     * @return  self
     */
    public function setCed($ced)
    {
        $this->ced = $ced;

        return $this;
    }

    /**
     * Get the value of comment
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set the value of comment
     *
     * @return  self
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get the value of printLabel
     */
    public function getPrintLabel()
    {
        return $this->printLabel;
    }

    /**
     * Set the value of printLabel
     *
     * @return  self
     */
    public function setPrintLabel($printLabel)
    {
        $this->printLabel = $printLabel;

        return $this;
    }

    /**
     * Get the value of status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the value of status
     *
     * @return  self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the value of isWorker
     */
    public function getIsWorker()
    {
        return $this->isWorker;
    }

    /**
     * Set the value of isWorker
     *
     * @return  self
     */
    public function setIsWorker($isWorker)
    {
        $this->isWorker = $isWorker;

        return $this;
    }
}
