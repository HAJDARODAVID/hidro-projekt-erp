<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select extends Component
{
    public $label;
    public $wModel;
    public $wModelEvent;
    public $options;
    public $initOption;
    public $tooltip;
    public $saved;
    /**
     * Create a new component instance.
     */
    public function __construct(
        array $options,
        $label = NULL,
        $wModel = NULL,
        $wModelEvent = 'change',
        $initOption = NULL,
        $tooltip = NULL,
        $saved = [],
    ) {
        $this->options = $options;
        $this->label = $label;
        $this->wModel = $wModel;
        $this->wModelEvent = $wModelEvent;
        $this->initOption = $initOption;
        $this->tooltip = $tooltip;

        $this->savedStatement($saved);
    }

    private function savedStatement($saved)
    {
        if (isset($saved[$this->wModel])) {
            if ($saved[$this->wModel] == TRUE) return $this->saved = 1;
            if ($saved[$this->wModel] == FALSE) return $this->saved = 2;
        }
        return $this->saved = NULL;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.select');
    }
}
