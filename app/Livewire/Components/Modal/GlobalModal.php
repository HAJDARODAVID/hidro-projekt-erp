<?php

namespace App\Livewire\Components\Modal;

use App\Models\Application\AppConfig;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalModal extends Component
{
    public ?string $componentName = null;

    public array $componentParams = [];

    /**
     * Triggered from JS via Livewire.dispatch('open-global-modal', { component, params }).
     * After setting state, dispatches a browser event so Alpine opens the overlay.
     */
    #[On('open-global-modal')]
    public function openModal(string $component, array $params = []): void
    {
        $this->componentName = $component;
        $this->componentParams = $params;
        dd($this);
        $this->dispatch('global-modal-open-overlay');
    }

    public function clearComponent(): void
    {
        $this->componentName = null;
        $this->componentParams = [];
    }

    #[On('clear-global-modal')]
    public function onClearGlobalModal(): void
    {
        $this->clearComponent();
    }

    public function render()
    {
        return view('livewire.components.modal.global-modal', [
            'headerName'  => AppConfig::getByKey('global_modal_header_name', 'Module'),
            'headerStyle' => AppConfig::getByKey('global_modal_header_name_style', 'font-weight: 600; font-size: 1rem;'),
            'maxWidth'    => AppConfig::getByKey('global_modal_max_width', '1140px'),
        ]);
    }
}
