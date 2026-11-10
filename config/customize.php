<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customize folder
    |--------------------------------------------------------------------------
    |
    | Per company GUI customizations (which fields a screen shows, labels, ...).
    | The database always has every field, the customization only decides what is used.
    |
    |   customize/{company}/{name}.json     e.g. customize/hidro-projekt/workplace_fields.json
    |
    | See customize/README.md and App\Services\Application\CustomizeService.
    |
    */

    'path' => base_path('customize'),

    /*
    | The active company (folder name). Later this comes from the company table
    | (CustomizeService::currentCompany() is the only place that has to change).
    */
    'company' => env('APP_COMPANY', 'hidro-projekt'),

];
