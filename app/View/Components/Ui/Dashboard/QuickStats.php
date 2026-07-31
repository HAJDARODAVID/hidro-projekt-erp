<?php

namespace App\View\Components\Ui\Dashboard;

use App\Models\BillModel;
use App\Models\ConstructionSiteModel;
use App\Models\TicketModel;
use App\Models\ToDoList;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class QuickStats extends Component
{
    public $activeConstructionSitesCount;
    public $openTicketsCount;
    public $myTasksCount;
    public $monthlyExpenses;
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->activeConstructionSitesCount = ConstructionSiteModel::where('status', ConstructionSiteModel::CONSTRUCTION_STATUS_ACTIVE)->count();
        $this->openTicketsCount = TicketModel::whereIn('status', [TicketModel::TICKET_STATUS_CREATED, TicketModel::TICKET_STATUS_WIP])->count();
        $this->myTasksCount = ToDoList::where('user_id', Auth::user()->id)->where('status', ToDoList::STATUS_ACTIVE)->count();
        $this->monthlyExpenses = BillModel::whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.dashboard.quick-stats');
    }
}
