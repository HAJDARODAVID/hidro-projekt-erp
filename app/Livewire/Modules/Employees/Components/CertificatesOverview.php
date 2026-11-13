<?php

namespace App\Livewire\Modules\Employees\Components;

use Livewire\Attributes\Locked;
use App\Livewire\LivewireController;
use App\Models\Employees\Workplace;
use App\Models\Employees\WorkerCertificate;
use App\Services\Application\Acl\AclService;
use App\Services\Employees\CertificatesOverviewService;

/**
 * Certificates & checks over all workers: what is expired, missing or due soon.
 *
 * One component for every place it is shown, the variant comes in the params:
 *  - "full" (default): the global modal (config/global-modal.php "certificates-overview", quick access CRT)
 *  - "card":           the dashboard card (later), compact, with "show all" opening the full one in the modal
 *
 * ACL permission employees.worker-info.certificates (the same as the worker's tab).
 */
class CertificatesOverview extends LivewireController
{
    public const VARIANTS = ['full', 'card'];

    /**Rows shown on the card */
    public const CARD_ROWS = 5;

    /**Params from the global modal / the dashboard: ['variant' => 'full'|'card', 'days' => int] */
    public array $params = [];

    #[Locked]
    public string $variant = 'full';

    /**Filters. "Missing" is off by default (it can be a long list before the workplaces are filled in), its count is still shown */
    public array $states = [CertificatesOverviewService::STATE_EXPIRED, CertificatesOverviewService::STATE_DUE_SOON];
    public string $type = '';
    public string $workplace = '';
    public string $search = '';
    public int $days = WorkerCertificate::DUE_SOON_DAYS;

    public function mount()
    {
        $this->variant = in_array($this->params['variant'] ?? 'full', self::VARIANTS, TRUE) ? $this->params['variant'] ?? 'full' : 'full';
        if (isset($this->params['days'])) $this->days = $this->cleanDays($this->params['days']);
    }

    public function updatedDays($value): void
    {
        $this->days = $this->cleanDays($value);
    }

    private function cleanDays($value): int
    {
        return in_array((int) $value, [30, 60, 90, 180], TRUE) ? (int) $value : WorkerCertificate::DUE_SOON_DAYS;
    }

    /**A certificate was renewed (RenewCertificateModal): render again with the new dates */
    #[\Livewire\Attributes\On(RenewCertificateModal::CHANGED_EVENT)]
    public function certificatesChanged(): void
    {
    }

    public function resetFilters(): void
    {
        $this->reset('states', 'type', 'workplace', 'search', 'days');
    }

    public function render()
    {
        $allowed = AclService::current()->can(WorkerCertificates::PERMISSION);
        $data = ['allowed' => $allowed, 'rows' => [], 'counts' => [], 'total' => 0];

        if ($allowed) {
            $all = CertificatesOverviewService::rows($this->days);
            $search = mb_strtolower(trim($this->search));

            $rows = array_values(array_filter($all, fn ($row) =>
                in_array($row['state'], $this->states, TRUE)
                && ($this->type === '' || $row['type'] === $this->type)
                && ($this->workplace === '' || (string) $row['workplaceId'] === $this->workplace)
                && ($search === '' || str_contains(mb_strtolower($row['worker'] . ' ' . $row['title'] . ' ' . $row['workplace']), $search))
            ));

            $data = [
                'allowed'    => TRUE,
                'rows'       => $this->variant === 'card' ? array_slice($all, 0, self::CARD_ROWS) : $rows,
                'counts'     => CertificatesOverviewService::counts($all),
                'total'      => count($all),
                'filtered'   => count($rows),
                'types'      => array_map(fn ($type) => translator($type[0]), WorkerCertificate::TYPES),
                'workplaces' => Workplace::orderBy('key')->pluck('key', 'id'),
            ];
        }

        return view('livewire.modules.employees.components.certificates-overview', $data);
    }
}
