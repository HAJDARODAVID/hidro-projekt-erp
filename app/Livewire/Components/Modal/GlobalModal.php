<?php

namespace App\Livewire\Components\Modal;

use App\Livewire\LivewireController;
use App\Models\Application\AppConfig;
use App\Services\Application\GlobalModal\GlobalModalService;
use App\Services\Application\GlobalModal\ModalDto;
use Livewire\Attributes\On;
use Livewire\Mechanisms\ComponentRegistry;

class GlobalModal extends LivewireController
{
    public array $componentParams = [];

    public int $renderVersion = 0;

    /**
     * The registry key of the currently open modal (see config/global-modal.php).
     * Kept as a plain public string - rather than the resolved ModalDto - because
     * Livewire only persists public properties across requests; render() resolves
     * this back into a ModalDto every time so it's never stale after a request
     * that didn't go through openGlobalModal() (e.g. clearComponent()).
     */
    public ?string $modalName = null;

    /**
     * Triggered from JS via Livewire.dispatch('open-global-modal', { component, params }).
     * $this->js() runs after the DOM morph, so the nested @livewire() child is already
     * in the DOM when Alpine receives the event and shows the overlay.
     */
    #[On('open-global-modal')]
    public function openGlobalModal(string $component, array $params = []): void
    {
        try {
            $modal = (new GlobalModalService)->make($component);

            // Resolve the underlying Livewire component now, so a bad/renamed
            // component-path in config/global-modal.php fails here with a
            // friendly notification instead of crashing render()'s @livewire() call.
            app(ComponentRegistry::class)->getClass($modal->getComponentPath());
        } catch (\Throwable $th) {
            $this->notifyMe($th->getMessage(), 'danger');
            return;
        }

        $this->modalName = $component;
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
            'modalService' => $this->modalName ? (new GlobalModalService)->make($this->modalName) : new ModalDto(),
            'time' => now()->format('Y-m-d H:i:s.u'),
        ]);
    }
}
