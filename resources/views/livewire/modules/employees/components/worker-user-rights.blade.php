<div class="px-2">
    @if ($userId)
        @if ($isSuperAdmin)
            <div class="alert alert-info py-2 mt-1 no-border-radius">{{ translator('This user is a super admin and may do everything, the roles below do not limit them.') }}</div>
        @endif
        <div class="row mt-1 px-2 g-3">
            <div class="col-md-6">
                <b>{{ translator('Roles') }}</b>
                <hr class="m-0 p-0 my-2">
                <ul class="list-group">
                    @forelse ($roles as $role)
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2" wire:key="worker-role-{{ $role->id }}">
                            <label class="form-check-label flex-fill" for="workerUserRole{{ $role->id }}">
                                <span class="@if (!$role->active) text-muted text-decoration-line-through @endif">{{ $role->name }}</span>
                                <small class="text-muted ms-1">{{ $role->key }}</small>
                                @if ($role->description) <div class="small text-muted">{{ $role->description }}</div> @endif
                            </label>
                            <small class="text-muted" title="{{ translator('Permissions') }}"><i class="bi bi-key"></i> {{ $role->permissions_count }}</small>
                            <div wire:loading.remove wire:target="userRoles.{{ $role->id }}">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="workerUserRole{{ $role->id }}"
                                    wire:model.live="userRoles.{{ $role->id }}"
                                    wire:loading.attr="disabled"
                                >
                            </div>
                            <div class="spinner-border spinner-border-sm" role="status" wire:loading wire:target="userRoles.{{ $role->id }}"></div>
                        </li>
                    @empty
                        <li class="list-group-item text-center"><i>{{ translator('No roles yet! Create them in the App control center - Access control.') }}</i></li>
                    @endforelse
                </ul>
            </div>
            <div class="col-md-6">
                <b>{{ translator('Effective permissions') }}</b>
                <hr class="m-0 p-0 my-2">
                @if ($isSuperAdmin)
                    <i class="text-muted">{{ translator('All') }}</i>
                @else
                    <div class="d-flex flex-wrap gap-1">
                        @forelse ($effective as $permissionKey)
                            <code class="border px-1">{{ $permissionKey }}</code>
                        @empty
                            <i class="text-muted">{{ translator('None') }}</i>
                        @endforelse
                    </div>
                @endif
                <div class="small text-muted mt-2">{{ translator('Per-user overrides (allow / deny) are set in the App control center - Access control - User access.') }}</div>
            </div>
        </div>
    @else
        <div class="d-flex justify-content-center"><div class="py-3"><i>{{ translator('Worker has no app user!') }}</i></div></div>
    @endif
</div>
