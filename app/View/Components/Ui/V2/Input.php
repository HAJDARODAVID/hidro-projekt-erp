<?php

namespace App\View\Components\Ui\V2;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Input extends Component
{
    /**Label that will be show over the input element */
    public $label;

    /**Tooltip for that element */
    public $tooltip;

    /**All the class attributes set over the Input::class  */
    public array $class = [];

    /**Inline style attributes set over the Input::class  */
    public array $style = [];

    /**Html input type attribute */
    public $type;

    /**Placeholder text for the input element */
    public $placeholder;

    /**Content prepended before the input element */
    public $prepend;

    /**Content appended after the input element */
    public $append;

    /**Removes the extra x padding on the append addon  */
    public $removeAddOnXP;

    /**Defines the element size */
    public $size;

    /**Fixed width in pixels for the input element */
    public $width;

    /**Livewire model attribute for this element */
    public $model;

    /**Livewire model event that handles the changes */
    public $event;

    /**Array of the saved elements  */
    public array $saved;

    /**Disables the element  */
    public bool $disabled;

    /**Url element  */
    public string|null $url;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $type = 'text',
        $label = NULL,
        $class = [],
        $tooltip = NULL,
        $placeholder = NULL,
        $prepend = NULL,
        $append = NULL,
        $removeAddOnXP = NULL,
        $model = NULL,
        $event = 'blur',
        $size = NULL,
        $width = NULL,
        $saved = [],
        $disabled = false,
        $url = null,
    ) {
        $this->type = $type;
        $this->label = $label;
        $this->class = $class;
        $this->tooltip = $tooltip;
        $this->placeholder = $placeholder;
        $this->prepend = $prepend;
        $this->append = $append;
        $this->removeAddOnXP = $removeAddOnXP === TRUE ? 'px-0' : NULL;
        $this->model = $model;
        $this->event = $event;
        $this->size = $size;
        $this->width = $width;
        $this->saved = $saved;
        $this->disabled = $disabled;
        $this->url = $url;

        $this->addToClass('no-border-radius', 'form-control')
            ->setSize($size)
            ->setWidth($width)
            ->checkSavedStatement($saved);
    }

    /**
     * Add the attributes to the class property
     *
     * @return self
     */
    private function addToClass(...$attributes): self
    {
        $this->class = array_merge($this->class, $attributes);
        return $this;
    }

    /**
     * Set the element size.
     * This works with the bootstrap sizes attributes.
     *
     * @return self
     */
    private function setSize($size)
    {
        switch ($size) {
            case 'sm':
                $this->addToClass('form-control-sm');
                break;
        }
        return $this;
    }

    /**
     * Set a fixed width for the input element
     *
     * @return self
     */
    private function setWidth($value)
    {
        if ($value !== NULL) $this->style[] = 'width: ' . $value . 'px';
        return $this;
    }

    /**
     * This will check if a key exists like the model name
     *
     * @return self
     */
    public function checkSavedStatement($saved)
    {
        if (key_exists($this->model, $saved)) {
            if ($saved[$this->model] == TRUE) $this->addToClass('is-valid');
            if ($saved[$this->model] == FALSE) $this->addToClass('is-invalid');
        }
        return $this;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.v2.input');
    }
}
