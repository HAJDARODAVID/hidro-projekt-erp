<?php

namespace App\Livewire\Modules\ConstructionSite\Components;

use App\Models\Jobs\ConstructionSite;
use App\Livewire\LivewireController;
use App\Exports\ConstructionSite\ConstructionSiteLogsExport;
use App\Services\ConstructionSite\ConstructionSiteLogsExportDto;
use App\Services\ConstructionSite\GetConstructionSiteLogsService;

class ExportConstructionSiteLogsBtn extends LivewireController
{
    public int $constructionSiteId;

    public function exportLogsAction()
    {
        try {
            $service = (new GetConstructionSiteLogsService((int) $this->constructionSiteId))->execute();
            if (!$service->getResponse()['success']) {
                $this->showException($service->getResponse()['message']);
                return;
            }

            $logs = $service->getResponse()['data'];
            if (empty($logs)) {
                $this->showException(translator('There are no logs for this construction site.'));
                return;
            }

            $constructionSiteName = ConstructionSite::where('id', $this->constructionSiteId)->value('name');

            return (new ConstructionSiteLogsExport(new ConstructionSiteLogsExportDto($logs, ['constructionSiteName' => $constructionSiteName])));
        } catch (\Throwable $th) {
            $this->showException($th->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.modules.construction-site.components.export-construction-site-logs-btn');
    }
}
