<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TransformerService
{
    /** @var Collection|Model */
    protected $data;

    /** @var array */
    protected $selectOptionsKeyPair = [];

    /** @var array */
    protected $selectionOptionRules = [];

    public function __construct(Collection|Model $data)
    {
        $this->data = $data;
    }

    /**
     * Define the way the option will be created.
     * What will be the value of the option and what will be the displayed name.
     * 
     * @param string $value Defines the value of the option. This will be set as the key of the output array.
     * @param string $name Defines the name that will be shown in the options.
     * @return self
     */
    public function setSelectOptionsKeyPair(string $value, string $name): self
    {
        $this->selectOptionsKeyPair = ['value' => $value, 'name' => $name];
        return $this;
    }

    /**
     * Get a array for the options on a select HTML element.
     * 
     * @return array
     */
    public function getSelectOptions(): array
    {
        $output = [];
        if ($this->isWithNullOptionSet()) {
            $output["0"] = NULL;
        }
        if (is_iterable($this->data)) {
            foreach ($this->data as $item) {
                $output[data_get($item, $this->selectOptionsKeyPair['value'])] = $this->selectOptionName($item);
            }
        } else {
            # code...
        }
        asort($output);
        return $output;
    }

    /**
     * Creates the option name by the given parameters.
     * 
     * @return string 
     */
    private function selectOptionName($item): string
    {
        /**$att will have all the property names */
        $att = preg_split('/&[^&]*&/', $this->selectOptionsKeyPair['name']);

        /**$separators contains all the separators between */
        preg_match_all('/&([^&]*)&/', $this->selectOptionsKeyPair['name'], $matches);
        $separators = array_map(function ($sep) {
            return $sep === '' ? ' ' : $sep;
        }, $matches[1]);

        /**Create the name string */
        $output = "";
        foreach ($att as $key => $property) {
            $output .= data_get($item, $property);
            if (isset($separators[$key])) $output .= $separators[$key];
        }
        return $output;
    }

    /**
     * Adds the rule to add a null option in the options array
     * 
     * @return self
     */
    public function withNullOption(): self
    {
        $this->selectionOptionRules['null-first'] = TRUE;
        return $this;
    }

    /**
     * Checks if the null-first rule on the selectionOptionRules property is set
     * 
     * @return bool
     */
    protected function isWithNullOptionSet(): bool
    {
        return isset($this->selectionOptionRules['null-first']) ? TRUE : FALSE;
    }
}
