<div class="{{ implode(" ", $calendarClass) }}" wire:click='{{ $method }}'>
    <span class="{{ implode(" ", $dayClass) }}">{{ $info['day'] }}</span>
</div>