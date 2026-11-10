<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="h-100 d-flex flex-column">

        <div class="alert alert-warning py-2 no-border-radius mb-2">
            <i class="bi bi-cone-striped"></i>
            <b>{{ translator('Prototype') }}:</b>
            {{ translator('hardcoded examples, nothing is saved to the database. Every field has a number') }}
            (<span class="badge text-bg-secondary">#1</span>)
            {{ translator('so you can pick which ones to keep.') }}
        </div>

        {{-- Per company customization: which profile (JSON) is applied --}}
        <div class="d-flex align-items-center gap-2 mb-2">
            <b class="small text-nowrap">{{ translator('Field configuration') }}</b>
            <select class="form-select form-select-sm no-border-radius" style="width: 380px" wire:model.live="profile">
                @foreach ($profiles as $value => $label) <option value="{{ $value }}">{{ translator($label) }}</option> @endforeach
            </select>
            <small class="text-muted text-nowrap">{{ $fieldCount }} {{ translator('fields') }}, {{ count($tabs) }} {{ translator('tabs') }}</small>
            <x-ui.btn type="lig.sm" icon="braces" action="toggleConfig" text="{{ $showConfig ? translator('Hide JSON') : translator('Show JSON') }}" />
        </div>
        @if ($showConfig)
            <div class="border bg-light p-2 mb-2">
                @if ($profile === 'company')
                    <div class="small mb-1">{{ translator('Company') }}: <b>{{ $company }}</b> &middot; {{ translator('File') }}: <code>{{ $companyFile }}</code></div>
                @endif
                @if ($configJson === NULL)
                    <div class="small text-muted">
                        {{ translator('This company has no customization file (or it is empty), so every field is shown. To customize, create the file, e.g.:') }}
                    </div>
<pre class="small mb-0 mt-1">{ "only": ["department", "description"], "tabs": { "workers": false } }</pre>
                @else
<pre class="small mb-0" style="max-height: 260px; overflow: auto">{{ $configJson }}</pre>
                @endif
                @if (!empty($warnings))
                    <div class="small text-danger mt-2">
                        <b>{{ translator('Ignored in the config') }}:</b>
                        <ul class="mb-0">@foreach ($warnings as $warning) <li>{{ $warning }}</li> @endforeach</ul>
                    </div>
                @endif
            </div>
        @endif

        <div class="row flex-fill g-0 gap-3">
            {{-- Workplaces list --}}
            <div class="col-md-3 d-flex flex-column">
                <x-ui.card title="{{ translator('Workplaces (contracts)') }}" class="flex-fill d-flex flex-column">
                    <div class="d-flex gap-1 mb-2">
                        <x-ui.input size="sm" placeholder="{{ translator('Name') }}" wModel="newWorkplace.name" />
                        <x-ui.input size="sm" placeholder="{{ translator('KEY') }}" wModel="newWorkplace.key" />
                        <x-ui.btn type="suc.sm" icon="add" action="createWorkplace" title="{{ translator('Create workplace') }}" />
                    </div>
                    <x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="workplaceSearch" wModelEvent="live.debounce.250ms" :removeAddOnXP=TRUE>
                        @if ($workplaceSearch)
                            <x-slot:append><x-ui.btn type="lig.sm" icon="trash" action="resetWorkplaceSearch" /></x-slot:append>
                        @endif
                    </x-ui.input>
                    <hr class="m-0 my-2">
                    <div style="max-height: 65vh; overflow-y: auto">
                        <x-ui.list-group>
                            @forelse ($list as $key => $workplace)
                                <x-ui.list-item :selected="$key === $selectedWorkplace" wClickMethod="selectWorkplace" wClickParam="{{ $key }}">
                                    <x-slot:slotLeft>
                                        <div class="@if (!$workplace['active']) text-muted text-decoration-line-through @endif">{{ $workplace['key'] }}</div>
                                        <small class="text-muted">{{ $workplace['name'] }}</small>
                                    </x-slot:slotLeft>
                                    <x-slot:slotRight>
                                        @php $filled = count($workplace['workers']); $planned = (int) $workplace['headcount']; @endphp
                                        <small class="@if ($planned && $filled < $planned) text-warning @else text-muted @endif" title="{{ translator('Workers') }} / {{ translator('Planned headcount') }}">
                                            <i class="bi bi-people"></i> {{ $filled }} / {{ $planned }}
                                        </small>
                                    </x-slot:slotRight>
                                </x-ui.list-item>
                            @empty
                                <div class="text-center py-3"><i>{{ translator('No workplaces found!') }}</i></div>
                            @endforelse
                        </x-ui.list-group>
                    </div>
                </x-ui.card>
            </div>

            {{-- Selected workplace --}}
            <div class="col-md d-flex flex-column">
                <x-ui.card class="flex-fill d-flex flex-column" headerHight=48 loading="selectWorkplace, selectTab">
                    <x-slot:title>
                        <div class="d-flex gap-2 align-items-center">
                            <div>{{ translator('Workplace settings') }}@if ($selected): <span class="fw-bold">{{ $selected['key'] }} - {{ $selected['name'] }}</span>@endif</div>
                            <x-v-divider class="align-self-stretch" />
                            <x-ui.nav-tabs :tabs=$tabs :selectedTab=$activeTab py=1 />
                        </div>
                    </x-slot:title>

                    @if ($selected)
                        @php $base = 'workplaces.' . $selectedWorkplace; @endphp

                        @if ($activeTab === 'workers')
                            {{-- Workers on this workplace --}}
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge text-bg-secondary">#32</span>
                                <b>{{ translator('Workers on this workplace') }}</b>
                                <small class="text-muted">{{ count($selected['workers']) }} / {{ (int) $selected['headcount'] }} {{ translator('planned') }}</small>
                            </div>
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 70px">#</th>
                                        <th>{{ translator('Worker') }}</th>
                                        <th>{{ translator('On this workplace since') }}</th>
                                        <th>{{ translator('Contract') }}</th>
                                        <th>{{ translator('Contract ends') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($selected['workers'] as $worker)
                                        <tr>
                                            <td>{{ str_pad($worker['id'], 3, '0', STR_PAD_LEFT) }}</td>
                                            <td>{{ $worker['name'] }}</td>
                                            <td>{{ date('d.m.Y', strtotime($worker['since'])) }}</td>
                                            <td>{{ $worker['contract'] }}</td>
                                            <td>
                                                @if ($worker['contract'] === 'Određeno' && $selected['duration'])
                                                    {{ date('d.m.Y', strtotime($worker['since'] . ' +' . (int) $selected['duration'] . ' months')) }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center py-3"><i>{{ translator('No workers on this workplace.') }}</i></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <small class="text-muted">{{ translator('Idea: workers are given a workplace in Worker info, this tab only lists them. "Contract ends" = start + default duration.') }}</small>
                        @else
                            {{-- Fields of the selected tab --}}
                            <div class="row g-3">
                                @foreach ($fields[$activeTab] ?? [] as $field => $definition)
                                    @php
                                        $no = $definition['no'];
                                        $label = $definition['label'];
                                        $type = $definition['type'];
                                        $extra = $definition['options'];
                                        $help = $definition['help'];
                                        $model = $base . '.' . $field;
                                        $wide = in_array($type, ['textarea', 'checks'], TRUE);
                                        $options = $extra === 'workplaces'
                                            ? array_diff_key($workplaceOptions, [$selectedWorkplace => NULL])
                                            : (is_array($extra) ? array_combine($extra, $extra) : []);
                                    @endphp
                                    <div class="{{ $wide ? 'col-12' : 'col-md-4' }}" wire:key="wp-field-{{ $selectedWorkplace }}-{{ $field }}">
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <span class="badge {{ $definition['custom'] ? 'text-bg-info' : 'text-bg-secondary' }}" title="{{ $definition['custom'] ? translator('Own field of this company') : '' }}">#{{ $no }}@if ($definition['custom']) {{ translator('custom') }}@endif</span>
                                            <label class="small fw-semibold" for="wp-{{ $field }}">{{ translator($label) }}@if ($definition['required'])<span class="text-danger">*</span>@endif</label>
                                            @if ($help) <x-ui.tool-tips :message="translator($help)" /> @endif
                                        </div>

                                        @switch ($type)
                                            @case ('textarea')
                                                <textarea id="wp-{{ $field }}" class="form-control form-control-sm no-border-radius" rows="3" wire:model.blur="{{ $model }}"></textarea>
                                                @break
                                            @case ('select')
                                                <select id="wp-{{ $field }}" class="form-select form-select-sm no-border-radius" wire:model.change="{{ $model }}">
                                                    <option value="">—</option>
                                                    @foreach ($options as $value => $optionLabel) <option value="{{ $value }}">{{ $optionLabel }}</option> @endforeach
                                                </select>
                                                @break
                                            @case ('switch')
                                                <div class="form-check form-switch">
                                                    <input id="wp-{{ $field }}" class="form-check-input" type="checkbox" wire:model.live="{{ $model }}">
                                                </div>
                                                @break
                                            @case ('checks')
                                                <div class="d-flex flex-wrap gap-3 border px-2 py-1">
                                                    @foreach ($options as $value => $optionLabel)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="wp-{{ $field }}-{{ $loop->index }}" value="{{ $value }}" wire:model.live="{{ $model }}">
                                                            <label class="form-check-label small" for="wp-{{ $field }}-{{ $loop->index }}">{{ $optionLabel }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @break
                                            @case ('range')
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text no-border-radius">{{ translator('min') }}</span>
                                                    <input type="number" step="0.01" min="0" class="form-control no-border-radius" wire:model.blur="{{ $model }}.min">
                                                    <span class="input-group-text no-border-radius">{{ translator('max') }}</span>
                                                    <input type="number" step="0.01" min="0" class="form-control no-border-radius" wire:model.blur="{{ $model }}.max">
                                                </div>
                                                @break
                                            @default
                                                <input id="wp-{{ $field }}" type="{{ $type }}" @if ($type === 'number') step="any" min="0" @endif
                                                    class="form-control form-control-sm no-border-radius" wire:model.blur="{{ $model }}"
                                                    @if ($field === 'key') disabled @endif>
                                        @endswitch
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="d-flex justify-content-center"><div class="py-3"><i>{{ translator('No workplace selected!') }}</i></div></div>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </x-ui.card>
</div>
