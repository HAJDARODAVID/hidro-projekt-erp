<?php

namespace App\Services\Payroll;

use App\Exceptions\ErrorMessage;
use App\Models\Payroll\Items;
use App\Models\Payroll\Payroll;
use App\Services\BaseService;

/**
 * Class GetPayrollItemService.
 * Gets one payroll item either by its ID or by payroll + worker.
 * The payroll and the worker are loaded with the item.
 */
class GetPayrollItemService extends BaseService
{
    /** @var int|null */
    protected $itemID = NULL;

    /** @var int|null */
    protected $payrollID = NULL;

    /** @var int|null */
    protected $workerID = NULL;

    /**Relations loaded with the item */
    protected array $with = ['getPayroll', 'getWorker'];

    /**
     * Get the item by its ID.
     *
     * @param int $itemID
     * @return self
     */
    public static function byID(int $itemID): self
    {
        $service = new self();
        $service->itemID = $itemID;
        return $service;
    }

    /**
     * Get the item of one worker on one payroll.
     *
     * @param Payroll|int $payroll The Payroll model or its ID
     * @param int $workerID
     * @return self
     */
    public static function byWorker(Payroll|int $payroll, int $workerID): self
    {
        $service = new self();
        $service->payrollID = $payroll instanceof Payroll ? $payroll->id : $payroll;
        $service->workerID = $workerID;
        return $service;
    }

    /**
     * Override the relations loaded with the item.
     *
     * @param string ...$relations
     * @return self
     */
    public function with(string ...$relations): self
    {
        $this->with = $relations;
        return $this;
    }

    /**
     * Get the item. The data is the Items model.
     *
     * @return self
     */
    public function execute(): self
    {
        try {
            $item = $this->buildQuery()->with($this->with)->first();
            if ($item === NULL) throw new ErrorMessage('Payroll item not found (' . $this->describeLookup() . ').');

            $this->setData($item);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * Shortcut: get the Items model directly, or NULL when it does not exist.
     *
     * @return Items|null
     */
    public function get(): ?Items
    {
        $this->execute();
        return $this->getResponseStatus() ? $this->getResponse()['data'] : NULL;
    }

    /**
     * Build the query for the requested lookup.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     * @throws ErrorMessage
     */
    private function buildQuery()
    {
        if ($this->itemID !== NULL) return Items::where('id', $this->itemID);

        if ($this->payrollID !== NULL && $this->workerID !== NULL) {
            return Items::where('payroll_id', $this->payrollID)->where('worker_id', $this->workerID);
        }

        throw new ErrorMessage('Set the item ID or the payroll and worker to get a payroll item.');
    }

    /**
     * Describe the lookup for the error message.
     *
     * @return string
     */
    private function describeLookup(): string
    {
        return $this->itemID !== NULL
            ? 'ID ' . $this->itemID
            : 'payroll ' . $this->payrollID . ', worker ' . $this->workerID;
    }
}
