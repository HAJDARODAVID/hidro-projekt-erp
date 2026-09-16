<td {{ $attributes->merge(['style' => $style]) }} @if ($trigger) data-trigger="global-modal" data-component="worker-attendance-info" @endif>{{ $slot }}{{translator($attendance ?? '')}}</td>
