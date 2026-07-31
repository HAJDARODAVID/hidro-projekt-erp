<?php

return [
    /*
     * Registry of all widgets that can appear on the dashboard.
     * key => blade component (dot notation, resolved via x-dynamic-component)
     */
    'available_widgets' => [
        'quick-actions' => [
            'component' => 'ui.dashboard.quick-actions',
            'label' => 'Brze akcije',
        ],
        'quick-stats' => [
            'component' => 'ui.dashboard.quick-stats',
            'label' => 'Brza statistika',
        ],
        'active-construction-sites' => [
            'component' => 'ui.dashboard.active-construction-sites',
            'label' => 'Aktivna gradilišta',
        ],
        'my-tasks' => [
            'component' => 'ui.dashboard.my-tasks',
            'label' => 'Moji zadaci',
        ],
    ],

    /*
     * Widget order/selection used when a user has no saved configuration.
     */
    'default_layout' => [
        'quick-actions',
        'quick-stats',
        'active-construction-sites',
        'my-tasks',
    ],
];
