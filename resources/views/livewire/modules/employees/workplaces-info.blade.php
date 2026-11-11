<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="h-100 d-flex flex-column">

        {{-- Customization of the company (customize/{company}/workplace_fields.json) --}}
        @if ($showConfig)
            <div class="border bg-light p-2 mb-2">
                <div class="small mb-1">
                    {{ translator('Company') }}: <b>{{ $company }}</b> &middot; {{ translator('File') }}: <code>{{ $companyFile }}</code>
                    &middot; {{ $fieldCount }} {{ translator('fields') }}, {{ count($tabs) }} {{ translator('tabs') }}
                </div>
                @if ($configJson === NULL)
                    <div class="small text-muted">{{ translator('No customization: every field is shown. Format and examples: customize/README.md') }}</div>
                @else
<pre class="small mb-0" style="max-height: 260px; overflow: auto">{{ $configJson }}</pre>
                @endif
            </div>
        @endif
        @if (!empty($warnings))
            <div class="alert alert-danger py-2 no-border-radius mb-2 small">
                <b>{{ translator('Ignored in the customization') }} ({{ $companyFile }}):</b>
                <ul class="mb-0">@foreach ($warnings as $warning) <li>{{ $warning }}</li> @endforeach</ul>
            </div>
        @endif

        <div class="row flex-fill g-0 gap-3">
            {{-- Workplaces list --}}
            <div class="col-md-3 d-flex flex-column">
                <x-ui.card title="{{ translator('Workplaces (contracts)') }}" class="flex-fill d-flex flex-column">
                    <x-slot:headerActions>
                        <x-ui.btn type="lig.sm" icon="braces" action="toggleConfig" title="{{ translator('Customization of the fields') }}" />
                    </x-slot:headerActions>
                    @if ($canEdit)
                        <div class="d-flex gap-1 mb-2">
                            <x-ui.input size="sm" placeholder="{{ translator('Name') }}" wModel="newWorkplace.name" />
                            <x-ui.input size="sm" placeholder="{{ translator('KEY') }}" wModel="newWorkplace.key" />
                            <x-ui.btn type="suc.sm" icon="add" action="createWorkplace" title="{{ translator('Create workplace') }}" />
                        </div>
                    @endif
                    <x-ui.input size="sm" placeholder="{{ translator('Search') }}" wModel="workplaceSearch" wModelEvent="live.debounce.250ms" :removeAddOnXP=TRUE>
                        @if ($workplaceSearch)
                            <x-slot:append><x-ui.btn type="lig.sm" icon="trash" action="resetWorkplaceSearch" /></x-slot:append>
                        @endif
                    </x-ui.input>
                    <hr class="m-0 my-2">
                    <div style="max-height: 65vh; overflow-y: auto">
                        <x-ui.list-group>
                            @forelse ($list as $workplace)
                                <x-ui.list-item :selected="$workplace['id'] == $selectedWorkplace" wClickMethod="selectWorkplace" wClickParam="{{ $workplace['id'] }}">
                                    <x-slot:slotLeft>
                                        <div class="@if (!$workplace['status']) text-muted text-decoration-line-through @endif">{{ $workplace['key'] }}</div>
                                        <small class="text-muted">{{ $workplace['name'] }}</small>
                                    </x-slot:slotLeft>
                                    <x-slot:slotRight>
                                        @php $filled = (int) $workplace['workers_count']; $planned = (int) $workplace['headcount']; @endphp
                                        <small class="text-nowrap @if ($planned && $filled < $planned) text-warning @else text-muted @endif"
                                            title="{{ translator('Employed workers') }}{{ $planned ? ' / ' . translator('Planned headcount') : '' }}">
                                            <i class="bi bi-people"></i> {{ $filled }}@if ($planned) / {{ $planned }}@endif
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
                <x-ui.card class="flex-fill d-flex flex-column" headerHight=48 loading="selectWorkplace, selectTab, deleteWorkplace">
                    <x-slot:title>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="text-nowrap">{{ translator('Workplace settings') }}@if ($selected): <span class="fw-bold">{{ $selected['key'] }} - {{ $selected['name'] }}</span>@endif</div>
                            <x-v-divider class="align-self-stretch" />
                            <x-ui.nav-tabs :tabs=$tabs :selectedTab=$activeTab py=1 />
                        </div>
                    </x-slot:title>
                    @if (($selected && $canEdit) || $isSuperAdmin)
                        <x-slot:headerActions>
                            <div class="d-flex gap-1">
                                @if ($isSuperAdmin)
                                    <div title="{{ translator('Customize the workplace fields (super admin)') }}">
                                        <x-ui.modal.modal-open-btn btnType="lig.sm" icon="gear" targetComponent="workplace-fields-configurator" />
                                    </div>
                                @endif
                                @if ($selected && $canEdit)
                                    <x-ui.btn type="dan.sm" icon="trash" action="deleteWorkplace" title="{{ translator('Delete workplace') }}"
                                        wire:confirm="{{ translator('Delete the workplace? Only possible when no worker is on it, otherwise switch it off.') }}" />
                                @endif
                            </div>
                        </x-slot:headerActions>
                    @endif

                    @if ($selected)
                        @if ($activeTab === 'workers')
                            {{-- Workers on this workplace --}}
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <b>{{ translator('Workers on this workplace') }}</b>
                                <small class="text-muted">
                                    {{ collect($workers)->where('employed', TRUE)->count() }} {{ translator('employed') }}@if ($selected['headcount']) / {{ $selected['headcount'] }} {{ translator('planned') }}@endif
                                </small>
                            </div>
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 70px">#</th>
                                        <th>{{ translator('Worker') }}</th>
                                        <th>{{ translator('Status') }}</th>
                                        <th>{{ translator('Date of employment') }}</th>
                                        <th>{{ translator('Contract expiration') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($workers as $worker)
                                        @php
                                            $ends = $worker['ced'] ? strtotime($worker['ced']) : NULL;
                                            $endsSoon = $ends && $worker['employed'] && $ends <= strtotime('+30 days');
                                        @endphp
                                        <tr class="@if (!$worker['employed']) text-muted @endif">
                                            <td>{{ str_pad($worker['id'], 3, '0', STR_PAD_LEFT) }}</td>
                                            <td>{{ $worker['name'] }}</td>
                                            <td>{{ $worker['status'] }}</td>
                                            <td>{{ $worker['doe'] ? date('d.m.Y', strtotime($worker['doe'])) : '—' }}</td>
                                            <td class="@if ($endsSoon) text-danger fw-bold @endif" @if ($endsSoon) title="{{ translator('Ends within 30 days') }}" @endif>
                                                {{ $ends ? date('d.m.Y', $ends) : '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center py-3"><i>{{ translator('No workers on this workplace.') }}</i></td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <small class="text-muted">{{ translator('Workers get their workplace in Employees - Worker info.') }}</small>
                        @else
                            {{-- Fields of the selected tab --}}
                            <div class="row g-3">
                                @foreach ($fields[$activeTab] ?? [] as $field => $definition)
                                    @php
                                        $type = $definition['type'];
                                        $model = 'form.' . $field;
                                        $wide = in_array($type, ['textarea', 'checks'], TRUE);
                                        $options = $definition['options'] === 'workplaces'
                                            ? $reportsToOptions
                                            : (is_array($definition['options']) ? array_combine($definition['options'], $definition['options']) : []);
                                        $state = $saved[$model] ?? NULL;
                                        $stateClass = $state === TRUE ? 'is-valid' : ($state === FALSE ? 'is-invalid' : '');
                                    @endphp
                                    <div class="{{ $wide ? 'col-12' : 'col-md-4' }}" wire:key="wp-field-{{ $selectedWorkplace }}-{{ $field }}">
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <label class="small fw-semibold" for="wp-{{ $field }}">{{ translator($definition['label']) }}@if ($definition['required'])<span class="text-danger">*</span>@endif</label>
                                            @if ($definition['help']) <x-ui.tool-tips :message="translator($definition['help'])" /> @endif
                                        </div>

                                        @switch ($type)
                                            @case ('textarea')
                                                <textarea id="wp-{{ $field }}" class="form-control form-control-sm no-border-radius {{ $stateClass }}" rows="3" wire:model.blur="{{ $model }}" @disabled(!$canEdit) @if ($state !== NULL) data-flash-validation @endif></textarea>
                                                @break
                                            @case ('select')
                                                <select id="wp-{{ $field }}" class="form-select form-select-sm no-border-radius {{ $stateClass }}" wire:model.change="{{ $model }}" @disabled(!$canEdit) @if ($state !== NULL) data-flash-validation @endif>
                                                    <option value="">—</option>
                                                    @foreach ($options as $value => $optionLabel) <option value="{{ $value }}">{{ $optionLabel }}</option> @endforeach
                                                    {{-- A saved value the company removed from the options still shows --}}
                                                    @if (($form[$field] ?? '') !== '' && $form[$field] !== NULL && !array_key_exists($form[$field], $options))
                                                        <option value="{{ $form[$field] }}">{{ $form[$field] }}</option>
                                                    @endif
                                                </select>
                                                @break
                                            @case ('switch')
                                                <div class="form-check form-switch">
                                                    <input id="wp-{{ $field }}" class="form-check-input {{ $stateClass }}" type="checkbox" wire:model.live="{{ $model }}" @disabled(!$canEdit) @if ($state !== NULL) data-flash-validation @endif>
                                                </div>
                                                @break
                                            @case ('checks')
                                                @php $checkOptions = array_unique(array_merge(array_keys($options), (array) ($form[$field] ?? []))); @endphp
                                                <div class="d-flex flex-wrap gap-3 border px-2 py-1 {{ $state === TRUE ? 'border-success' : ($state === FALSE ? 'border-danger' : '') }}">
                                                    @foreach ($checkOptions as $optionValue)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="wp-{{ $field }}-{{ $loop->index }}" value="{{ $optionValue }}" wire:model.live="{{ $model }}" @disabled(!$canEdit)>
                                                            <label class="form-check-label small" for="wp-{{ $field }}-{{ $loop->index }}">{{ $optionValue }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @break
                                            @case ('range')
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text no-border-radius">{{ translator('min') }}</span>
                                                    <input type="number" step="0.01" min="0" class="form-control no-border-radius {{ $stateClass }}" wire:model.blur="{{ $model }}.min" @disabled(!$canEdit)>
                                                    <span class="input-group-text no-border-radius">{{ translator('max') }}</span>
                                                    <input type="number" step="0.01" min="0" class="form-control no-border-radius {{ $stateClass }}" wire:model.blur="{{ $model }}.max" @disabled(!$canEdit)>
                                                </div>
                                                @break
                                            @default
                                                <input id="wp-{{ $field }}" type="{{ $type }}" @if ($type === 'number') step="any" min="0" @endif
                                                    class="form-control form-control-sm no-border-radius {{ $stateClass }}" wire:model.blur="{{ $model }}"
                                                    @disabled(!$canEdit) @if ($state !== NULL) data-flash-validation @endif>
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
