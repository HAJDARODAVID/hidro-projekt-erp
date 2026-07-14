@if ($link)
    <a 
        @if(Route::has($link)) href="{{ route($link) }}" @endif
        class="btn {{ $btnColor }} @if($btnSize){{ $btnSize }} @endif shadow no-border-radius">
        <div class="d-flex align-items-center gap-2">
            @if ($iconPosition == 'start')
                <x-ui.bi-icon :icon=$iconName />
            @endif
            {{ $slot }}{{ $text }}
            @if ($iconPosition == 'end')
                <x-ui.bi-icon :icon=$iconName />
            @endif
        </div>
    </a>
@else
    <button 
        type="button"
        class="btn {{ $btnColor }} @if($btnSize){{ $btnSize }} @endif shadow" 
        style="border-radius: 0px!Important;" 
        {{ $attributes }}
        @if ($action)
            wire:click="{{ $action }}('{{ $param }}')"
        @elseif ($wClickMethod)
            wire:click="{{ $wClickMethod }}('{{ $wClickParam }}')" 
        @endif
        
        @if ($disabled) disabled @endif
        @if ($stopPropagation) onclick="event.stopPropagation();" @endif>
        <div class="d-flex align-items-center gap-2">
            @if($iconName) 
                @if ($iconPosition == 'start')
                    <x-ui.bi-icon :icon=$iconName />
                @endif
            @endif
            {{ $slot }}{{ $text }}
            @if ($iconPosition == 'end')
                <x-ui.bi-icon :icon=$iconName />
            @endif
        </div>
    </button>
@endif