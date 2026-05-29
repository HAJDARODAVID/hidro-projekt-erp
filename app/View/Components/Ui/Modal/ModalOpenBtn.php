<?php

namespace App\View\Components\Ui\Modal;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ModalOpenBtn extends Component
{
    public bool $displayIcon;
    public string $btnType;
    public string $icon;
    /**
     * Create a new component instance.
     */
    public function __construct($show = TRUE, $btnType = 'suc.sm', $icon = 'plus-square')
    {
        $this->displayIcon = $show;
        $this->btnType = $btnType;
        $this->icon = $icon;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.modal.modal-open-btn');
    }
}
