<?php

declare(strict_types=1);

return [
    'description' => 'সেন্টারের তথ্য ও ব্যবসায়িক নিয়ম।',
    'center' => 'সেন্টারের তথ্য',
    'rules' => 'ব্যবসায়িক নিয়ম',
    'saved' => 'সেটিংস সেভ হয়েছে।',
    'days' => 'দিন',
    'minutes' => 'মিনিট',
    'hours' => 'ঘণ্টা',
    'release_rules' => [
        'hold_until_paid' => 'সম্পূর্ণ পরিশোধ পর্যন্ত আটকে রাখুন',
        'manager_override' => 'আটকে রাখুন, ম্যানেজার ছাড়তে পারবেন',
        'always' => 'সবসময় প্রকাশ করা যাবে',
    ],
    'basis' => [
        'paid' => 'সম্পূর্ণ পরিশোধ হলে',
        'billed' => 'বিল হলে',
    ],
    'helpers' => [
        'release_rule' => 'যাচাইয়ের আগে কোনো রিপোর্ট প্রকাশ করা যাবে না।',
    ],
    'fields' => [
        'release_rule' => 'রিপোর্ট প্রকাশের নিয়ম',
        'discount_limit_percent' => 'অনুমোদন ছাড়া ছাড়ের সর্বোচ্চ সীমা',
        'vat_enabled' => 'ভ্যাট নিন',
        'vat_percent' => 'ভ্যাটের হার',
        'service_charge_enabled' => 'সার্ভিস চার্জ নিন',
        'service_charge_percent' => 'সার্ভিস চার্জের হার',
        'allow_self_verification' => 'ফলাফল যিনি দিয়েছেন তাঁকেই যাচাই করতে দিন',
        'report_link_days' => 'রিপোর্ট লিংকের মেয়াদ',
        'follow_up_days' => 'ডিফল্ট ফলো-আপ সময়সীমা',
        'follow_up_fee_percent' => 'ডিফল্ট ফলো-আপ ফি (পরামর্শ ফির অংশ)',
        'delta_check_percent' => 'যে পরিবর্তনের বেশি হলে সতর্কতা',
        'commission_basis' => 'কমিশন হিসাব হবে',
        'commission_default_percent' => 'ডিফল্ট কমিশন',
        'commission_enabled' => 'ডাক্তার কমিশন ব্যবহার করুন',
        'default_locale' => 'ডিফল্ট ভাষা',
        'idle_timeout_minutes' => 'নিষ্ক্রিয়তার পর সাইন আউট',
        'tat_target_hours' => 'ডিফল্ট টিএটি লক্ষ্য',
        'collected_samples_refundable' => 'সংগৃহীত নমুনার টাকা ফেরতযোগ্য',
    ],
];
