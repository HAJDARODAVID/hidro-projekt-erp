<div class="form-group">
    @if ($label ?? NULL)
        <div class="d-flex gap-1">
            <label>{{ $label }}</label>
            @if($tooltip) <x-ui.tool-tips :message=$tooltip/> @endif
        </div>
    @endif
    <div class="input-group">
        @if ($prepend)
            <div class="input-group-append">
                <span class="input-group-text no-border-radius" style="padding-bottom: 0px; padding-top: 0px; height: 100%">{{ $prepend }}</span>
            </div>
        @endif
        <input type="{{ $type }}"
            {{ $attributes->merge([
                'class' => implode(" ", $class),
                'style' => implode("; ", $style).'; '
                ]) }}
            placeholder="{{ $placeholder }}"
            @if($model) wire:model.{{ $event }} = '{{ $model }}' @endif
            @if($disabled) disabled @endif
            @if($url)
                x-on:change="(() => { const url = new URL(window.location.href); url.searchParams.set('{{ $url }}', $event.target.value); history.replaceState({}, '', url); })()"
            @endif
        >
        @if ($append)
            <div class="input-group-append">
                <span class="input-group-text no-border-radius {{ $removeAddOnXP }}" style="padding-bottom: 0px; padding-top: 0px; height: 100%">{{ $append }}</span>
            </div>
        @endif
    </div>
</div>
