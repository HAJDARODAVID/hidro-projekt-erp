<?php

namespace App\Services\Application;

use App\Services\BaseService;
use DateTime;

/**
 * Class CalendarService.
 */
class CalendarService extends BaseService
{
    private ?int $month;

    private ?int $year;

    public function __construct(?int $month = null, ?int $year = null)
    {
        $this->month = $month;
        $this->year = $year;

        $this->execute();
    }

    /**
     * Create a new CalendarService instance by passing the DateTime
     * 
     * @param \DateTime $date
     * @return CalendarService
     */
    public static function date(\DateTime $date)
    {
        return new self($date->format('n'), $date->format('Y'));
    }

    /**
     * Execute this service.
     * This will run in the constructor.
     * 
     * @return CalendarService
     */
    public function execute()
    {
        try {
            if ($this->month == null || $this->year == null) {
                $now = now();
                if ($this->month == null) $this->month = $now->format('n');
                if ($this->year == null) $this->year = $now->format('Y');
            }
            $start = new DateTime("$this->year-$this->month-01");
            $end = (clone $start)->modify('last day of this month');
            $start->modify('-' . ($start->format('N') - 1) . ' day');
            $end->modify('+' . (7 - $end->format('N')) . ' day');
            $output = [];
            while ($start <= $end) {
                $output[$start->format('W')][] = [
                    'day' => $start->format('j'),
                    'month' => $start->format('n'),
                    'today' => $start->format('Y-m-d') === now()->format('Y-m-d') ? TRUE : FALSE,
                ];
                $start->modify('+1 day');
            }
            $this->setData($output);
        } catch (\Throwable $th) {
            $this->setErrorMessage($th->getMessage());
        }
        return $this;
    }
}
