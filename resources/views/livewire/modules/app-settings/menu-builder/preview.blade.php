{{-- The menu as a user sees it, rendered with the real component --}}
<div wire:key="mb-preview">
    <label class="form-label small fw-bold mb-1">{{ translator('Preview as') }}</label>
    <select class="form-select form-select-sm mb-2" wire:model.live="previewUserId">
        <option value="">{{ translator('Me') }} ({{ auth()->user()->name }})</option>
        @foreach ($users as $user)
            @continue($user->id === auth()->id())
            <option value="{{ $user->id }}">{{ trim($user->name . ' ' . $user->last_name) }}@if (!$user->active) – {{ translator('inactive') }}@endif</option>
        @endforeach
    </select>
    <div class="form-text mb-2">
        @if ($previewUser && \App\Services\Application\Acl\AclService::forUser($previewUser)->isSuperAdmin())
            <i class="bi bi-info-circle"></i> {{ translator('Super admin: sees every item.') }}
        @else
            {{ translator('Only what this user may open is shown (ACL), empty groups are hidden.') }}
        @endif
    </div>

    @if (!$menu->active)
        <div class="alert alert-secondary py-1 px-2 small"><i class="bi bi-eye-slash"></i> {{ translator('The menu is switched off, it is not rendered anywhere.') }}</div>
    @endif

    <div class="mb-preview">
        <x-ui.app-menu :menu="$menu->id" :user="$previewUser ?? auth()->user()" currentRoute="" :interactive="false" :preview="true" />
    </div>

    <label class="form-label small fw-bold mt-3 mb-1">{{ translator('Use it in a view') }}</label>
    <code class="d-block p-2 bg-light border small user-select-all">&lt;x-ui.app-menu menu="{{ $menu->key }}" /&gt;</code>
</div>
