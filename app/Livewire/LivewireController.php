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
}
