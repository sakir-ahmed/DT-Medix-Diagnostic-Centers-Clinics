<?php

declare(strict_types=1);

/*
| One installation is one center. Datatech deploys the app and the
| database for a center by hand; there is no tenant id and no platform
| console (docs/prd.md section 2, docs/architecture.md section 11).
*/
return [

    /*
    | The center profile created on first install. Changed later from the
    | admin settings page.
    */
    'name' => env('CENTER_NAME', 'DT Medix Diagnostic Center'),
    'name_bn' => env('CENTER_NAME_BN'),
    'address' => env('CENTER_ADDRESS'),
    'mobile' => env('CENTER_MOBILE'),
    'email' => env('CENTER_EMAIL'),

    /*
    | The hidden Datatech super-admin login on this server. Never hard-code
    | the password; the seeder skips the account when it is not set.
    */
    'super_admin' => [
        'name' => env('SUPER_ADMIN_NAME', 'Datatech Support'),
        'email' => env('SUPER_ADMIN_EMAIL'),
        'password' => env('SUPER_ADMIN_PASSWORD'),
    ],

    'demo_password' => env('DEMO_PASSWORD'),

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
