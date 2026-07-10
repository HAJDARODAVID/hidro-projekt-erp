<?php

namespace App\Livewire\Components\Helpers;

use App\Exceptions\MissingMethodException;
use Exception;
use Livewire\Component;

class Calculator extends Component
{
    public $data = [];

    public function updatedData($value, $key)
    {
        if (strpos($key, '.')) {
            list($key, $property) = explode('.', $key);
        }
        try {
            if (!method_exists(get_class($this), 'set' . ucfirst($key))) {
                throw new MissingMethodException('set' . ucfirst($key));
            }
            $method = 'set' . ucfirst($key);
            $this->$method();
        } catch (Exception $e) {
            return $this->dispatch('show-exception-modal', $e->getMessage());
        }
    }

    private function setPercentageOfAmount()
    {
        $arrayKey = 'percentageOfAmount';
        if (isset($this->data[$arrayKey]['percentage']) && isset($this->data[$arrayKey]['amount'])) {
            if (!$this->data[$arrayKey]['percentage'] || !$this->data[$arrayKey]['amount']) {
                return $this->data[$arrayKey]['result'] = null;
            }
            return $this->data[$arrayKey]['result'] = $this->data[$arrayKey]['amount'] * ($this->data[$arrayKey]['percentage'] / 100);
        }
        return $this->data[$arrayKey]['result'] = null;
    }

    private function setPercentageFromAmount()
    {
        $arrayKey = 'percentageFromAmount';
        if (isset($this->data[$arrayKey]['amountI']) && isset($this->data[$arrayKey]['amountII'])) {
            if (!$this->data[$arrayKey]['amountI'] || !$this->data[$arrayKey]['amountII']) {
                return $this->data[$arrayKey]['result'] = null;
            }
            return $this->data[$arrayKey]['result'] = ($this->data[$arrayKey]['amountI'] / $this->data[$arrayKey]['amountII']) * 100;
        }
        return $this->data[$arrayKey]['result'] = null;
    }

    private function setAmountFromPercentage()
    {
        $arrayKey = 'amountFromPercentage';
        if (isset($this->data[$arrayKey]['amount']) && isset($this->data[$arrayKey]['percentage'])) {
            if (!$this->data[$arrayKey]['amount'] || !$this->data[$arrayKey]['percentage']) {
                return $this->data[$arrayKey]['result'] = null;
            }
            return $this->data[$arrayKey]['result'] = $this->data[$arrayKey]['amount'] / ($this->data[$arrayKey]['percentage'] / 100);
        }
        return $this->data[$arrayKey]['result'] = null;
    }

    private function setEnlargeAmountForPercentage()
    {
        $arrayKey = 'enlargeAmountForPercentage';
        if (isset($this->data[$arrayKey]['amount']) && isset($this->data[$arrayKey]['percentage'])) {
            if (!$this->data[$arrayKey]['amount'] || !$this->data[$arrayKey]['percentage']) {
                return $this->data[$arrayKey]['result'] = null;
            }
            return $this->data[$arrayKey]['result'] = $this->data[$arrayKey]['amount'] * (1 + ($this->data[$arrayKey]['percentage'] / 100));
        }
        return $this->data[$arrayKey]['result'] = null;
    }

    private function setDecreaseAmountForPercentage()
    {
        $arrayKey = 'decreaseAmountForPercentage';
        if (isset($this->data[$arrayKey]['amount']) && isset($this->data[$arrayKey]['percentage'])) {
            if (!$this->data[$arrayKey]['amount'] || !$this->data[$arrayKey]['percentage']) {
                return $this->data[$arrayKey]['result'] = null;
            }
            return $this->data[$arrayKey]['result'] = $this->data[$arrayKey]['amount'] * (1 - ($this->data[$arrayKey]['percentage'] / 100));
        }
        return $this->data[$arrayKey]['result'] = null;
    }

    public function render()
    {
        return view('livewire.components.helpers.calculator');
    }
}
