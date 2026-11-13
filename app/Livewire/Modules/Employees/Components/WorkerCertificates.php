<?php

namespace App\Livewire\Modules\Employees\Components;

use Livewire\Attributes\Locked;
use App\Livewire\LivewireController;
use App\Models\Employees\WorkerCertificate;
use App\Services\Application\Acl\AclService;
use App\Services\Employees\WorkerCertificatesService;

/**
 * Employees -> Worker info -> Certificates & checks.
 * Medical check-ups, trainings and certificates of the worker with their due dates, and what the
 * worker's workplace requires. ACL permission employees.worker-info.certificates (tab + changes).
 */
class WorkerCertificates extends LivewireController
{
    public const PERMISSION = 'employees.worker-info.certificates';

    #[Locked]
    public $workerId;

    /**Form open? (new or editing) */
    public bool $showForm = FALSE;

    /**Row being edited, NULL = new */
    #[Locked]
    public ?int $editingId = NULL;

    public array $form = ['type' => 'medical', 'title' => '', 'issued_at' => '', 'valid_until' => '', 'note' => ''];

    /**The last suggested "valid until" (replaced by a new suggestion only while unchanged) */
    public ?string $suggestedUntil = NULL;

    /**Show the history (renewed / deleted versions, worker_certificate_history) */
    public bool $showHistory = FALSE;

    /**A certificate of this worker was renewed in the modal: render again */
    #[\Livewire\Attributes\On(RenewCertificateModal::CHANGED_EVENT)]
    public function certificatesChanged($workerId = NULL): void
    {
        if ($workerId !== NULL && (int) $workerId !== (int) $this->workerId) $this->skipRender();
    }

    private function allowed(): bool
    {
        return AclService::current()->can(self::PERMISSION);
    }

    /**
     * Open the form for a new row, optionally pre-filled (from a requirement: type + title).
     */
    public function newEntry($type = 'medical', $title = ''): void
    {
        if (!$this->allowed()) return;
        $type = isset(WorkerCertificate::TYPES[$type]) ? $type : 'medical';
        $this->editingId = NULL;
        $this->form = [
            'type'        => $type,
            'title'       => WorkerCertificate::isOneOfAKind($type) ? '' : (string) $title,
            'issued_at'   => now()->format('Y-m-d'),
            'valid_until' => '',
            'note'        => '',
        ];
        $this->suggestedUntil = WorkerCertificatesService::suggestValidUntil((int) $this->workerId, $type, $this->form['issued_at']);
        $this->form['valid_until'] = (string) $this->suggestedUntil;
        $this->showForm = TRUE;
    }

    public function editEntry($id): void
    {
        if (!$this->allowed()) return;
        $row = WorkerCertificate::where('worker_id', (int) $this->workerId)->find((int) $id);
        if ($row === NULL) {
            $this->showException(translator('The entry does not exist.'));
            return;
        }
        $this->editingId = $row->id;
        $this->form = [
            'type'        => $row->type,
            'title'       => WorkerCertificate::isOneOfAKind($row->type) ? '' : $row->title,
            'issued_at'   => $row->issued_at?->format('Y-m-d') ?? '',
            'valid_until' => $row->valid_until?->format('Y-m-d') ?? '',
            'note'        => (string) $row->note,
        ];
        $this->suggestedUntil = NULL;
        $this->showForm = TRUE;
    }

    public function cancelForm(): void
    {
        $this->reset('showForm', 'editingId', 'form', 'suggestedUntil');
    }

    /**
     * A new medical check-up date: suggest the due date from the workplace interval
     * (only while it's empty or still the last suggestion, so a typed date isn't overwritten).
     */
    public function updatedForm($value, $field): void
    {
        if (!in_array($field, ['issued_at', 'type'], TRUE)) return;
        $suggestion = WorkerCertificatesService::suggestValidUntil((int) $this->workerId, (string) $this->form['type'], (string) $this->form['issued_at']);
        if ($suggestion !== NULL && in_array($this->form['valid_until'], ['', $this->suggestedUntil], TRUE)) {
            $this->form['valid_until'] = $suggestion;
            $this->suggestedUntil = $suggestion;
        }
    }

    public function saveEntry()
    {
        if (!$this->allowed()) return $this->showException(translator('You are not allowed to change the certificates!'));

        $service = WorkerCertificatesService::init()->save((int) $this->workerId, $this->form, $this->editingId);
        if (!$service->getResponseStatus()) return $this->showException($service->getResponse()['message']);

        $this->cancelForm();
        $this->notifyMe($service->getResponse()['message']);
    }

    public function deleteEntry($id)
    {
        if (!$this->allowed()) return $this->showException(translator('You are not allowed to change the certificates!'));

        $service = WorkerCertificatesService::init()->delete((int) $this->workerId, (int) $id);
        if (!$service->getResponseStatus()) return $this->showException($service->getResponse()['message']);

        if ($this->editingId === (int) $id) $this->cancelForm();
        $this->notifyMe($service->getResponse()['message']);
    }

    public function render()
    {
        $entries = WorkerCertificatesService::entries((int) $this->workerId);
        $historyCount = \App\Models\Employees\WorkerCertificateHistory::where('worker_id', (int) $this->workerId)->count();

        return view('livewire.modules.employees.components.worker-certificates', [
            'canEdit'      => $this->allowed(),
            'entries'      => $entries,
            'history'      => $this->showHistory ? WorkerCertificatesService::history((int) $this->workerId) : collect(),
            'historyCount' => $historyCount,
            'requirements' => WorkerCertificatesService::requirements((int) $this->workerId, $entries),
            'types'        => array_map(fn ($type) => translator($type[0]), WorkerCertificate::TYPES),
            'oneOfAKind'   => array_keys(array_filter(WorkerCertificate::TYPES, fn ($type) => $type[1])),
            'suggestions'  => WorkerCertificatesService::licenceSuggestions(),
            'dueSoonDays'  => WorkerCertificate::DUE_SOON_DAYS,
        ]);
    }
}
