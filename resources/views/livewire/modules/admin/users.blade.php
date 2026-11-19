<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <style>
        .us-root .us-row { cursor: pointer; }
        .us-root .us-row.us-selected > td { background-color: var(--bs-primary-bg-subtle); }
        .us-root .us-sort { cursor: pointer; user-select: none; white-space: nowrap; }

    </style>

    <div class="us-root d-flex flex-column flex-fill">
        {{-- Toolbar --}}
        <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
            <b>{{ translator('Users') }}</b>
            <span class="badge text-bg-success no-border-radius" title="{{ translator('Active') }}">{{ $counts['active'] }}</span>
            <span class="badge text-bg-secondary no-border-radius" title="{{ translator('Inactive') }}">{{ $counts['inactive'] }}</span>
            <x-v-divider class="align-self-stretch" />

            <div style="width: 280px">
                <x-ui.input size="sm" placeholder="{{ translator('Search the name or the email') }}" wModel="search" wModelEvent="live.debounce.300ms" />
            </div>
            <select class="form-select form-select-sm no-border-radius" style="width: auto" wire:model.live="status" title="{{ translator('Status') }}">
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm no-border-radius" style="width: auto" wire:model.live="type" title="{{ translator('User type') }}">
                <option value="">{{ translator('All types') }}</option>
                @foreach ($typeLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm no-border-radius" style="width: auto" wire:model.live="link" title="{{ translator('Linked worker') }}">
                @foreach ($links as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @if ($search !== '' || $status !== 'active' || $type !== '' || $link !== '')
                <x-ui.btn type="lig.sm" icon="x-lg" action="resetFilters" title="{{ translator('Reset the filters') }}" />
            @endif

            <div class="ms-auto d-flex align-items-center gap-2">
                @if ($can['acl'])
                    <x-ui.btn type="lig.sm" icon="shield-lock" text="{{ translator('Access control') }}" link="getAclModule" />
                @endif
                @if ($can['create'])
                    <x-ui.btn type="pri.sm" icon="person-plus" text="{{ translator('New user') }}" action="newUser" />
                @endif
            </div>
        </div>

        <div class="d-flex flex-fill gap-3" style="min-height: 0">
            {{-- Users table --}}
            <div class="flex-fill d-flex flex-column" style="min-width: 0">
                <div class="flex-fill border" style="overflow: auto; max-height: calc(100vh - 290px)">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="sticky-top bg-white">
                            <tr>
                                @foreach (['name' => 'Name', 'email' => 'Email', 'type' => 'User type'] as $column => $label)
                                    <th class="us-sort" wire:click="sortBy('{{ $column }}')">
                                        {{ translator($label) }}
                                        @if ($sort === $column)<i class="bi bi-caret-{{ $dir === 'asc' ? 'up' : 'down' }}-fill small"></i>@endif
                                    </th>
                                @endforeach
                                <th>{{ translator('Linked worker') }}</th>
                                <th>{{ translator('Roles') }}</th>
                                <th class="us-sort" wire:click="sortBy('last_login_at')">
                                    {{ translator('Last login') }}
                                    @if ($sort === 'last_login_at')<i class="bi bi-caret-{{ $dir === 'asc' ? 'up' : 'down' }}-fill small"></i>@endif
                                </th>
                                <th>{{ translator('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr wire:key="us-{{ $user->id }}" wire:click="openUser({{ $user->id }})" title="{{ translator('Open the user') }}"
                                    @class(['us-row', 'us-selected' => (string) $openedId === (string) $user->id, 'text-muted' => !$user->active])>
                                    <td>
                                        {{ $user->name }} {{ $user->last_name }}
                                        @if ($user->id === auth()->id()) <span class="badge text-bg-light border no-border-radius">{{ translator('You') }}</span> @endif
                                    </td>
                                    <td class="small">{{ $user->email }}</td>
                                    <td class="small">{{ $typeLabels[$user->type] ?? $user->type }}</td>
                                    <td class="small">
                                        @if ($user->worker_id)
                                            <i class="bi bi-person-badge" title="{{ translator('Worker') }}"></i> {{ $user->worker_name ?: '#' . $user->worker_id }}
                                        @elseif ($user->coop_id)
                                            <i class="bi bi-building" title="{{ translator('Subcontractor') }}"></i> {{ $user->coop_name ?: '#' . $user->coop_id }}
                                            @if ($user->coop_company)<span class="text-muted">({{ $user->coop_company }})</span>@endif
                                        @endif
                                    </td>
                                    <td class="small">{{ implode(', ', $roleNames[$user->id] ?? []) }}</td>
                                    <td class="small text-nowrap" title="{{ $user->last_login_at?->format('d.m.Y H:i:s') }}">
                                        {{ $user->last_login_at ? $user->last_login_at->format('d.m.Y H:i') : '–' }}
                                    </td>
                                    <td>
                                        @if ($user->active)
                                            <span class="badge text-bg-success no-border-radius">{{ translator('Active') }}</span>
                                        @else
                                            <span class="badge text-bg-secondary no-border-radius">{{ translator('Inactive') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4"><i>{{ translator('No users match the filters.') }}</i></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex align-items-center flex-wrap gap-2 pt-2">
                    <span class="small text-muted">
                        @if ($users->total())
                            {{ $users->firstItem() }}–{{ $users->lastItem() }} / {{ $users->total() }}
                        @else
                            0
                        @endif
                    </span>
                    <select class="form-select form-select-sm no-border-radius" style="width: auto" wire:model.live="perPage" title="{{ translator('Rows per page') }}">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                    <div class="ms-auto">
                        {{ $users->onEachSide(1)->links('livewire.partials.pagination') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
