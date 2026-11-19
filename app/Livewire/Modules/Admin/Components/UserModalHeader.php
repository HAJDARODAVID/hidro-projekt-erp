<?php

namespace App\Livewire\Modules\Admin\Components;

use App\Livewire\LivewireController;
use App\Livewire\Modules\Admin\Users;
use App\Models\User;
use App\Services\Application\Acl\AclService;
use Livewire\Attributes\On;

/**
 * Header of the global modal admin-user (config/global-modal.php: header-component):
 * who the modal is about, status and the dates. Refreshed by UserModal after a change.
 */
class UserModalHeader extends LivewireController
{
    /**Params passed in from the global modal (the same as the modal body gets) */
    public array $params = [];

    public $userId = NULL;

    /**Event UserModal dispatches after a change (with the user ID, also the new one after creating) */
    public const REFRESH_EVENT = 'admin-user-modal-header-refresh';

    public function boot()
    {
        abort_unless(AclService::current()->canRoute(Users::ROUTE), 403);
    }

    public function mount()
    {
        $this->userId = isset($this->params['userId']) ? (int) $this->params['userId'] : NULL;
    }

    #[On(self::REFRESH_EVENT)]
    public function refreshHeader($userId = NULL)
    {
        if ($userId) $this->userId = (int) $userId;
    }

    public function render()
    {
        $user = $this->userId ? User::find($this->userId) : NULL;

        return view('livewire.modules.admin.components.user-modal-header', [
            'user'   => $user,
            'isSelf' => $user && $user->id === auth()->id(),
        ]);
    }
}
