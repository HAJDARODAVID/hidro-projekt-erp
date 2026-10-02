<?php

namespace App\Exports\Subcontractors;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Support\Responsable;
use App\Services\Application\ExcelDataMapper;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Subcontractors\Sheets\SubcontractorHoursListSheet;
use App\Exports\Subcontractors\Sheets\SubcontractorHoursSummarySheet;

/**
 * Excel export of the subcontractor work hours for one month.
 * Sheet 1: summary per worker, sheet 2: every attendance record per day.
 */
class SubcontractorHoursExport implements WithMultipleSheets, Responsable
{
    use Exportable;

    /**
     * Name of the downloaded file
     * @var string;
     */
    protected string $fileName;

    public function __construct(
        protected ExcelDataMapper $summary,
        protected ExcelDataMapper $list,
        string $name,
        int $month,
        int $year,
    ) {
        $this->fileName = Str::slug($name . ' subcontractor hours ' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT)) . '.xlsx';
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new SubcontractorHoursSummarySheet($this->summary),
            new SubcontractorHoursListSheet($this->list),
        ];
    }
}
