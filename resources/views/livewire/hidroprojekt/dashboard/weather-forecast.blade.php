<div class="px-2">
    <select class="form-control form-control-sm" wire:model.change='town' wire:loading.remove>
        @foreach ($townArray ?? [] as $town => $misc)
            <option value="{{ $town }}">{{ $town }}</option>
        @endforeach
    </select>
    <table class="table" wire:loading.remove>
        <tbody>
            <tr>
                <td></td>
                <td>5:00</td>
                <td>8:00</td>
                <td>11:00</td>
                <td>14:00</td>
                <td>17:00</td>
            </tr>
            @foreach ($weatherData as $day => $items)
                <tr>
                    <td>
                        <b>{{ $dayShort[date('N',strtotime($day))] }}</b><br>
                        {{ date('d.m',strtotime($day)) }}
                    </td>
                    @foreach ($items as $hour)
                        <td>
                            <x-dashboard.weather-card :hour="$hour" />
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="d-flex justify-content-center" >
        <div class="spinner-border" role="status" style="display:none" wire:loading wire:target="town">
            <span class="sr-only"></span>
        </div>
    </div> 
</div>
