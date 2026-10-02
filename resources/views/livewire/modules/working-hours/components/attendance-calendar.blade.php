<div>
    @if ($displayCard)
        <x-ui.card loading="selectedMonth, selectedYear">
            <x-slot:title>
                <div class="d-flex gap-2 align-items-center">
                    <div class="">{{ translator('Month') }}:</div>
                    <x-v-divider style="height: 31px" />
                    <x-ui-select s-type="months" size="sm" url="month" model="selectedMonth" style="width: 100px" />
                    <x-v-divider px=0 style="height: 31px" />
                    <x-ui-select s-type="years" size="sm" url="year" model="selectedYear" style="width: 100px" />
                </div>
            </x-slot:title>

            <x-ui.employees.attendance-calendar-grid :weeks=$weeks :dayNames=$dayNames />
        </x-ui.card>
    @else
        <x-ui.employees.attendance-calendar-grid :weeks=$weeks :dayNames=$dayNames />
    @endif
</div>
