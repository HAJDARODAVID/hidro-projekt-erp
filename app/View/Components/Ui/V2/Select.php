<?php

namespace App\View\Components\Ui\V2;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select extends Component
{
    /**Label that will be show over the select element */
    public $label;

    /**Tooltip for that element */
    public $tooltip;

    /**All the class attributes set over the Select::class  */
    public array $class = [];

    /**All the select option in the select element */
    public array $options;

    /**Defines the element size */
    public $size;

    /**Livewire model attribute for this element */
    public $model;

    /**Livewire model event that handles the changes */
    public $event;

    /**This will be used if a initial option is set */
    public $initOpt;

    /**Array of the saved elements  */
    public array $saved;

    /**
     * Create a new component instance.
     */
    public function __construct(
        array $options,
        $label = NULL,
        $class = [],
        $tooltip = NULL,
        $model = NULL,
        $event = 'change',
        $initOpt = NULL,
        $size = NULL,
        $saved = [],
    ) {
        $this->options = $options;
        $this->label = $label;
        $this->class  = $class;
        $this->tooltip = $tooltip;
        $this->model = $model;
        $this->event = $event;
        $this->size = $size;
        $this->saved = $saved;

        $this->addToClass('no-border-radius', 'form-select')
            ->setSize($size)
            ->setInitOption($initOpt)
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
     * Set the initial option if given.
     * This will check if there is a init-option set in the options array.
     * 
     * @return self
     */
    private function setInitOption($initOpt)
    {
        $initOutput = NULL;
        if (array_key_exists('init-option', $this->options)) {
            $initOutput = $this->options['init-option'];
            unset($this->options['init-option']);
        }
        if ($initOpt !== NULL) $initOutput = $initOpt;

        $this->initOpt = $initOutput;
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
                $this->addToClass('form-select-sm');
                break;
        }
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
        return view('components.ui.v2.select');
    }
}
