<?php

namespace App\Services\Payroll;

use App\Services\Application\ExcelDataMapper;

class PayrollExportDto extends ExcelDataMapper
{
    public function prepare(): void
    {
        $this->padWorkerIDs()
            ->fillWorkHours()
            ->roundAmounts()
            ->removeKeyFromData('status', 'fixRate', 'deductions', 'gross', 'missingPayrollInfo')
            ->reorderByKeys(
                'workerID',
                'name',
                'workHours',
                'hours',
                'paidLeaveDays',
                'sickLeaveDays',
                'holidayDays',
                'homeDays',
                'fieldDays',
                'hourRate',
                'base',
                'homeBonus',
                'fieldBonus',
                'travelExpense',
                'phoneExpense',
                'bonus',
                'net'
            )
            ->setColumnHeaders([
                'workerID'      => 'ID',
                'name'          => 'Worker',
                'workHours'     => 'Work hours',
                'hours'         => 'Base hours',
                'paidLeaveDays' => 'PL',
                'sickLeaveDays' => 'SL',
                'holidayDays'   => 'HD',
                'homeDays'      => 'Home days',
                'fieldDays'     => 'Field days',
                'hourRate'      => 'Hourly rate',
                'base'          => 'Base',
                'homeBonus'     => 'Home bonus',
                'fieldBonus'    => 'Field bonus',
                'travelExpense' => 'Travel expense',
                'phoneExpense'  => 'Phone expense',
                'bonus'         => 'Bonus',
                'net'           => 'Overall',
            ])
            ->setSpecialHeader([
                ['Payroll'],
                ['Month', 'addInfo.month'],
                ['Year', 'addInfo.year'],
            ])
            ->replaceNullWithZero();
    }

    /**
     * Rows saved before the logged work hours were split from the base hours have no workHours,
     * fall back to their hours so every row has the same keys.
     */
    private function fillWorkHours(): self
    {
        foreach ($this->rawData as $workerID => $row) {
            if (!array_key_exists('workHours', $row)) $this->rawData[$workerID]['workHours'] = $row['hours'] ?? 0;
        }
        return $this;
    }

    /**
     * Worker IDs are shown zero padded in the payroll table, keep the export consistent.
     */
    private function padWorkerIDs(): self
    {
        foreach ($this->rawData as $workerID => $row) {
            $this->rawData[$workerID]['workerID'] = str_pad((string) $row['workerID'], 3, '0', STR_PAD_LEFT);
        }
        return $this;
    }

    /**
     * Round the monetary/hour values so floating point noise doesn't leak into the sheet.
     */
    private function roundAmounts(): self
    {
        $fields = ['workHours', 'hours', 'hourRate', 'base', 'homeBonus', 'fieldBonus', 'travelExpense', 'phoneExpense', 'bonus', 'net'];

        foreach ($this->rawData as $workerID => $row) {
            foreach ($fields as $field) {
                $this->rawData[$workerID][$field] = round((float) ($row[$field] ?? 0), 2);
            }
        }
        return $this;
    }
}
