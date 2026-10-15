<?php

declare(strict_types=1);

return [
    'description' => 'Center details and business rules.',
    'center' => 'Center details',
    'rules' => 'Business rules',
    'saved' => 'Settings saved.',
    'days' => 'days',
    'minutes' => 'min',
    'hours' => 'hours',
    'release_rules' => [
        'hold_until_paid' => 'Hold until fully paid',
        'manager_override' => 'Hold, manager may override',
        'always' => 'Always allow release',
    ],
    'basis' => [
        'paid' => 'When fully paid',
        'billed' => 'When billed',
    ],
    'helpers' => [
        'release_rule' => 'A report can never be released before it is verified.',
    ],
    'fields' => [
        'release_rule' => 'Report release rule',
        'discount_limit_percent' => 'Discount without approval up to',
        'vat_enabled' => 'Charge VAT',
        'vat_percent' => 'VAT rate',
        'service_charge_enabled' => 'Charge service charge',
        'service_charge_percent' => 'Service charge rate',
        'allow_self_verification' => 'Allow the person who entered a result to verify it',
        'report_link_days' => 'Report link valid for',
        'follow_up_days' => 'Default follow-up window',
        'follow_up_fee_percent' => 'Default follow-up fee (of consultation)',
        'delta_check_percent' => 'Delta warning above',
        'commission_basis' => 'Commission accrues',
        'commission_default_percent' => 'Default commission',
        'commission_enabled' => 'Use doctor commission',
        'default_locale' => 'Default language',
        'idle_timeout_minutes' => 'Sign out after inactivity',
        'tat_target_hours' => 'Default turnaround target',
        'collected_samples_refundable' => 'Collected samples are refundable',
    ],
];
