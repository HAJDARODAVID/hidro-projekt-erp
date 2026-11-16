<?php

namespace App\Livewire\Modules\AppSettings;

use App\Livewire\LivewireController;
use App\Models\Translation\AppTranslation;
use App\Services\Application\Acl\AclService;
use App\Services\Application\AppTranslationsService;
use App\Services\Application\ManageTranslationsService;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Translator: maintain the app translations (app_translations) used by translator().
 * The texts come from the code (translations:scan, runs on every deploy), the translations are filled in here,
 * per environment. Every change is saved right away.
 */
class Translator extends LivewireController
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [25, 50, 100];

    /** Status filter: all | untranslated | translated */
    public const STATUSES = ['all', 'untranslated', 'translated'];

    #[Url('search')]
    public string $search = '';

    #[Url('lang')]
    public string $lang = '';

    #[Url('status')]
    public string $status = 'all';

    #[Url('per_page')]
    public int $perPage = 25;

    public bool $showNew = FALSE;

    public array $newTranslation = ['value' => '', 'translation' => ''];

    /**
     * Livewire requests skip the route middleware: check the super admin on every request.
     */
    public function boot()
    {
        abort_unless(AclService::current()->isSuperAdmin(), 403);
    }

    public function mount()
    {
        $this->sanitizeFilters();
    }

    public function updated($property)
    {
        if (in_array($property, ['search', 'lang', 'status', 'perPage'], TRUE)) {
            $this->sanitizeFilters();
            $this->resetPage();
        }
    }

    public function resetSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function toggleNew()
    {
        $this->showNew = !$this->showNew;
        $this->newTranslation = ['value' => '', 'translation' => ''];
    }

    /**
     * Add the translator() strings of the code that are missing (same as the deploy does).
     */
    public function scanCode()
    {
        try {
            $stats = AppTranslationsService::addMissingStrings();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }
        $this->resetPage();
        $this->notifyMe(translator('Scan done') . ': ' . array_sum($stats['added']) . ' ' . translator('texts added'));
    }

    public function createTranslation()
    {
        $this->run(function () {
            $row = $this->service()->create((string) $this->newTranslation['value'], $this->lang, (string) $this->newTranslation['translation']);
            $this->newTranslation = ['value' => '', 'translation' => ''];
            $this->search = $row->value;
            $this->status = 'all';
            $this->resetPage();
            $this->notifyMe(translator('Translation saved!'));
        });
    }

    /**
     * Inline edit of a translation (on change of the input), empty = not translated.
     */
    public function saveTranslation($id, $translation)
    {
        $this->run(function () use ($id, $translation) {
            $row = $this->service()->update((int) $id, (string) $translation);
            $this->notifyMe($row->translation === NULL ? translator('Translation cleared') : translator('Translation saved!'));
        });
    }

    public function deleteTranslation($id)
    {
        $this->run(function () use ($id) {
            $this->service()->delete((int) $id);
            $this->notifyMe(translator('Translation deleted!'));
        });
    }

    /* ----------------------------------------------------------------------------------------------
     | Internals
     * --------------------------------------------------------------------------------------------*/

    private function sanitizeFilters(): void
    {
        if (!AppTranslationsService::isAllowedLanguage($this->lang)) $this->lang = AppTranslationsService::defaultLanguage();
        if (!in_array($this->status, self::STATUSES, TRUE)) $this->status = 'all';
        if (!in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, TRUE)) $this->perPage = self::PER_PAGE_OPTIONS[0];
    }

    private function service(): ManageTranslationsService
    {
        return app(ManageTranslationsService::class);
    }

    /**
     * Run a change; a validation problem is shown to the user.
     */
    private function run(callable $change): void
    {
        try {
            $change();
        } catch (\InvalidArgumentException $e) {
            $this->showException($e->getMessage());
        }
    }

    public function render()
    {
        $search = trim($this->search);
        $untranslated = fn ($q) => $q->whereNull('translation')->orWhere('translation', '');

        $translations = AppTranslation::query()
            ->where('lang', $this->lang)
            ->when($this->status === 'untranslated', fn ($query) => $query->where($untranslated))
            ->when($this->status === 'translated', fn ($query) => $query->whereNotNull('translation')->where('translation', '!=', ''))
            ->when($search !== '', function ($query) use ($search) {
                // value is case sensitive (utf8mb4_bin): search it case insensitive
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(fn ($q) => $q->whereRaw('value COLLATE utf8mb4_unicode_ci LIKE ?', [$like])->orWhere('translation', 'LIKE', $like));
            })
            ->orderByRaw('value COLLATE utf8mb4_unicode_ci')
            ->paginate($this->perPage);

        return view('livewire.modules.app-settings.translator', [
            'translations'      => $translations,
            'languages'         => AppTranslationsService::languages(),
            'untranslatedCount' => AppTranslation::where('lang', $this->lang)->where($untranslated)->count(),
            'statuses'          => [
                'all'          => translator('All'),
                'untranslated' => translator('Untranslated'),
                'translated'   => translator('Translated'),
            ],
            'perPageOptions'    => self::PER_PAGE_OPTIONS,
            'maxLength'         => ManageTranslationsService::MAX_LENGTH,
        ]);
    }
}
