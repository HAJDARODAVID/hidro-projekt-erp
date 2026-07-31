<?php

namespace App\View\Components\Ui\Dashboard;

use App\Models\ToDoList;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class MyTasks extends Component
{
    public $myTasks;
    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->myTasks = ToDoList::where('user_id', Auth::user()->id)
            ->where('status', ToDoList::STATUS_ACTIVE)
            ->orderByDesc('priority')
            ->take(5)
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.dashboard.my-tasks');
    }
}
