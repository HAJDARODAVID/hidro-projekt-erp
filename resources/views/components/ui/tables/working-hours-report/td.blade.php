<td {{ $attributes->merge(['style' => $style]) }} @if ($trigger) data-trigger="global-modal" data-component="{{ $component }}" @endif>{{ $slot }}{{translator($attendance ?? '')}}</td>
