<?php

namespace App\Livewire\Components\Modal;

use App\Livewire\LivewireController;
use Livewire\Attributes\On;

class StableTestingExample extends LivewireController
{
    public string $info = 'not mounted yet';

    public int $timesReady = 0;

    /**
     * Runs once, the first time this instance is ever created - this is where
     * first-paint setup belongs, since it can't race a dispatched event.
     */
    public function mount(): void
    {
        $this->info = 'mount() ran - first paint';
    }

    /**
     * Because this component keeps a stable wire:key (it survives modal
     * close/reopen instead of being torn down and remounted each time), this
     * listener is already registered well before later opens dispatch the
     * event - unlike a component remounted on every open, which races the
     * very first dispatch against its own hydration.
     */
    #[On('global-modal-component-ready')]
    public function refresh(): void
    {
        $this->timesReady++;
        $this->info = "global-modal-component-ready received - reopened {$this->timesReady} time(s) since mount";
    }

    public function render()
    {
        return view('livewire.components.modal.stable-testing-example');
    }
}
