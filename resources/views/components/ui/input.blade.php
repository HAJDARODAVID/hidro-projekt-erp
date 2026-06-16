<div class="form-group">
    @if ($label)
       <div class="d-flex gap-1">
            <label>{{ $label }}</label>
            @if($tooltip)
                <x-ui.tool-tips :message=$tooltip/>
            @endif
        </div>
    @endif
    <div class="input-group {{ $inputGroupSize }}"> 
        @if ($prepend)
            <div class="input-group-append">
                <span class="input-group-text no-border-radius" style="padding-bottom: 0px; padding-top: 0px; height: 100%">{{ $prepend }}</span>
            </div>    
        @endif
        <input 
            type="{{ $type }}" 
            {{ $attributes->merge([
                'class' => 'form-control ',
                'style' => implode("; ", $style).'; '
                ]) }} 
            placeholder="{{ $placeholder }}"
            @if($wModel)
            wire:model.{{ $wModelEvent }} = '{{ $wModel }}'
            @endif
            @if($disabled) disabled @endif
        >
        @if ($append)
            <div class="input-group-append">
                <span class="input-group-text no-border-radius {{ $removeAddOnXP }}" style="padding-bottom: 0px; padding-top: 0px; height: 100%">{{ $append }}</span>
            </div>    
        @endif
    </div>
</div>