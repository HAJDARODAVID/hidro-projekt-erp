<?php

namespace App\Livewire\Modules\Employees\Components;

use Livewire\Attributes\Locked;
use App\Livewire\LivewireController;
use App\Models\Employees\WorkerCertificate;
use App\Services\Application\Acl\AclService;
use App\Services\Employees\WorkerCertificatesService;

/**
 * Renew a worker's certificate / check (global modal "renew-certificate", params: certificateId).
 * Start date = the current due date, due date suggested (WorkerCertificatesService::suggestRenewal()).
 * The current version goes to the history; afterwards CHANGED_EVENT refreshes the tab / the overview.
 */
class RenewCertificateModal extends LivewireController
{
    /**Sent after a renewal (the worker's tab and the overview refresh) */
    public const CHANGED_EVENT = 'worker-certificates-changed';

    /**Params from the global modal: ['certificateId' => int] */
    public array $params = [];

    #[Locked]
    public ?int $certificateId = NULL;

    #[Locked]
    public ?int $workerId = NULL;

    public array $form = ['issued_at' => '', 'valid_until' => '', 'note' => ''];

    /**The last suggested due date (a new start date replaces it only while it's unchanged) */
    public ?string $suggestedUntil = NULL;

    public function mount()
    {
        $row = $this->row((int) ($this->params['certificateId'] ?? 0));
        if ($row === NULL) return;

        $this->certificateId = $row->id;
        $this->workerId = $row->worker_id;
        $suggestion = WorkerCertificatesService::suggestRenewal($row);
        $this->form = ['issued_at' => (string) $suggestion['issued_at'], 'valid_until' => (string) $suggestion['valid_until'], 'note' => ''];
        $this->suggestedUntil = $suggestion['valid_until'];
    }

    private function allowed(): bool
    {
        return AclService::current()->can(WorkerCertificates::PERMISSION);
    }

    private function row(int $id): ?WorkerCertificate
    {
        return $id > 0 ? WorkerCertificate::with('worker:id,firstName,lastName')->find($id) : NULL;
    }

    /**A new start date: move the suggested due date along (not a date the user typed) */
    public function updatedForm($value, $field): void
    {
        if ($field !== 'issued_at') return;
        $row = $this->row((int) $this->certificateId);
        if ($row === NULL) return;
        $suggestion = WorkerCertificatesService::suggestRenewal($row, (string) $value)['valid_until'];
        if ($suggestion !== NULL && in_array($this->form['valid_until'], ['', $this->suggestedUntil], TRUE)) {
            $this->form['valid_until'] = $suggestion;
            $this->suggestedUntil = $suggestion;
        }
    }

    public function renew()
    {
        if (!$this->allowed()) return $this->showException(translator('You are not allowed to change the certificates!'));
        if ($this->certificateId === NULL) return $this->showException(translator('The entry does not exist.'));

        $service = WorkerCertificatesService::init()->renew((int) $this->workerId, (int) $this->certificateId, $this->form);
        if (!$service->getResponseStatus()) return $this->showException($service->getResponse()['message']);

        $this->js("window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))");
        $this->dispatch(self::CHANGED_EVENT, workerId: $this->workerId);
        $this->notifyMe($service->getResponse()['message']);
    }

    public function render()
    {
        $row = $this->certificateId ? $this->row($this->certificateId) : NULL;
        return view('livewire.modules.employees.components.renew-certificate-modal', [
            'allowed' => $this->allowed(),
            'row'     => $row,
            'state'   => $row?->status(),
            'types'   => array_map(fn ($type) => translator($type[0]), WorkerCertificate::TYPES),
        ]);
    }
}
