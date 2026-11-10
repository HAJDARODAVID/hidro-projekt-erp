<div class="row flex-fill g-0 gap-3">
    {{-- Roles list --}}
    <div class="col-md-3 d-flex flex-column">
        <x-ui.card title="{{ translator('Roles') }}" class="flex-fill d-flex flex-column">
            <div class="d-flex gap-1 mb-2">
                <x-ui.input size="sm" placeholder="{{ translator('Role name') }}" wModel="newRole.name" />
                <x-ui.input size="sm" placeholder="{{ translator('KEY') }}" wModel="newRole.key" />
                <x-ui.btn type="suc.sm" icon="add" action="createRole" title="{{ translator('Create role') }}" />
            </div>
            <x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="roleSearch" wModelEvent="live.debounce.250ms" :removeAddOnXP=TRUE>
                @if ($roleSearch)
                    <x-slot:append><x-ui.btn type="lig.sm" icon="trash" action="resetRoleSearch" /></x-slot:append>
                @endif
            </x-ui.input>
            <hr class="m-0 my-2">
            <div style="max-height: 65vh; overflow-y: auto">
                <x-ui.list-group>
                    @forelse ($roles as $role)
                        <x-ui.list-item :selected="$role['id'] === $selectedRole" wClickMethod="selectRole" wClickParam="{{ $role['id'] }}">
                            <x-slot:slotLeft>
                                <div class="@if (!$role['active']) text-muted text-decoration-line-through @endif">{{ $role['key'] }}</div>
                                <small class="text-muted">{{ $role['name'] }}</small>
                            </x-slot:slotLeft>
                            <x-slot:slotRight>
                                <small class="text-muted" title="{{ translator('Permissions') }} / {{ translator('Users') }}">
                                    <i class="bi bi-key"></i> {{ $role['permissions_count'] }}
                                    <i class="bi bi-people ms-1"></i> {{ $role['users_count'] }}
                                </small>
                            </x-slot:slotRight>
                        </x-ui.list-item>
                    @empty
                        <div class="text-center py-3"><i>{{ translator('No roles yet!') }}</i></div>
                    @endforelse
                </x-ui.list-group>
            </div>
        </x-ui.card>
    </div>

    {{-- Selected role --}}
    <div class="col-md d-flex flex-column">
        <x-ui.card title="{{ translator('Role settings') }}" class="flex-fill d-flex flex-column" loading="selectRole, setGroupPermissions">
            @if ($selectedRole)
                <div class="row g-2 align-items-end">
                    <div class="col-md-3"><x-ui.input size="sm" label="{{ translator('Key') }}" wModel="roleForm.key" :disabled=TRUE /></div>
                    <div class="col-md-3"><x-ui.input size="sm" label="{{ translator('Name') }}" wModel="roleForm.name" /></div>
                    <div class="col-md"><x-ui.input size="sm" label="{{ translator('Description') }}" wModel="roleForm.description" /></div>
                    <div class="col-md-auto">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" id="aclRoleActive" wire:model.live="roleForm.active">
                            <label class="form-check-label" for="aclRoleActive">{{ translator('Active') }}</label>
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <x-ui.btn type="dan.sm" icon="trash" action="deleteRole" title="{{ translator('Delete role') }}"
                            wire:confirm="{{ translator('Delete the role? Users lose the permissions they got from it.') }}" />
                    </div>
                </div>
                <hr>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <b>{{ translator('Permissions') }}</b>
                    <div class="ms-auto" style="width: 250px">
                        <x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="permissionSearch" wModelEvent="live.debounce.250ms" />
                    </div>
                </div>
                <div style="max-height: 55vh; overflow-y: auto">
                    @forelse ($catalog as $group => $permissions)
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <b class="small text-uppercase">{{ $group }}</b>
                            <button type="button" class="btn btn-link btn-sm p-0" wire:click="setGroupPermissions(@js($group), true)">{{ translator('all') }}</button>
                            <button type="button" class="btn btn-link btn-sm p-0" wire:click="setGroupPermissions(@js($group), false)">{{ translator('none') }}</button>
                        </div>
                        <ul class="list-group mt-1">
                            @foreach ($permissions as $permissionId => $permission)
                                <li class="list-group-item d-flex align-items-center gap-2 py-1" wire:key="role-perm-{{ $permissionId }}">
                                    <input class="form-check-input mt-0" type="checkbox" id="rp{{ $permissionId }}" wire:model.live="rolePermissions.{{ $permissionId }}">
                                    <label class="form-check-label flex-fill" for="rp{{ $permissionId }}">{{ $permission['name'] }}</label>
                                    <code class="small">{{ $permission['key'] }}</code>
                                </li>
                            @endforeach
                        </ul>
                    @empty
                        <div class="text-center py-3"><i>{{ translator('No permissions found. Create them in the Permissions tab.') }}</i></div>
                    @endforelse
                </div>
            @else
                <div class="d-flex justify-content-center"><div class="py-3"><i>{{ translator('No role selected!') }}</i></div></div>
            @endif
        </x-ui.card>
    </div>
</div>
