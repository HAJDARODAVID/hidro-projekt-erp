<?php

namespace App\Livewire\Components\Modal;

use App\Models\Application\AppConfig;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalModal extends Component
{
    public $isOpen = false;

    public ?string $componentName = null;

    public array $componentParams = [];

    public int $renderVersion = 0;

    /**
     * Triggered from JS via Livewire.dispatch('open-global-modal', { component, params }).
     * $this->js() runs after the DOM morph, so the nested @livewire() child is already
     * in the DOM when Alpine receives the event and shows the overlay.
     */
    #[On('open-global-modal')]
    public function openModal(string $component, array $params = []): void
    {
        $this->componentName = $component;
        $this->componentParams = $params;
        $this->renderVersion++;
        $this->dispatch('global-modal-open-overlay');
        //$this->js("window.dispatchEvent(new CustomEvent('global-modal-open-overlay'))");
    }

    /**
     * Called by Alpine's close() via $wire.clearComponent() so the child
     * component is unmounted on the next render.
     */
    public function clearComponent(): void
    {
        $this->componentName = null;
        $this->componentParams = [];
        $this->renderVersion++;
    }

    public function render()
    {
        return view('livewire.components.modal.global-modal', [
            'componentName' => $this->componentName,
            'componentParams' => $this->componentParams,
            'renderVersion' => $this->renderVersion,
            'headerName'  => AppConfig::getByKey('global_modal_header_name', 'Module_fix'),
            'headerStyle' => AppConfig::getByKey('global_modal_header_name_style', 'font-weight: 600; font-size: 1rem;'),
            'maxWidth'    => AppConfig::getByKey('global_modal_max_width', '1140px'),
            'time' => now()->format('Y-m-d H:i:s.u'),
        ]);
    }
}
