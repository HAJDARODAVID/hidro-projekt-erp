<?php

namespace App\Services\ConstructionSite;

use App\Services\Application\ExcelDataMapper;

class ConstructionSiteLogsExportDto extends ExcelDataMapper
{
    public function prepare(): void
    {
        //Normalize the textarea line breaks, so Excel shows them as new lines in the cell
        foreach ($this->rawData as $key => $item) {
            $this->rawData[$key]['log'] = str_replace(["\r\n", "\r"], "\n", $item['log'] ?? '');
        }

        $this->setColumnHeaders([
            "date"                  => "Date",
            "working_day_record_id" => "Work diary #",
            "worker"                => "Worker",
            "log"                   => "Log",
        ])->setSpecialHeader([
            ['Construction site', 'addInfo.constructionSiteName'],
        ]);
    }
}
