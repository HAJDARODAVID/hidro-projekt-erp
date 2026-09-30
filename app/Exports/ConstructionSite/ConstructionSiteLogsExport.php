<?php

namespace App\Exports\ConstructionSite;

use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\ConstructionSite\Sheets\ConstructionSiteLogsSheet;

class ConstructionSiteLogsExport implements WithMultipleSheets, Responsable
{
    use Exportable;

    /**
     * Data for exporting
     */
    protected $data;

    /**
     * Data for exporting
     * @var string;
     */
    protected string $fileName = 'construction-site-logs';

    public function __construct($data)
    {
        $this->data = $data;
        $this->fileName = $this->fileName . '-' . date('U') . '.xlsx';
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new ConstructionSiteLogsSheet($this->data),
        ];
    }
}
