<div class="px-2">
    @if ($userId)
        <div class="row mt-1 px-2">
            <div class="col-md-6">
                <b>{{ translator('User rights') }}</b>
                <hr class="m-0 p-0 my-2">
                <ul class="list-group">
                    @foreach ($roleGroups as $roleId => $roleName)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <label class="form-check-label flex-fill" for="workerUserRole{{ $roleId }}">{{ $roleName }}</label>
                            <div wire:loading.remove wire:target="userRoles.{{ $roleId }}">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="workerUserRole{{ $roleId }}"
                                    wire:model.live="userRoles.{{ $roleId }}"
                                    wire:loading.attr="disabled"
                                >
                            </div>
                            <div class="spinner-border spinner-border-sm" role="status" wire:loading wire:target="userRoles.{{ $roleId }}"></div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @else
        <div class="d-flex justify-content-center"><div class="py-3"><i>{{ translator('Worker has no app user!') }}</i></div></div>
    @endif
</div>
