<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <style>
        .tr-root .tr-value { white-space: pre-wrap; word-break: break-word; }
    </style>

    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="tr-root h-100 d-flex flex-column">
        {{-- Toolbar --}}
        <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
            <b>{{ translator('Translator') }}</b>
            <x-v-divider class="align-self-stretch" />

            <select class="form-select form-select-sm no-border-radius" style="width: auto" wire:model.live="lang" title="{{ translator('Language') }}">
                @foreach ($languages as $code)
                    <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm no-border-radius" style="width: auto" wire:model.live="status" title="{{ translator('Status') }}">
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <div style="width: 320px">
                <x-ui.input size="sm" placeholder="{{ translator('Search the text or the translation') }}" wModel="search" wModelEvent="live.debounce.300ms" />
            </div>
            @if ($search !== '')
                <x-ui.btn type="lig.sm" icon="x-lg" action="resetSearch" title="{{ translator('Reset the search') }}" />
            @endif
            @if ($untranslatedCount)
                <a href="#" class="badge text-bg-warning text-decoration-none no-border-radius" wire:click.prevent="$set('status', 'untranslated')" title="{{ translator('Show the untranslated texts') }}">
                    {{ $untranslatedCount }} {{ translator('untranslated') }}
                </a>
            @endif

            <div class="ms-auto d-flex align-items-center gap-2">
                <x-ui.btn
                    type="lig.sm"
                    icon="search"
                    text="{{ translator('Scan the code') }}"
                    action="scanCode"
                    title="{{ translator('Add the texts of the code that are missing (the deploy does it too)') }}"
                    wire:loading.attr="disabled"
                    wire:target="scanCode"
                />
                <x-ui.btn type="lig.sm" icon="plus-lg" text="{{ translator('New text') }}" action="toggleNew" title="{{ translator('For texts the scan can not find, e.g. texts passed as a variable') }}" />
            </div>
        </div>

        {{-- New translation --}}
        @if ($showNew)
            <form class="d-flex align-items-center gap-2 mb-3 p-2 border bg-light" wire:submit="createTranslation" wire:key="tr-new">
                <i class="bi bi-plus-square text-primary"></i>
                <input type="text" class="form-control form-control-sm no-border-radius" style="max-width: 420px" placeholder="{{ translator('Text exactly as in the code, e.g. Save') }}" wire:model="newTranslation.value" maxlength="{{ $maxLength }}" autofocus>
                <i class="bi bi-arrow-right text-muted"></i>
                <input type="text" class="form-control form-control-sm no-border-radius" style="max-width: 420px" placeholder="{{ translator('Translation') }} ({{ strtoupper($lang) }}, {{ translator('optional') }})" wire:model="newTranslation.translation" maxlength="{{ $maxLength }}">
                <button type="submit" class="btn btn-primary btn-sm shadow no-border-radius">{{ translator('Save') }}</button>
                <button type="button" class="btn btn-light btn-sm shadow gray-border-color no-border-radius" wire:click="toggleNew">{{ translator('Cancel') }}</button>
                <span class="small text-muted ms-2">{{ translator('Upper / lower case and spaces must match the code.') }}</span>
            </form>
        @endif

        {{-- Translations --}}
        <div class="flex-fill" style="overflow-y: auto; max-height: calc(100vh - 290px)">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="sticky-top bg-white">
                    <tr>
                        <th style="width: 45%">{{ translator('Text in the code') }}</th>
                        <th>{{ translator('Translation') }} ({{ strtoupper($lang) }})</th>
                        <th style="width: 1%"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($translations as $row)
                        {{-- the key changes with the saved translation, so the input shows the saved text after a save --}}
                        @php($isUntranslated = $row->translation === NULL || $row->translation === '')
                        <tr wire:key="tr-{{ $row->id }}-{{ md5((string) $row->translation) }}">
                            <td class="tr-value small"><code class="text-body">{{ $row->value }}</code></td>
                            <td>
                                <input
                                    type="text"
                                    @class(['form-control form-control-sm no-border-radius', 'border-warning' => $isUntranslated])
                                    value="{{ $row->translation }}"
                                    placeholder="{{ $isUntranslated ? translator('Not translated, the original text is shown') : '' }}"
                                    maxlength="{{ $maxLength }}"
                                    wire:change="saveTranslation({{ $row->id }}, $event.target.value)"
                                    x-on:keydown.enter="$event.target.blur()"
                                >
                            </td>
                            <td class="text-end">
                                <x-ui.btn type="dan.sm" icon="trash" action="deleteTranslation" param="{{ $row->id }}"
                                    title="{{ translator('Delete the text (for texts no longer in the code)') }}"
                                    wire:confirm="{{ translator('Delete the text? If it is still used in the code, the next scan adds it again (untranslated).') }}" />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i>{{ $search !== '' || $status !== 'all' ? translator('No translations match the filters.') : translator('No texts yet. Scan the code to add them.') }}</i>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex align-items-center flex-wrap gap-2 pt-2 border-top">
            <span class="small text-muted">
                @if ($translations->total())
                    {{ $translations->firstItem() }}–{{ $translations->lastItem() }} / {{ $translations->total() }}
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
                {{ $translations->onEachSide(1)->links('livewire.partials.pagination') }}
            </div>
        </div>
    </x-ui.card>
</div>
