<?php

namespace App\Exports\Subcontractors\Sheets;

use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Services\Application\ExcelArrayExporterService;

class SubcontractorHoursSummarySheet extends ExcelArrayExporterService implements WithTitle, ShouldAutoSize
{
    public function title(): string
    {
        return translator('Summary');
    }
}
