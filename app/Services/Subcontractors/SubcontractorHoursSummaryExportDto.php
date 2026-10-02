<?php

namespace App\Services\Subcontractors;

use App\Services\Application\ExcelDataMapper;

/**
 * Maps the summary (one row per worker) of the subcontractor hours export.
 * Expected addInfo keys: subcontractor, month, year
 */
class SubcontractorHoursSummaryExportDto extends ExcelDataMapper
{
    public function prepare(): void
    {
        $this->setColumnHeaders([
            'id'            => '#',
            'name'          => 'Worker',
            'subcontractor' => 'Subcontractor',
            'hours'         => 'Work hours',
            'cost'          => 'Cost',
        ])
            ->setSpecialHeader([
                ['Subcontractor hours summary'],
                ['Subcontractor', 'addInfo.subcontractor'],
                ['Month', 'addInfo.month'],
                ['Year', 'addInfo.year'],
            ]);
    }
}
