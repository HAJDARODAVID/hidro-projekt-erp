<div>
    <x-ui.nav-tabs :tabs="$tabs" :selectedTab="$activeTab" py="1" />

    <hr class="mt-2">

    @switch ($activeTab)
        @case ('calculation')
            @livewire('modules.finance-accounting.components.payroll-calculation-settings', key('payroll-settings-calculation'))
            @break
        @case ('bonus')
            <div class="text-center text-muted py-5">
                {{ translator('Bonus settings') }}
            </div>
            @break
    @endswitch
</div>
