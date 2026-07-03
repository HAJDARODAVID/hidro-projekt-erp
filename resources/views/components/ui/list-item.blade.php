<li
    class="list-group-item @if($selected) activated-list-item @endif"
    style="cursor: pointer"
    wire:click="{{ $wClickMethod }}('{{ $wClickParam }}')"
    @if($url)
        x-on:click="(() => { const url = new URL(window.location.href); url.searchParams.set(@js($url), @js($wClickParam)); history.replaceState({}, '', url); })()"
    @endif
>
    <div class="d-flex justify-content-between">
        <div class="">{{ $slot }}{{ $slotLeft }}</div>
        <div class="">{{ $slotRight }}</div>
    </div>
</li>