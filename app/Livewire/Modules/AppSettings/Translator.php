<?php

namespace App\Livewire\Modules\AppSettings;

use App\Livewire\LivewireController;
use App\Models\Translation\AppTranslation;
use App\Services\Application\Acl\AclService;
use App\Services\Application\AppTranslationsSyncService;
use App\Services\Application\ManageTranslationsService;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Translator: maintain the app translations (app_translations) used by translator().
 * Every change is saved right away. New strings of the code are found with `php artisan translations:scan`.
 */
class Translator extends LivewireController
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [25, 50, 100];

    #[Url('search')]
    public string $search = '';

    #[Url('lang')]
    public string $lang = 'hr';

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
        if (!in_array($this->lang, ManageTranslationsService::languages(), TRUE)) $this->lang = 'hr';
        if (!in_array($this->perPage, self::PER_PAGE_OPTIONS, TRUE)) $this->perPage = self::PER_PAGE_OPTIONS[0];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedLang()
    {
        if (!in_array($this->lang, ManageTranslationsService::languages(), TRUE)) $this->lang = 'hr';
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        if (!in_array((int) $this->perPage, self::PER_PAGE_OPTIONS, TRUE)) $this->perPage = self::PER_PAGE_OPTIONS[0];
        $this->resetPage();
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

    public function createTranslation()
    {
        $this->run(function () {
            $row = $this->service()->create((string) $this->newTranslation['value'], $this->lang, (string) $this->newTranslation['translation']);
            $this->newTranslation = ['value' => '', 'translation' => ''];
            $this->search = $row->value;
            $this->resetPage();
            $this->notifyMe(translator('Translation saved!'));
        });
    }

    /**
     * Inline edit of a translation (on change of the input).
     */
    public function saveTranslation($id, $translation)
    {
        $this->run(function () use ($id, $translation) {
            $this->service()->update((int) $id, (string) $translation);
            $this->notifyMe(translator('Translation saved!'));
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
     | Sync between environments (same as the modules, the ACL and the menus)
     * --------------------------------------------------------------------------------------------*/

    public function exportTranslations()
    {
        if (!app()->isLocal()) return $this->showException(translator('The translations can only be exported on the local environment.'));

        try {
            $export = AppTranslationsSyncService::exportToFile();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }
        $this->notifyMe(translator('Exported') . ': ' . $export['translations'] . ' ' . translator('translations'));
    }

    public function syncTranslationsFromFile()
    {
        if (app()->isLocal()) return $this->showException(translator('On the local environment the translations are exported, not synced.'));

        try {
            $stats = AppTranslationsSyncService::importFromFile();
        } catch (\Throwable $th) {
            return $this->showException($th->getMessage());
        }
        $this->notifyMe(translator('Synced') . ': ' . ($stats['created'] ?? 0) . ' ' . translator('created') . ', ' . ($stats['updated'] ?? 0) . ' ' . translator('updated'));
    }

    /* ----------------------------------------------------------------------------------------------
     | Internals
     * --------------------------------------------------------------------------------------------*/

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

        $translations = AppTranslation::query()
            ->where('lang', $this->lang)
            ->when($search !== '', function ($query) use ($search) {
                // value is case sensitive (utf8mb4_bin): search it case insensitive
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where(fn ($q) => $q->whereRaw('value COLLATE utf8mb4_unicode_ci LIKE ?', [$like])->orWhere('translation', 'LIKE', $like));
            })
            ->orderByRaw('value COLLATE utf8mb4_unicode_ci')
            ->paginate($this->perPage);

        return view('livewire.modules.app-settings.translator', [
            'translations'   => $translations,
            'languages'      => ManageTranslationsService::languages(),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'maxLength'      => ManageTranslationsService::MAX_LENGTH,
            'isLocalEnv'     => app()->isLocal(),
            'syncFileDate'   => File::exists(AppTranslationsSyncService::filePath()) ? date('d.m.Y H:i', File::lastModified(AppTranslationsSyncService::filePath())) : NULL,
        ]);
    }
}
