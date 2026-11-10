<div class="d-flex flex-column gap-3">
    {{-- New permission --}}
    <x-ui.card title="{{ translator('New permission') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-2"><x-ui.input size="sm" label="{{ translator('Key') }}" placeholder="module.page.action" wModel="newPermission.key" /></div>
            <div class="col-md-2"><x-ui.input size="sm" label="{{ translator('Name') }}" wModel="newPermission.name" /></div>
            <div class="col-md-2"><x-ui.input size="sm" label="{{ translator('Description') }}" wModel="newPermission.description" /></div>
            <div class="col-md-2"><x-ui.select label="{{ translator('Module') }}" :options=$moduleOptions wModel="newPermission.module_id" class="form-select-sm" /></div>
            <div class="col-md"><x-ui.select label="{{ translator('Guards route') }}" :options=$routeOptions wModel="newPermission.route_id" class="form-select-sm"
                tooltip="{{ translator('Optional. Users without this permission can not open the route and do not see its tab.') }}" /></div>
            <div class="col-md-auto"><x-ui.btn type="suc.sm" icon="save" action="createPermission" title="{{ translator('Create permission') }}" /></div>
        </div>
    </x-ui.card>

    {{-- All permissions --}}
    <x-ui.card title="{{ translator('Permissions') }}" loading="deletePermission">
        <x-slot:headerActions>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm no-border-radius" wire:model.live="moduleFilter" style="width: 200px">
                    <option value="">{{ translator('All modules') }}</option>
                    <option value="-">{{ translator('General') }}</option>
                    @foreach ($moduleOptions as $id => $name)
                        @if ($id !== '') <option value="{{ $id }}">{{ $name }}</option> @endif
                    @endforeach
                </select>
                <div style="width: 250px"><x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="permissionSearch" wModelEvent="live.debounce.250ms" /></div>
            </div>
        </x-slot:headerActions>

        <div style="max-height: 60vh; overflow-y: auto">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="sticky-top bg-white">
                    <tr>
                        <th>{{ translator('Key') }}</th>
                        <th>{{ translator('Name') }}</th>
                        <th>{{ translator('Description') }}</th>
                        <th>{{ translator('Module') }}</th>
                        <th>{{ translator('Guards route') }}</th>
                        <th class="text-center">{{ translator('Roles') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($permissions as $permissionId => $permission)
                        <tr wire:key="perm-{{ $permissionId }}">
                            <td><code>{{ $permission['key'] }}</code></td>
                            <td><input type="text" class="form-control form-control-sm no-border-radius" wire:model.blur="permissions.{{ $permissionId }}.name"></td>
                            <td><input type="text" class="form-control form-control-sm no-border-radius" wire:model.blur="permissions.{{ $permissionId }}.description"></td>
                            <td>
                                <select class="form-select form-select-sm no-border-radius" wire:model.change="permissions.{{ $permissionId }}.module_id">
                                    @foreach ($moduleOptions as $id => $name) <option value="{{ $id }}">{{ $name }}</option> @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="form-select form-select-sm no-border-radius" wire:model.change="permissions.{{ $permissionId }}.route_id">
                                    @foreach ($routeOptions as $id => $label) <option value="{{ $id }}">{{ $label }}</option> @endforeach
                                </select>
                            </td>
                            <td class="text-center">{{ $permission['roles_count'] }}</td>
                            <td class="text-end">
                                <x-ui.btn type="dan.sm" icon="trash" action="deletePermission" param="{{ $permissionId }}"
                                    wire:confirm="{{ translator('Delete the permission? It is removed from every role and user.') }}" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-3"><i>{{ translator('No permissions found!') }}</i></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
