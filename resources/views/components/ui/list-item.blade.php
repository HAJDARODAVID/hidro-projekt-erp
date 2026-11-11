<li
    class="list-group-item @if($selected) activated-list-item @endif"
    style="cursor: pointer"
    wire:click="{{ $wClickMethod }}('{{ $wClickParam }}')"
    @if($url)
        x-on:click="(() => { const url = new URL(window.location.href); url.searchParams.set(@js($url), @js($wClickParam)); history.replaceState({}, '', url); })()"
    @endif
>
    <div class="d-flex justify-content-between gap-2">
        {{-- min-width 0: long text can shrink (text-truncate) instead of pushing the right side out --}}
        <div class="" style="min-width: 0">{{ $slot }}{{ $slotLeft }}</div>
        <div class="flex-shrink-0">{{ $slotRight }}</div>
    </div>
</li>