{{-- Shown in the global modal (config/global-modal.php: workplace-fields-configurator) --}}
<div>
        @if (!$allowed)
            <div class="text-center py-3"><i>{{ translator('Only a super admin can customize the workplace fields!') }}</i></div>
        @else
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="small text-muted">
                    {{ translator('Company') }}: <b>{{ $company }}</b> &middot; {{ translator('File') }}: <code>{{ $companyFile }}</code>
                    &middot; {{ $fieldCount }} {{ translator('fields') }}
                </div>
                <div class="ms-auto d-flex align-items-center gap-2">
                    @if ($dirty) <small class="text-warning fw-semibold">{{ translator('Not saved') }}</small> @endif
                    <x-ui.btn type="lig.sm" icon="arrow-counterclockwise" wClickMethod="loadDraft" title="{{ translator('Discard the changes (load the file again)') }}" />
                    <x-ui.btn type="suc.sm" icon="save" wClickMethod="save" text="{{ translator('Save') }}" :disabled="$loadError !== NULL" />
                </div>
            </div>
            <hr>
            <x-ui.card :border=FALSE :noBodyPadding=TRUE loading="selectTab, selectPreviewTab, save, loadDraft, addCustomField, removeCustomField, resetOptions">
                <div class="mb-2">
                    <x-ui.nav-tabs :tabs=$tabs :selectedTab=$activeTab py=1 />
                </div>

                @if ($loadError)
                    <div class="alert alert-danger py-2 no-border-radius mb-2 small">
                        <b>{{ translator('The file can not be read, saving is blocked') }}:</b> {{ $loadError }}
                    </div>
                @endif
                @if (!empty($warnings))
                    <div class="alert alert-warning py-2 no-border-radius mb-2 small">
                        <b>{{ translator('Ignored in the customization') }}:</b>
                        <ul class="mb-0">@foreach ($warnings as $warning) <li>{{ $warning }}</li> @endforeach</ul>
                    </div>
                @endif

                <div style="max-height: 68vh; overflow-y: auto; overflow-x: hidden">
                    @if ($activeTab === 'configurator')
                        {{-- ============ CONFIGURATOR ============ --}}
                        <div class="row g-3">
                            <div class="col-lg-8">
                                {{-- Tabs --}}
                                <h6 class="fw-bold mb-1">{{ translator('Tabs') }}</h6>
                                <table class="table table-sm align-middle mb-3">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px">{{ translator('Visible') }}</th>
                                            <th style="width: 160px">{{ translator('Tab') }}</th>
                                            <th>{{ translator('Label') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tabRows as $tab => $row)
                                            <tr wire:key="cfg-tab-{{ $tab }}" class="@if (!$row['visible']) text-muted @endif">
                                                <td>
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" wire:model.live="tabRows.{{ $tab }}.visible" @disabled($tab === 'basic')
                                                            @if ($tab === 'basic') title="{{ translator('Holds the core fields, can not be hidden') }}" @endif>
                                                    </div>
                                                </td>
                                                <td><code>{{ $tab }}</code></td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm no-border-radius" placeholder="{{ $allTabs[$tab] }}" wire:model.blur="tabRows.{{ $tab }}.label">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                {{-- Catalog fields --}}
                                <h6 class="fw-bold mb-1">{{ translator('Fields') }}</h6>
                                <table class="table table-sm align-middle mb-3">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px">{{ translator('Visible') }}</th>
                                            <th style="width: 160px">{{ translator('Field') }}</th>
                                            <th style="width: 80px">{{ translator('Type') }}</th>
                                            <th>{{ translator('Label') }}</th>
                                            <th style="width: 150px">{{ translator('Tab') }}</th>
                                            <th style="width: 70px" class="text-center">{{ translator('Required') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($fieldRows as $field => $row)
                                            @php $isCore = in_array($field, $coreFields, TRUE); @endphp
                                            <tr wire:key="cfg-field-{{ $field }}" class="@if (!$row['visible']) text-muted @endif">
                                                <td>
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" wire:model.live="fieldRows.{{ $field }}.visible" @disabled($isCore)
                                                            @if ($isCore) title="{{ translator('Core field, can not be hidden') }}" @endif>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code>{{ $field }}</code>
                                                    @if ($isCore) <span class="badge bg-secondary no-border-radius">{{ translator('core') }}</span> @endif
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $catalog[$field][2] }}</small>
                                                    @if ($row['options'] !== NULL)
                                                        @php
                                                            $optionCount = $row['options'] === '' ? 0 : substr_count($row['options'], "\n") + 1;
                                                            $changed = $row['options'] !== implode("\n", $catalog[$field][4]);
                                                        @endphp
                                                        <button type="button" class="btn btn-sm py-0 px-1 no-border-radius d-block mt-1 text-nowrap {{ $optionsOpen === "fieldRows.$field" ? 'btn-primary' : ($optionCount === 0 ? 'btn-outline-danger' : ($changed ? 'btn-outline-primary' : 'btn-light')) }}"
                                                            wire:click="toggleOptions('fieldRows', '{{ $field }}')" @disabled(!$row['visible'])
                                                            title="{{ translator('Options') }}{{ $changed ? ' (' . translator('changed') . ')' : '' }}">
                                                            <i class="bi bi-list-ul"></i> {{ $optionCount }}
                                                        </button>
                                                    @elseif (($catalog[$field][4] ?? NULL) === 'workplaces')
                                                        <small class="d-block text-muted fst-italic" title="{{ translator('The options are the other workplaces') }}"><i class="bi bi-diagram-3"></i> {{ translator('auto') }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm no-border-radius" placeholder="{{ $catalog[$field][3] }}"
                                                        wire:model.blur="fieldRows.{{ $field }}.label" @disabled(!$row['visible'])>
                                                </td>
                                                <td>
                                                    <select class="form-select form-select-sm no-border-radius" wire:model.change="fieldRows.{{ $field }}.tab" @disabled(!$row['visible'])>
                                                        @foreach ($allTabs as $tab => $tabLabel)
                                                            @continue($tab === 'workers')
                                                            <option value="{{ $tab }}">{{ $tabRows[$tab]['label'] ?: $tabLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <input class="form-check-input" type="checkbox" wire:model.live="fieldRows.{{ $field }}.required" @disabled(!$row['visible'])>
                                                </td>
                                            </tr>
                                            @if ($optionsOpen === "fieldRows.$field" && $row['options'] !== NULL && $row['visible'])
                                                @include('livewire.modules.employees.components.partials.workplace-field-options-editor', [
                                                    'rows' => 'fieldRows', 'field' => $field, 'colspan' => 6,
                                                    'defaults' => $catalog[$field][4], 'maxLength' => $optionMaxLengths[$field] ?? $optionMaxLength,
                                                ])
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>

                                {{-- The company's own fields --}}
                                <h6 class="fw-bold mb-1">{{ translator('Own fields of the company') }}</h6>
                                <table class="table table-sm align-middle mb-1">
                                    <thead>
                                        <tr>
                                            <th style="width: 70px">{{ translator('Visible') }}</th>
                                            <th style="width: 160px">{{ translator('Field') }}</th>
                                            <th style="width: 110px">{{ translator('Type') }}</th>
                                            <th>{{ translator('Label') }}</th>
                                            <th style="width: 150px">{{ translator('Tab') }}</th>
                                            <th style="width: 70px" class="text-center">{{ translator('Required') }}</th>
                                            <th style="width: 40px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($customRows as $field => $row)
                                            <tr wire:key="cfg-custom-{{ $field }}" class="@if (!$row['visible']) text-muted @endif">
                                                <td>
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input" type="checkbox" wire:model.live="customRows.{{ $field }}.visible">
                                                    </div>
                                                </td>
                                                <td><code>{{ $field }}</code></td>
                                                <td>
                                                    <select class="form-select form-select-sm no-border-radius" wire:model.change="customRows.{{ $field }}.type">
                                                        @foreach ($fieldTypes as $type) <option value="{{ $type }}">{{ $type }}</option> @endforeach
                                                    </select>
                                                    @if (in_array($row['type'], ['select', 'checks'], TRUE))
                                                        @php $optionCount = trim((string) $row['options']) === '' ? 0 : substr_count($row['options'], "\n") + 1; @endphp
                                                        <button type="button" class="btn btn-sm py-0 px-1 no-border-radius mt-1 text-nowrap {{ $optionsOpen === "customRows.$field" ? 'btn-primary' : ($optionCount === 0 ? 'btn-outline-danger' : 'btn-light') }}"
                                                            wire:click="toggleOptions('customRows', '{{ $field }}')" title="{{ translator('Options') }}">
                                                            <i class="bi bi-list-ul"></i> {{ $optionCount }}
                                                        </button>
                                                    @endif
                                                </td>
                                                <td><input type="text" class="form-control form-control-sm no-border-radius" wire:model.blur="customRows.{{ $field }}.label"></td>
                                                <td>
                                                    <select class="form-select form-select-sm no-border-radius" wire:model.change="customRows.{{ $field }}.tab">
                                                        @foreach ($allTabs as $tab => $tabLabel)
                                                            @continue($tab === 'workers')
                                                            <option value="{{ $tab }}">{{ $tabRows[$tab]['label'] ?: $tabLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="text-center"><input class="form-check-input" type="checkbox" wire:model.live="customRows.{{ $field }}.required"></td>
                                                <td>
                                                    <x-ui.btn type="dan.sm" icon="trash" wClickMethod="removeCustomField" wClickParam="{{ $field }}" title="{{ translator('Remove the field') }}"
                                                        wire:confirm="{{ translator('Remove the field? Values already saved on workplaces stay in the database.') }}" />
                                                </td>
                                            </tr>
                                            @if ($optionsOpen === "customRows.$field" && in_array($row['type'], ['select', 'checks'], TRUE))
                                                @include('livewire.modules.employees.components.partials.workplace-field-options-editor', [
                                                    'rows' => 'customRows', 'field' => $field, 'colspan' => 7, 'defaults' => NULL, 'maxLength' => $optionMaxLength,
                                                ])
                                            @endif
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted py-2"><i>{{ translator('No own fields yet.') }}</i></td></tr>
                                        @endforelse
                                        {{-- New own field --}}
                                        <tr class="table-light">
                                            <td><small class="text-muted">{{ translator('New') }}</small></td>
                                            <td><input type="text" class="form-control form-control-sm no-border-radius" placeholder="{{ translator('key') }}" wire:model.blur="newCustom.key"></td>
                                            <td>
                                                <select class="form-select form-select-sm no-border-radius" wire:model="newCustom.type">
                                                    @foreach ($fieldTypes as $type) <option value="{{ $type }}">{{ $type }}</option> @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control form-control-sm no-border-radius" placeholder="{{ translator('Label') }}" wire:model.blur="newCustom.label"></td>
                                            <td>
                                                <select class="form-select form-select-sm no-border-radius" wire:model="newCustom.tab">
                                                    @foreach ($allTabs as $tab => $tabLabel)
                                                        @continue($tab === 'workers')
                                                        <option value="{{ $tab }}">{{ $tabRows[$tab]['label'] ?: $tabLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td></td>
                                            <td><x-ui.btn type="suc.sm" icon="plus-lg" wClickMethod="addCustomField" title="{{ translator('Add the field') }}" /></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <small class="text-muted">{{ translator('Help texts and the order inside a tab are kept from the file; change them there.') }}</small>
                            </div>

                            {{-- The file that will be written --}}
                            <div class="col-lg-4">
                                <h6 class="fw-bold mb-1">{{ translator('Result') }} <small class="text-muted fw-normal">({{ $companyFile }})</small></h6>
<pre class="small border bg-light p-2 mb-0" style="position: sticky; top: 0; max-height: 64vh; overflow: auto">{{ empty($config) ? '{}  // ' . translator('no customization: every field is shown') : $json }}</pre>
                            </div>
                        </div>
                    @else
                        {{-- ============ PREVIEW ============ --}}
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <ul class="nav nav-tabs no-border-radius flex-fill">
                                @foreach ($previewTabs as $tab => $label)
                                    <li class="nav-item no-border-radius">
                                        <button class="nav-link no-border-radius py-1 @if ($tab === $previewTab) active @endif @if (in_array($tab, $hiddenTabs, TRUE)) text-muted text-decoration-line-through @endif"
                                            wire:click="selectPreviewTab('{{ $tab }}')">{{ translator($label) }}</button>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="form-check form-switch m-0 text-nowrap">
                                <input class="form-check-input" type="checkbox" id="cfg-preview-hidden" wire:model.live="previewShowHidden">
                                <label class="form-check-label small" for="cfg-preview-hidden">{{ translator('Show the hidden tabs and fields') }}</label>
                            </div>
                        </div>

                        @if ($previewTab === 'workers')
                            <table class="table table-sm align-middle @if (in_array('workers', $hiddenTabs, TRUE)) opacity-50 @endif">
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
                                    <tr class="text-muted"><td>001</td><td>{{ translator('Example worker') }}</td><td>—</td><td>01.01.2026</td><td>—</td></tr>
                                </tbody>
                            </table>
                        @else
                            <div class="row g-3 @if (in_array($previewTab, $hiddenTabs, TRUE)) opacity-50 @endif">
                                @forelse ($previewFields[$previewTab] ?? [] as $field => $definition)
                                    @php
                                        $type = $definition['type'];
                                        $wide = in_array($type, ['textarea', 'checks'], TRUE);
                                        $options = is_array($definition['options']) ? $definition['options'] : ($definition['options'] === 'workplaces' ? [translator('Other workplaces')] : []);
                                        $isHidden = in_array($field, $hiddenFields, TRUE);
                                    @endphp
                                    <div class="{{ $wide ? 'col-12' : 'col-md-4' }} @if ($isHidden) opacity-50 @endif" wire:key="cfg-preview-{{ $previewTab }}-{{ $field }}">
                                        <div class="d-flex align-items-center gap-1 mb-1">
                                            <label class="small fw-semibold @if ($isHidden) text-decoration-line-through @endif">{{ translator($definition['label']) }}@if ($definition['required'])<span class="text-danger">*</span>@endif</label>
                                            @if ($definition['help']) <x-ui.tool-tips :message="translator($definition['help'])" /> @endif
                                            @if ($definition['custom']) <span class="badge bg-info no-border-radius">{{ translator('own') }}</span> @endif
                                            @if ($isHidden) <span class="badge bg-secondary no-border-radius">{{ translator('hidden') }}</span> @endif
                                        </div>

                                        @switch ($type)
                                            @case ('textarea')
                                                <textarea class="form-control form-control-sm no-border-radius" rows="3"></textarea>
                                                @break
                                            @case ('select')
                                                <select class="form-select form-select-sm no-border-radius">
                                                    <option value="">—</option>
                                                    @foreach ($options as $option) <option>{{ $option }}</option> @endforeach
                                                </select>
                                                @break
                                            @case ('switch')
                                                <div class="form-check form-switch"><input class="form-check-input" type="checkbox"></div>
                                                @break
                                            @case ('checks')
                                                <div class="d-flex flex-wrap gap-3 border px-2 py-1">
                                                    @forelse ($options as $option)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" id="cfg-preview-{{ $field }}-{{ $loop->index }}">
                                                            <label class="form-check-label small" for="cfg-preview-{{ $field }}-{{ $loop->index }}">{{ $option }}</label>
                                                        </div>
                                                    @empty
                                                        <small class="text-muted"><i>{{ translator('No options (set them in the file)') }}</i></small>
                                                    @endforelse
                                                </div>
                                                @break
                                            @case ('range')
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text no-border-radius">{{ translator('min') }}</span>
                                                    <input type="number" class="form-control no-border-radius">
                                                    <span class="input-group-text no-border-radius">{{ translator('max') }}</span>
                                                    <input type="number" class="form-control no-border-radius">
                                                </div>
                                                @break
                                            @default
                                                <input type="{{ $type }}" class="form-control form-control-sm no-border-radius">
                                        @endswitch
                                    </div>
                                @empty
                                    <div class="col-12 text-center py-3"><i>{{ translator('No fields on this tab.') }}</i></div>
                                @endforelse
                            </div>
                        @endif
                        <hr class="my-2">
                        <small class="text-muted">{{ translator('Example only: nothing typed here is saved.') }}</small>
                    @endif
                </div>
            </x-ui.card>
        @endif
</div>
