<?php

namespace App\Traits;

/**
 * Trait used in the LivewireCOntroller for added a saved flag on the properties.
 * This will, in combination with the right components, add the classes for successful or error save 
 */
trait SavedStatementTrait
{
    /**
     * Key value pair for the save statement, where the key = property
     * @var array
     */
    public $saved = [];

    /**
     * Add the property TRUE save statement to the array.
     * 
     * @param $property The property for which the statement should be TRUE.
     * @return self 
     */
    public function savedSuccess(...$properties)
    {
        foreach ($properties as $property) {
            $this->saved[$property] = TRUE;
        }
        return $this;
    }

    /**
     * Add the property FALSE save statement to the array.
     * 
     * @param $property The property for which the statement should be FALSE.
     * @return self 
     */
    public function savedError(...$properties)
    {
        foreach ($properties as $property) {
            $this->saved[$property] = TRUE;
        }
        return $this;
    }

    /**
     * Reset the saved array.
     * 
     * @return self 
     */
    public function resetSaveStatement()
    {
        $this->saved = [];
        return $this;
    }
}
