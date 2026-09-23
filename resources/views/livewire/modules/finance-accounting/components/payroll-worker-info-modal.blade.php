<div>
    <div class="d-flex align-items-center gap-2">
        <strong>{{ $workerLabel }}</strong>
        <x-v-divider />
        <x-ui.nav-tabs :tabs="$tabs" :selectedTab="$activeTab" py="1" />
    </div>

    <hr class="mt-2">

    @switch ($activeTab)
        @case ('payroll-info')
            @livewire(
                'modules.employees.components.worker-payroll-info',
                ['workerId' => $workerID],
                key('payroll-worker-info-payroll-info-' . $workerID)
            )
            @break
        @case ('deductions')
            @livewire(
                'modules.finance-accounting.components.payroll-deduction',
                ['params' => ['workerID' => $workerID, 'month' => $selectedMonth, 'year' => $selectedYear]],
                key('payroll-worker-info-deductions-' . $workerID)
            )
            @break
        @case ('attendance')
            @livewire(
                'modules.working-hours.components.attendance-calendar',
                ['workerID' => $workerID, 'month' => $selectedMonth, 'year' => $selectedYear, 'displayCard' => false],
                key('payroll-worker-info-attendance-' . $workerID)
            )
            @break
    @endswitch
</div>
