<?php

declare(strict_types=1);

/*
| One installation is one center. Datatech deploys the app and the
| database for a center by hand; there is no tenant id and no platform
| console (docs/prd.md section 2, docs/architecture.md section 11).
*/
return [

    /*
    | The center profile lives in the settings table (seeded by
    | SettingsSeeder, edited from the admin settings page).
    */

    'demo_password' => 'secret',

    /*
    | Sales phone number shown in the demo banner while IS_DEMO is on.
    */
    'demo_contact_phone' => env('DEMO_CONTACT_PHONE', '+880 1575-888775'),

    /*
    | Private disk for patient files, report attachments and exports.
    */
    'storage_disk' => env('CENTER_STORAGE_DISK', 'local'),

    /*
    | Models with a branch_id that are intentionally readable across the
    | branches of the center (checked by the architecture test).
    */
    'cross_branch_models' => [],
];
