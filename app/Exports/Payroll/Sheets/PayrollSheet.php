<?php

namespace App\Exports\Payroll\Sheets;

use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Services\Application\ExcelArrayExporterService;

class PayrollSheet extends ExcelArrayExporterService implements ShouldAutoSize, WithStyles
{
    /**
     * Columns from hours (C) onward, centered.
     */
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('C:P')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        /**hourRate (I) through net (P) are money/rate values, force two decimals */
        $sheet->getStyle('I:P')->getNumberFormat()->setFormatCode('0.00');
        return;
    }
}
