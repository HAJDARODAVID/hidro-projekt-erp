<?php

namespace App\Services\Payroll;

use App\Models\Payroll\Items;
use App\Services\BaseDTO;

/**
 * Class PayrollEditableValuesDto.
 * The payroll values of one worker that can be changed on the payroll itself
 * (hourly rate, travel expense, phone expense, bonus amount).
 * A NULL property means "not changed, use the value from the payroll info / calculation".
 */
class PayrollEditableValuesDto extends BaseDTO
{
    /**Keys of the payroll item data that hold the editable values */
    const ITEM_KEYS = [
        'hourRate'      => 'hourRate',
        'travelExpense' => 'travelExpense',
        'phoneExpense'  => 'phoneExpense',
        'bonus'         => 'bonus',
    ];

    /**Hourly rate [€/h] */
    protected $hourRate = NULL;

    /**Travel expense [€] */
    protected $travelExpense = NULL;

    /**Phone expense [€] */
    protected $phoneExpense = NULL;

    /**Bonus amount [€] */
    protected $bonus = NULL;

    /**
     * Create a new instance from the payroll item data.
     * Keys missing in the item (e.g. items saved by the legacy payroll) stay NULL.
     *
     * @param Items $item
     * @return self
     */
    public static function fromPayrollItem(Items $item): self
    {
        $data = $item->payroll_data ?? [];
        $dto  = new self();
        foreach (self::ITEM_KEYS as $key => $property) {
            if (isset($data[$key]) && is_numeric($data[$key])) $dto->{$property} = (float) $data[$key];
        }
        return $dto;
    }

    /**
     * Get the value of hourRate
     */
    public function getHourRate(): ?float
    {
        return $this->hourRate;
    }

    /**
     * Set the value of hourRate
     *
     * @return  self
     */
    public function setHourRate(?float $hourRate): self
    {
        $this->hourRate = $hourRate;

        return $this;
    }

    /**
     * Get the value of travelExpense
     */
    public function getTravelExpense(): ?float
    {
        return $this->travelExpense;
    }

    /**
     * Set the value of travelExpense
     *
     * @return  self
     */
    public function setTravelExpense(?float $travelExpense): self
    {
        $this->travelExpense = $travelExpense;

        return $this;
    }

    /**
     * Get the value of phoneExpense
     */
    public function getPhoneExpense(): ?float
    {
        return $this->phoneExpense;
    }

    /**
     * Set the value of phoneExpense
     *
     * @return  self
     */
    public function setPhoneExpense(?float $phoneExpense): self
    {
        $this->phoneExpense = $phoneExpense;

        return $this;
    }

    /**
     * Get the value of bonus
     */
    public function getBonus(): ?float
    {
        return $this->bonus;
    }

    /**
     * Set the value of bonus
     *
     * @return  self
     */
    public function setBonus(?float $bonus): self
    {
        $this->bonus = $bonus;

        return $this;
    }
}
