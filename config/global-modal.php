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
    ],
];
