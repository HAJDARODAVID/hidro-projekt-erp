{{--
    Options editor of a select / checks field (row under the field in the configurator).
    $rows: "fieldRows" | "customRows", $field, $colspan, $defaults: catalog options (NULL for own fields), $maxLength
--}}
<tr wire:key="cfg-options-{{ $rows }}-{{ $field }}" class="table-light">
    <td colspan="{{ $colspan }}">
        <div class="row g-2 align-items-start">
            <div class="col-md-5">
                <label class="small fw-semibold mb-1" for="cfg-options-{{ $rows }}-{{ $field }}">
                    {{ translator('Options of') }} <code>{{ $field }}</code> <small class="text-muted fw-normal">({{ translator('one per line, in this order') }})</small>
                </label>
                <textarea id="cfg-options-{{ $rows }}-{{ $field }}" class="form-control form-control-sm no-border-radius font-monospace" rows="7"
                    wire:model.blur="{{ $rows }}.{{ $field }}.options" placeholder="{{ translator('Option 1') }}&#10;{{ translator('Option 2') }}"></textarea>
            </div>
            <div class="col-md-7 small">
                <ul class="text-muted ps-3 mb-2">
                    <li>{{ translator('Empty lines and duplicates are removed when you leave the box.') }}</li>
                    <li>{{ translator('Max.') }} {{ $maxLength }} {{ translator('characters per option') }}, {{ \App\Services\Config\WorkplaceFieldsConfigService::OPTIONS_MAX }} {{ translator('options') }}.</li>
                    <li>{{ translator('A value already saved on a workplace that is no longer in the list stays saved and is still shown there.') }}</li>
                </ul>
                @if ($defaults !== NULL)
                    <div class="mb-2">
                        <span class="text-muted">{{ translator('Default') }}:</span>
                        @foreach ($defaults as $option) <span class="badge bg-light text-dark border no-border-radius fw-normal">{{ $option }}</span> @endforeach
                    </div>
                @endif
                <div class="d-flex gap-2">
                    @if ($defaults !== NULL)
                        <x-ui.btn type="lig.sm" icon="arrow-counterclockwise" text="{{ translator('Back to default') }}" wire:click="resetOptions('{{ $field }}')" />
                    @endif
                    <x-ui.btn type="lig.sm" icon="check-lg" text="{{ translator('Done') }}" wire:click="toggleOptions('{{ $rows }}', '{{ $field }}')" />
                </div>
            </div>
        </div>
    </td>
</tr>
