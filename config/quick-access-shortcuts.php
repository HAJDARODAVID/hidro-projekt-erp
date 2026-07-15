<?php

return [
    'known_shortcuts' => [
        [
            'code' => 'ATT',
            'label' => 'Attendance module',
            'type' => 'route',
            'route_names' => [
                'getAllEmployeeWorkingHours',
                'hp_allWorkHours',
            ],
        ],
        [
            'code' => 'CLR',
            'label' => 'Clear application cache',
            'type' => 'action',
            'action' => 'clear-cache',
        ],
        [
            'code' => 'CAL',
            'label' => 'Open calculator modal',
            'type' => 'modal',
            'modal' => 'calculator',
        ],
        // Example: open any Livewire component in the global modal via quick-access.
        // Set type to 'modal', modal to 'global', and add a 'component' key with the
        // Livewire component name (dot-notation, same as @livewire() / wire:navigate).
        //
        // [
        //     'code'      => 'XYZ',
        //     'label'     => 'Open XYZ module',
        //     'type'      => 'modal',
        //     'modal'     => 'global',
        //     'component' => 'calculator',
        // ],
    ],
];
