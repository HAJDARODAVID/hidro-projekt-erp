<?php

return [
    'stable-test' => [
        'component-path' => 'components.modal.stable-testing-example',
        'header-name' => 'Stable Test Example',
        'header-style' => null,
        'max-width' => null,
        'stable' => true,
        // Name of a public method on the modal's own Livewire component
        // (components.modal.stable-testing-example). Called on that component
        // before the modal closes, and the close is held until it finishes.
        // 'before-close' => 'beforeCloseAction',
    ],
    'worker-attendance-info' => [
        'component-path' => 'modules.working-hours.components.worker-attendance-per-day',
        'header-name' => 'Attendance',
        'header-style' => null,
        'max-width' => '1050px',
        'before-close' => 'beforeCloseAction',
    ],
    'monthly-hours-report' => [
        'component-path' => 'modules.working-hours.components.monthly-hours-report-modal',
        'header-name' => 'Monthly worker hours report',
        'header-style' => null,
        'max-width' => null,
    ],
    'day-attendance-for-all-workers' => [
        'component-path' => 'modules.working-hours.components.day-attendance-for-all-workers-modal',
        'header-name' => 'Attendance / absence',
        'header-style' => null,
        'max-width' => '450px',
    ],
    'subcontractor-hours-export' => [
        'component-path' => 'modules.working-hours.components.subcontractor-hours-export-modal',
        'header-name' => 'Subcontractor hours export',
        'header-style' => null,
        'max-width' => '750px',
    ],
    'subcontractor-attendance-info' => [
        'component-path' => 'modules.working-hours.components.subcontractor-worker-attendance-per-day',
        'header-name' => 'Subcontractor attendance',
        'header-style' => null,
        'max-width' => '1050px',
        'before-close' => 'beforeCloseAction',
    ],
    'payroll-deduction' => [
        'component-path' => 'modules.finance-accounting.components.payroll-deduction',
        'header-name' => 'Payroll deduction',
        'header-style' => null,
        'max-width' => '700px',
    ],
    'payroll-worker-info' => [
        'component-path' => 'modules.finance-accounting.components.payroll-worker-info-modal',
        'header-name' => 'Worker info',
        'header-style' => null,
        'max-width' => '900px',
    ],
    'payroll-settings' => [
        'component-path' => 'modules.finance-accounting.components.payroll-settings-modal',
        'header-name' => 'Payroll settings',
        'header-style' => null,
        'max-width' => '700px',
    ],
];
