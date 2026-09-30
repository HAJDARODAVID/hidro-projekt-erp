<?php

namespace App\Exports\ConstructionSite\Sheets;

use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Services\Application\ExcelArrayExporterService;

class ConstructionSiteLogsSheet extends ExcelArrayExporterService implements WithStyles, WithColumnWidths
{
    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 14,
            'B' => 14,
            'C' => 25,
            'D' => 80,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        //Row 1 is the construction site header, row 2 is empty and row 3 are the column headers
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('3')->getFont()->setBold(true);

        //Align all cells to the top, so multi-line logs are easier to read
        $sheet->getStyle('A:D')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        //Wrap the log column so the new lines from the textarea are shown
        $sheet->getStyle('D')->getAlignment()->setWrapText(true);
    }
}
