<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Input extends Component
{
    public $type;
    public $label;
    public $placeholder;
    public $wModel;
    public $wModelEvent;
    public $prepend;
    public $append;
    public $inputGroupSize;
    public $removeAddOnXP;
    public $width;
    public $tooltip;
    public $style = [];
    public $disabled;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $type = 'text',
        $label = NULL,
        $placeholder = NULL,
        $wModel = NULL,
        $wModelEvent = 'blur',
        $prepend = NULL,
        $append = NULL,
        $size = NULL,
        $removeAddOnXP = NULL,
        $width = NULL,
        $tooltip = NULL,
        $disabled = false,
    ) {
        $this->type = $type;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->wModel = $wModel;
        $this->wModelEvent = $wModelEvent;
        $this->prepend = $prepend;
        $this->append = $append;
        $this->inputGroupSize = $size == NULL ? NULL : $this->setInputGroupSize($size);
        $this->removeAddOnXP = $removeAddOnXP === TRUE ? 'px-0' : NULL;
        $this->tooltip = $tooltip;
        $this->disabled = $disabled;

        $this->noBorderRadius()
            ->width($width);
    }

    private function setInputGroupSize($size)
    {
        $availableSizes = [
            'sm' => 'input-group-sm',
            'lg' => 'input-group-lg'
        ];
        if (key_exists($size, $availableSizes)) return $availableSizes[$size];
        return NULL;
    }

    private function noBorderRadius()
    {
        $this->style[] = 'border-radius: 0px';
        return $this;
    }

    private function width($value)
    {
        if ($value !== NULL) $this->style[] = 'width: ' . $value . 'px';
        return $this;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.input');
    }
}
