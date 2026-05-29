<?php

namespace App\Services\WorkdayDiary;

use Illuminate\Database\Eloquent\Collection;
use App\Models\WorkDiary\WorkDiary;
use App\Services\BaseService;
use DateTime;

/**
 * Class GetAllWorkDiariesForDateService.
 */
class GetAllWorkDiariesForDateService extends BaseService
{
    private DateTime $date;

    /**
     * @var Collection|null
     */
    private $workDayDiaries = null;

    protected $with = [];

    public function __construct(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Execute the service.
     * This will get all the work diaries.
     * 
     * @return GetAllWorkDiariesForDateService
     */
    public function execute(): self
    {
        try {
            $this->workDayDiaries = new WorkDiary();
            if ($this->with) $this->workDayDiaries = $this->workDayDiaries->with($this->with);
            $this->workDayDiaries = $this->workDayDiaries->where('date', $this->date->format('Y-m-d'))->get('*');
            $this->setSuccessTrue();
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }

    /**
     * This will add the with() to the model.
     * 
     * @return self
     */
    public function with(...$with): self
    {
        $this->with = $with;
        return $this;
    }

    /**
     * Return the whole collection of the work diaries
     *
     * @return Collection|null
     */
    public function getWorkDayDiaries(): ?Collection
    {
        return $this->workDayDiaries;
    }
}
