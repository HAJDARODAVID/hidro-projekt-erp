<?php

namespace App\Livewire\Components\Modal;

use App\Livewire\LivewireController;
use App\Models\Application\AppConfig;
use App\Services\Application\GlobalModal\GlobalModalService;
use App\Services\Application\GlobalModal\ModalDto;
use Livewire\Attributes\On;

class GlobalModal extends LivewireController
{
    public array $componentParams = [];

    public int $renderVersion = 0;

    private ?ModalDto $modal = null;

    /**
     * Triggered from JS via Livewire.dispatch('open-global-modal', { component, params }).
     * $this->js() runs after the DOM morph, so the nested @livewire() child is already
     * in the DOM when Alpine receives the event and shows the overlay.
     */
    #[On('open-global-modal')]
    public function openGlobalModal(string $component, array $params = []): void
    {
        try {
            $this->modal = (new GlobalModalService)->make($component);
        } catch (\Throwable $th) {
            $this->notifyMe($th->getMessage(), 'danger');
            return;
        }

        $this->componentParams = $params;
        $this->renderVersion++;
        $this->dispatch('global-modal-open-overlay');
        //$this->js("window.dispatchEvent(new CustomEvent('global-modal-open-overlay'))");

        // Dispatched after the child component above has been rendered into the DOM
        // (same request/morph as global-modal-open-overlay), so the freshly mounted
        // child's #[On('global-modal-component-ready')] listener can run its own
        // setup/data-loading method here instead of relying on mount().
        $this->dispatch('global-modal-component-ready');
    }

    /**
     * Called by Alpine's close() via $wire.clearComponent() so the child
     * component is unmounted on the next render.
     */
    public function clearComponent(): void
    {
        $this->componentParams = [];
        $this->renderVersion++;
    }

    public function render()
    {
        return view('livewire.components.modal.global-modal', [
            'headerName'  => AppConfig::getByKey('global_modal_header_name', 'Module_fix'),
            'headerStyle' => AppConfig::getByKey('global_modal_header_name_style', 'font-weight: 600; font-size: 1rem;'),
            'maxWidth'    => AppConfig::getByKey('global_modal_max_width', '1140px'),
            'modalService'  => $this->modal ?? new ModalDto(),
            'time' => now()->format('Y-m-d H:i:s.u'),
        ]);
    }
}
