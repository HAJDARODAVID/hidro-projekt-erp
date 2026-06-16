<?php

namespace App\Livewire;

use App\Traits\ArraySearchTrait;
use App\Traits\ExplodeParams;
use App\Traits\ModalTrait;
use App\Traits\SavedStatementTrait;
use App\Traits\TabTrait;
use App\Traits\ValidationTrait;
use Illuminate\Support\Carbon;
use Livewire\Component;

class LivewireController extends Component
{
    use TabTrait;
    use ModalTrait;
    use ExplodeParams;
    use ValidationTrait;
    use ArraySearchTrait;
    use SavedStatementTrait;

    public ?Carbon $DOMupdateKey = null;

    /**Define all the rules/attributes for this component */
    public array $att = [];

    /**
     * This will dispatch a message to the notifications component. 
     * 
     * @param string $message The message you want to show.
     * @param string $type This will define the class of the notifications component(for colors).
     */
    protected function notifyMe(string $message, string $type = 'success')
    {
        return $this->dispatch('notify', ['message' => $message, 'type' => $type]);
    }

    /**
     * This will dispatch a message to the exception modal component. 
     * 
     * @param string $message The error message you want to show.
     */
    protected function showException(string $message)
    {
        return $this->dispatch('show-exception-modal', $message);
    }

    /**
     * This will dispatch a refresh event to a specific table. 
     * 
     * @param string $tableClass The classes of the Livewire tables to be refreshed.
     */
    protected function refreshTable(...$tableClass)
    {
        foreach ($tableClass as $table) {
            $this->dispatch('refreshDatatable')->to($table);
        }
        //return $this->dispatch('show-exception-modal', $message);
    }

    /**
     * Update the DOMupdateKey property.
     * This will help you to handel the DOM where you change something.
     */
    protected function updateDOMKey()
    {
        $this->DOMupdateKey = now();
    }

    /**
     * Get a value from the $att property using dot notation.
     * 
     * @param string $key The dot-notated key path (e.g., 'foo.bar.baz')
     * @return mixed The value if found, null otherwise
     * 
     * The last key in the path has special behavior: it first checks if the key exists
     * in the parent array, and if not, looks for a value matching that key name.
     */
    protected function getAttValue(string $key)
    {
        $keys = explode('.', $key);
        $current = $this->att ?? [];

        // Navigate through all keys except the last one
        foreach (array_slice($keys, 0, -1) as $pathKey) {
            if (!is_array($current) || !isset($current[$pathKey])) {
                return null;
            }
            $current = $current[$pathKey];
        }

        // Handle the last key with special logic
        $lastKey = end($keys);

        // First, try to get the last key as a direct key in the current array
        if (is_array($current) && isset($current[$lastKey])) {
            return $current[$lastKey];
        }

        // If not found as a key, look for a value matching the last key
        if (is_array($current)) {
            foreach ($current as $value) {
                if ($value === $lastKey) {
                    return true;
                }
            }
        }

        return null;
    }
}
