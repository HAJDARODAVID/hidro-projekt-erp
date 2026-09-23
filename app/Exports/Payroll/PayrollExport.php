<?php

namespace App\Exports\Payroll;

use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Support\Responsable;
use App\Exports\Payroll\Sheets\PayrollSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PayrollExport implements WithMultipleSheets, Responsable
{
    use Exportable;

    protected $data;

    protected string $fileName = 'payroll';

    public function __construct($data, $month, $year)
    {
        $this->data = $data;
        $this->fileName = "{$this->fileName}-{$month}-{$year}-" . date('U') . '.xlsx';
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new PayrollSheet($this->data),
        ];
    }
}
