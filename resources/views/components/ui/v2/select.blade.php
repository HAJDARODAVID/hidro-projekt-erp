<div class="form-group">
    @if ($label ?? NULL)
        <div class="d-flex gap-1">
            <label>{{ $label }}</label>
            @if($tooltip) <x-ui.tool-tips :message=$tooltip/> @endif
        </div>
    @endif
    <select {{ $attributes->merge(['class' => implode(" " , $class)]) }}
        @if($model) wire:model.{{ $event }} = '{{ $model }}' @endif 
        @if($disabled) disabled @endif
        @if($url) 
            x-on:change="(() => { const url = new URL(window.location.href); url.searchParams.set('{{ $url }}', $event.target.value); history.replaceState({}, '', url); })()"
        @endif>
        @if ($initOpt) <option value="init-option" selected>{{ $initOpt }}</option> @endif
        @foreach ($options as $value => $option)
            <option value="{{ $value }}">{{ translator($option ?? '') }}</option>
        @endforeach
    </select>
</div>