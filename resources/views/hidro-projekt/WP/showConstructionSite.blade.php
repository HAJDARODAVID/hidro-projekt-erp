@extends('layouts.mainAdminLayout')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div class="d-flex flex-row">
        @livewire('hidroProjekt.wp.change-header-name-input-box',[
        'headerName' => $constructionSite->name,
        'headerId' => $constructionSite->id,
        ])
        &nbsp;&nbsp;&nbsp;
        @livewire('hidroProjekt.wp.change-construction-site-status',[
            'status' => $constructionSite->status,
            'constId' => $constructionSite->id,
        ])
    </div>
    <a href = "{{ route('hp_constructionSites') }}" class="btn btn-secondary">NATRAG</a>
  </div>
  
  <div class="container">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <x-admin-module-tabs name="construction-site-info" :routeParams="[$constructionSite->id]"></x-admin-module-tabs>
    </div>
    <div class="row">
        <div class="col">
            <h1 class="h6">Informacije gradilišta:</h1>

            @livewire('hidroProjekt.wp.construction-site-address',[
                'town' =>$constructionSite->town,
                'street' =>$constructionSite->street,
                'constId' => $constructionSite->id,
            ])
            
            @livewire('hidroProjekt.wp.construction-site-from-to-dates', [
                'start_date' =>$constructionSite->start_date,
                'end_date' =>$constructionSite->end_date,
                'constId' => $constructionSite->id,
            ])
        </div>
        <div class="col">
            @livewire('hidroProjekt.wp.construction-site-job-description-textarea',[
                'description' => $constructionSite->job_description,
                'constId' => $constructionSite->id,
            ])
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col">
            <h1 class="h6">Pregled troškova:</h1>

            <div class="row">
                <div class="col">
                    <p>
                        &nbsp; - Radnika[HP]: <b>{{ number_format($perDayHoursAndCost['sumOfWorkerCost'], 2, ',', '.') }}</b>€ <br>
                        &nbsp; - Radnika[CoOp]: <b>{{ number_format($perDayHoursAndCostCoOp['sumOfWorkerCost'], 2, ',', '.') }}</b>€ <br>
                        &nbsp; - Ukupan trošak radnika: <b>{{ number_format($perDayHoursAndCost['sumOfWorkerCost'] + $perDayHoursAndCostCoOp['sumOfWorkerCost'], 2, ',', '.') }}</b>€
                    </p>
                </div>
                <div class="col">
                    <p>
                        &nbsp; - Kumulativno izvršeni radni sati: <b>{{$cumulativelyHours + $perDayHoursAndCostCoOp['sumOfWorkerHours'] }}</b>h <br>
                        &nbsp; - HP radni sati: <b>{{$cumulativelyHours}}</b>h / CoOp radni sati: <b>{{ $perDayHoursAndCostCoOp['sumOfWorkerHours']}}</b>h <br>
                        &nbsp; - Trošak vozila: <b>{{ number_format($carCost['carCost'], 2, ',', '.') }}</b>€ <br>
                    </p>
                </div>
            </div>

            <?php
                $overallCost = $carCost['carCost'] + $perDayHoursAndCost['sumOfWorkerCost'] + $perDayHoursAndCostCoOp['sumOfWorkerCost'];
            ?>
            <p>
                &nbsp; - <b>Ukupno troškovi: {{ number_format($overallCost, 2, ',', '.') }}</b>€ <br>
            </p>

            <hr>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <td><b>Datum</b></td>
                        <td><b>Poslovođa</b></td>
                        <td><b>#</b></td>
                        <td><b>km</b></td>
                        <td><b>Sati[H-P]</b></td>
                        <td><b>€[H-P]</b></td>
                        <td><b>Sati[CoOp]</b></td>
                        <td><b>€[CoOp]</b></td>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($perDayHoursAndCost['tableData'] as $date => $data)
                        @foreach ($data as $wdr => $info)
                            <tr style='cursor: pointer; cursor: hand;' onclick="location.href='{{ route('hp_showWorkDayDiary', $wdr) }}';">
                                <td>{{ $date }}</td>
                                <td>{{ $info['groupeLeader'] }}</td>
                                <td>{{ $wdr }}</td>
                                <td>{{ $info['carMileage'] }}</td>
                                <td>{{ $info['workerHoursSum'] }}</td>
                                <td>{{ $info['workerHoursCost'] }}</td>
                                <td>
                                    @isset($perDayHoursAndCostCoOp['tableData'][$wdr])
                                        {{ $perDayHoursAndCostCoOp['tableData'][$wdr]['hours'] }}
                                    @endisset
                                </td>
                                <td>
                                    @isset($perDayHoursAndCostCoOp['tableData'][$wdr])
                                        {{ $perDayHoursAndCostCoOp['tableData'][$wdr]['cost'] }}
                                    @endisset
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
    
        </div>
        <div class="col">
            <div class="d-flex justify-content-between pb-2">
                <h1 class="h6">Dnevnik radova:</h1>
                @livewire("modules.construction-site.components.export-construction-site-logs-btn", ['constructionSiteId' => $constructionSite->id])
            </div>
            <textarea class="form-control" id="job_description" rows="18" style="resize: none;" disabled>{{$allLogsForConstructionSite}}</textarea>
        </div>
    </div>
    

  </div>
<script>
    function enableHeaderNameChange(){
        document.getElementById('constSiteHeaderName').style.display ="none";
        document.getElementById('constSiteHeaderNameChange').style.display ="block";
    }
</script>
@endsection