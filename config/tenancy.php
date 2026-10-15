<?php

declare(strict_types=1);

return [

    /*
    | Hosts that belong to the platform itself (no tenant). Tenants are
    | resolved from "{slug}.{base_domain}" or from a custom domain.
    */
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'dtmedix.test'),

    'central_domains' => array_filter(explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', 'localhost,127.0.0.1,dtmedix.test'))),

    'trial_days' => (int) env('TENANCY_TRIAL_DAYS', 30),

    'grace_days' => (int) env('TENANCY_GRACE_DAYS', 7),

    'retention_days' => (int) env('TENANCY_RETENTION_DAYS', 90),

    /*
    | The root platform account (Datatech). Never hard-code the password.
    */
    'platform_admin' => [
        'name' => env('PLATFORM_ADMIN_NAME', 'Datatech Admin'),
        'email' => env('PLATFORM_ADMIN_EMAIL'),
        'password' => env('PLATFORM_ADMIN_PASSWORD'),
    ],

    'demo_password' => env('DEMO_PASSWORD'),

    /*
    | Models with a branch_id that are intentionally readable across the
    | branches of a tenant (checked by the architecture test).
    */
    'cross_branch_models' => [],

    'storage_disk' => env('TENANCY_STORAGE_DISK', 'local'),

    /*
    | Suggested plans. Limits and modules are copied onto the tenant when the
    | plan is assigned, so they stay editable per tenant from the platform.
    | A null limit means unlimited.
    */
    'plans' => [
        'starter' => [
            'price' => '2000.00',
            'limits' => ['branches' => 1, 'users' => 5],
            'modules' => ['patients', 'appointments', 'orders', 'lab', 'reports', 'billing', 'dues', 'sms'],
        ],
        'growth' => [
            'price' => '5000.00',
            'limits' => ['branches' => 3, 'users' => 15],
            'modules' => [
                'patients', 'appointments', 'orders', 'lab', 'reports', 'billing', 'dues', 'sms',
                'commission', 'referrals', 'report_templates', 'whatsapp', 'patient_portal', 'due_reminders',
            ],
        ],
        'pro' => [
            'price' => '10000.00',
            'limits' => ['branches' => 10, 'users' => null],
            'modules' => ['*'],
        ],
    ],
];
