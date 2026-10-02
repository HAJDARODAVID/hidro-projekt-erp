<?php

namespace App\Services\Subcontractors;

use App\Services\Application\ExcelDataMapper;

/**
 * Maps the detailed list (one row per attendance record) of the subcontractor hours export.
 * Expected addInfo keys: subcontractor, month, year
 */
class SubcontractorHoursListExportDto extends ExcelDataMapper
{
    public function prepare(): void
    {
        $this->setColumnHeaders([
            'date'              => 'Date',
            'id'                => '#',
            'name'              => 'Worker',
            'subcontractor'     => 'Subcontractor',
            'construction-site' => 'Construction site',
            'hours'             => 'Work hours',
        ])
            ->setSpecialHeader([
                ['Subcontractor hours per day'],
                ['Subcontractor', 'addInfo.subcontractor'],
                ['Month', 'addInfo.month'],
                ['Year', 'addInfo.year'],
            ]);
    }
}
