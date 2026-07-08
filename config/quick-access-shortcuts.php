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
    ],
];
