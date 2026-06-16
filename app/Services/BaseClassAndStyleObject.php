<?php

namespace App\Services;

/**
 * BaseClassAndStyleObject
 * Provides common functionality for handling the classes and style of components.
 */
class BaseClassAndStyleObject
{

    /**
     * @var array All the classes that will be used in a btn instance 
     */
    protected array $class = [];

    /**
     * @var array All the styles that will be used in a btn instance 
     */
    protected array $style = [];

    /**
     * Add a background-color style attribute.
     * 
     * @return string
     */
    public function addBackgroundColorStyle(?string $value)
    {
        if ($value) $this->style['background-color'] = $value;
        return $this;
    }

    /**
     * Return the string of the classes set this btn
     * 
     * @return string
     */
    public function getClass()
    {
        return implode(" ", $this->class);
    }

    /**
     * Return the string of the styles set this btn
     * 
     * @return string
     */
    public function getStyle()
    {
        $output = [];
        foreach ($this->style as $styleKey => $param) {
            $output[] = $styleKey . ": " . $param;
        }
        return implode("; ", $output);
    }

    /**
     * Return the value of a specific style element
     * 
     * @param string $element Css style element
     * @return string
     */
    public function getStyleElement(string $element)
    {
        return $this->style[$element] ?? null;
    }
}
