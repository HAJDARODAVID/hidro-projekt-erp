<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ListItem extends Component
{
    public $selected;
    public $slotLeft;
    public $slotRight;
    public $wClickMethod = NULL;
    public $wClickParam = NULL;
    public $url = NULL;
    /**
     * Create a new component instance.
     */
    public function __construct(
        $selected = FALSE,
        $slotLeft = NULL,
        $slotRight = NULL,
        $wClickMethod = NULL,
        $wClickParam = NULL,
        $url = NULL,
    ) {
        $this->selected = $selected;
        $this->slotLeft = $slotLeft;
        $this->slotRight = $slotRight;
        $this->wClickMethod = $wClickMethod;
        $this->wClickParam = $wClickParam;
        $this->url = $url;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.list-item');
    }
}
