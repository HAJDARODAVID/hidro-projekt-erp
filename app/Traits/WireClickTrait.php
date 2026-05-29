<?php

namespace App\Traits;

trait WireClickTrait
{
    /**
     * Define the wire:click method that will be used od your component 
     * @var string
     */
    protected string $wireMethod = '';

    /**
     * Define the wire:click attributes that will be used od your component 
     * @var string
     */
    protected string $wireAttributes = '';

    /**
     * Set the wire method
     * 
     * @param string $methodName The name of the method that will be put in the wire:click
     * @return self  
     */
    protected function setWireMethod(string $methodName)
    {
        $this->wireMethod = $methodName;
        return $this;
    }

    /**
     * Set the wire method attributes
     * 
     * @param string $attributes The attributes of the method
     * @return self  
     */
    protected function setWireAttributes(string $attributes)
    {
        $this->wireAttributes = $attributes;
        return $this;
    }

    /**
     * Return the wire method
     * 
     * @return string
     */
    public function getWireMethod()
    {
        return $this->wireMethod;
    }

    /**
     * Return the wire attributes
     * 
     * @return string
     */
    public function getWireAttributes()
    {
        return $this->wireAttributes;
    }
}
