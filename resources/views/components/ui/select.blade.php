<div class="form-group">
    @if ($label)
        <div class="d-flex gap-1">
            <label>{{ $label }}</label>
            @if($tooltip)
                <x-ui.tool-tips :message=$tooltip/>
            @endif
        </div>
    @endif
    <select {{ $attributes->merge(['class' => 'no-border-radius form-select ']) }}
        @if($wModel)
        wire:model.{{ $wModelEvent }} = '{{ $wModel }}'
        @endif >
        @if ($initOption)
            <option value="init-option" selected>{{ $initOption }}</option>
        @endif
        @foreach ($options as $value => $option)
            <option value="{{ $value }}">{{ translator($option ?? '') }}</option>
        @endforeach
    </select>
</div>