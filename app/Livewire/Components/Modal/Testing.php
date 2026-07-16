<?php

namespace App\Livewire\Components\Modal;

use App\Livewire\LivewireController;

class Testing extends LivewireController
{
    public string $info = 'initial info';

    public function mount()
    {
        $this->info = 'init method has run';
    }

    public function render()
    {
        return view('livewire.components.modal.testing');
    }
}
