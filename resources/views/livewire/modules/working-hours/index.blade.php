<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <x-ui.card :noBodyPadding=TRUE class="h-100 d-flex flex-column">
        <x-slot:title>
            <div class="d-flex gap-2 align-items-center">
                <div class="">{{ translator('Month overview') }}</div>
                <x-v-divider style="height: 31px"/>
                <x-ui.select :options=$months class="form-select-sm" wModel='selectedMonth' style="width: 100px" />
                <x-v-divider px=0 style="height: 31px"/>
                <x-ui.select :options=$years class="form-select-sm"  wModel='selectedYear' style="width: 100px" />
            </div>
        </x-slot:title>
        <x-slot:headerActions>
            <div class="d-flex gap-2">
                @livewire('modules.workday-diary.create-new-diary-modal')
                @livewire('modules.working-hours.components.monthly-hours-report-modal', ['month' => $selectedMonth, 'year' => $selectedYear], key('monthly.report.'.now()))
            </div>
        </x-slot:headerActions>
        <x-ui.card class="flex-fill d-flex flex-column" loading="selectedMonth, selectedYear, refreshMe">
            @if (Session::get('is_phone'))
                @livewire('modules.working-hours.components.mobile-table', ['tableData' => $data], key('working-hours-mobile'.now()))
            @else
                @livewire('modules.working-hours.components.table', ['tableData' => $data], key('working-hours'.now()))
            @endif
        </x-ui.card>
    </x-ui.card>
</div>
