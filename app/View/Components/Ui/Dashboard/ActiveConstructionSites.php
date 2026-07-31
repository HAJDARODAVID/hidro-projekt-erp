<?php

namespace App\View\Components\Ui\Dashboard;

use App\Models\ConstructionSiteModel;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ActiveConstructionSites extends Component
{
    public $activeConstructionSites;
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->activeConstructionSites = ConstructionSiteModel::where('status', ConstructionSiteModel::CONSTRUCTION_STATUS_ACTIVE)
            ->orderByDesc('start_date')
            ->take(5)
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.dashboard.active-construction-sites');
    }
}
