<?php

namespace App\Services\Employees;

use App\Services\BaseDTO;

/**
 * Class WorkerFullInfoDto.
 * Holds all the info of one worker: the basic info, the address and the contact info.
 */
class WorkerFullInfoDto extends BaseDTO
{
    /** @var WorkerInfoDto */
    protected $info;

    /** @var WorkerAddressDto */
    protected $address;

    /** @var WorkerContactDto */
    protected $contact;

    /**
     * All the properties in a array, the nested DTOs as arrays too.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'info'    => $this->info?->toArray(),
            'address' => $this->address?->toArray(),
            'contact' => $this->contact?->toArray(),
        ];
    }

    /**
     * Get the value of info
     *
     * @return WorkerInfoDto
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Set the value of info
     *
     * @return  self
     */
    public function setInfo(WorkerInfoDto $info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * Get the value of address
     *
     * @return WorkerAddressDto
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set the value of address
     *
     * @return  self
     */
    public function setAddress(WorkerAddressDto $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get the value of contact
     *
     * @return WorkerContactDto
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set the value of contact
     *
     * @return  self
     */
    public function setContact(WorkerContactDto $contact)
    {
        $this->contact = $contact;

        return $this;
    }
}
