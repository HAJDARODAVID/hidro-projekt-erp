<th  {{ $attributes->merge(['style' => $style, 'class' => $class]) }} @if ($lwAction) wire:click='{{ $lwAction }}({{ $lwActionAtt }})' @endif>
    {{ $slot }}
</th>
