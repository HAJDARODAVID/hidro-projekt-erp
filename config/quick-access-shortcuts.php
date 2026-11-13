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
        [
            'code' => 'MHR',
            'label' => 'Monthly worker hours report',
            'type' => 'modal',
            'modal' => 'global',
            'component' => 'monthly-hours-report',
        ],
        [
            'code' => 'CRT',
            'label' => 'Certificates & checks - overview (expired, missing, due soon)',
            'type' => 'modal',
            'modal' => 'global',
            'component' => 'certificates-overview',
            // Only shown to users with this ACL permission (optional key, any shortcut can have it)
            'permission' => 'employees.worker-info.certificates',
        ],
        [
            'code' => 'APP-MODULES',
            'label' => 'Application modules',
            'type' => 'route',
            'route_names' => [
                'getAllApplicationModules',
            ],
        ],
        [
            'code' => 'APP-ACL',
            'label' => 'Access control (ACL)',
            'type' => 'route',
            'route_names' => [
                'getAclModule',
            ],
        ],
        // Any shortcut can have an optional 'permission' => 'acl.key': it is only offered to users who hold it.
        //
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
