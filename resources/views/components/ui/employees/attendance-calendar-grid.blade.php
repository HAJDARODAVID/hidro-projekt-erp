<div style="overflow-x: auto">
    <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; min-width: 600px">
        <thead class="table-header">
            <tr style="text-align: center">
                <th style="width: 45px">{{ translator('WK') }}</th>
                @foreach ($dayNames as $dayName)
                    <th>{{ $dayName }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($weeks as $week)
                <tr>
                    <td class="text-muted fw-bold" style="text-align: center; vertical-align: middle">{{ $week['number'] }}</td>
                    @foreach ($week['days'] as $day)
                        <td style="height: 62px; vertical-align: top; @if (!$day['inMonth']) background-color: #f4f4f4 @elseif ($day['isWeekend']) background-color: #fafafa @endif">
                            <div class="d-flex justify-content-between align-items-start gap-1">
                                <small @if (!$day['inMonth']) class="text-muted" @endif>{{ $day['day'] }}</small>
                                @if ($day['absence'])
                                    <span class="badge no-border-radius" style="background-color: {{ $day['color'] }}">{{ $day['absence'] }}</span>
                                @endif
                            </div>
                            @if ($day['hours'])
                                <div class="fw-bold" style="text-align: center">{{ number_format($day['hours'], 2, ',', '.') }}</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
