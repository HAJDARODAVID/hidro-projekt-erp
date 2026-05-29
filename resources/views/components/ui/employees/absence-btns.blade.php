<div class="d-flex gap-2">
    @foreach ($absenceBtnObj->getTypes() as $btnObj)
        <button 
            class="{{ $btnObj->getClass() }}" 
            style="{{ $btnObj->getStyle() }}"
            wire:click='{{ $btnObj->getWireMethod() }}("{{ $btnObj->code() }}")'
        >
            <b>{{ $btnObj->shtDesc() }}</b>
        </button>
    @endforeach
</div>