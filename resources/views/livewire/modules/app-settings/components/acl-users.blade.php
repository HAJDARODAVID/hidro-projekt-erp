<div class="row flex-fill g-0 gap-3">
    {{-- Users list --}}
    <div class="col-md-3 d-flex flex-column">
        <x-ui.card title="{{ translator('Users') }}" class="flex-fill d-flex flex-column">
            <x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="userSearch" wModelEvent="live.debounce.250ms" :removeAddOnXP=TRUE>
                @if ($userSearch)
                    <x-slot:append><x-ui.btn type="lig.sm" icon="trash" action="resetUserSearch" /></x-slot:append>
                @endif
            </x-ui.input>
            <hr class="m-0 my-2">
            <div style="max-height: 70vh; overflow-y: auto">
                <x-ui.list-group>
                    @foreach ($users as $user)
                        <x-ui.list-item :selected="$user['id'] == $selectedUser" wClickMethod="selectUser" wClickParam="{{ $user['id'] }}">
                            <x-slot:slotLeft>
                                <div class="@if (!$user['active']) text-muted @endif">{{ $user['name'] }} {{ $user['last_name'] }}</div>
                                <small class="text-muted">{{ $user['email'] }}</small>
                            </x-slot:slotLeft>
                        </x-ui.list-item>
                    @endforeach
                </x-ui.list-group>
            </div>
        </x-ui.card>
    </div>

    {{-- Access of the selected user --}}
    <div class="col-md d-flex flex-column">
        <x-ui.card title="{{ translator('User access') }}" class="flex-fill d-flex flex-column" loading="selectUser">
            @if ($selectedUser)
                @if ($isSuperAdmin)
                    <div class="alert alert-info py-2 no-border-radius">{{ translator('This user is a super admin and may do everything, the settings below do not limit them.') }}</div>
                @endif

                <b>{{ translator('Roles') }}</b>
                <hr class="m-0 p-0 my-2">
                <div class="d-flex flex-wrap gap-3">
                    @forelse ($roles as $role)
                        <div class="form-check" wire:key="user-role-{{ $role->id }}">
                            <input class="form-check-input" type="checkbox" id="ur{{ $role->id }}" wire:model.live="userRoles.{{ $role->id }}">
                            <label class="form-check-label @if (!$role->active) text-muted text-decoration-line-through @endif" for="ur{{ $role->id }}" title="{{ $role->key }}">{{ $role->name }}</label>
                        </div>
                    @empty
                        <i>{{ translator('No roles yet!') }}</i>
                    @endforelse
                </div>

                <hr>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <b>{{ translator('Permissions') }}</b>
                    <small class="text-muted">{{ translator('Inherit = follow the roles, Allow / Deny = override for this user (deny wins).') }}</small>
                    <div class="ms-auto" style="width: 250px">
                        <x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="permissionSearch" wModelEvent="live.debounce.250ms" />
                    </div>
                </div>
                <div style="max-height: 50vh; overflow-y: auto">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="sticky-top bg-white">
                            <tr>
                                <th>{{ translator('Permission') }}</th>
                                <th>{{ translator('From roles') }}</th>
                                <th style="width: 140px">{{ translator('Override') }}</th>
                                <th class="text-center" style="width: 80px">{{ translator('Access') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($catalog as $group => $permissions)
                                <tr><td colspan="4" class="small text-uppercase fw-bold bg-light">{{ $group }}</td></tr>
                                @foreach ($permissions as $permissionId => $permission)
                                    <tr wire:key="user-perm-{{ $permissionId }}">
                                        <td>{{ $permission['name'] }} <code class="small ms-1">{{ $permission['key'] }}</code></td>
                                        <td><small class="text-muted">{{ $fromRoles[$permissionId] ?? '—' }}</small></td>
                                        <td>
                                            <select class="form-select form-select-sm no-border-radius" wire:model.change="overrides.{{ $permissionId }}">
                                                <option value="inherit">{{ translator('Inherit') }}</option>
                                                <option value="allow">{{ translator('Allow') }}</option>
                                                <option value="deny">{{ translator('Deny') }}</option>
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            @if ($isSuperAdmin || isset($effective[$permission['key']]))
                                                <i class="bi bi-check-circle-fill text-success"></i>
                                            @else
                                                <i class="bi bi-x-circle text-danger"></i>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr><td colspan="4" class="text-center py-3"><i>{{ translator('No permissions found!') }}</i></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="d-flex justify-content-center"><div class="py-3"><i>{{ translator('No user selected!') }}</i></div></div>
            @endif
        </x-ui.card>
    </div>
</div>
