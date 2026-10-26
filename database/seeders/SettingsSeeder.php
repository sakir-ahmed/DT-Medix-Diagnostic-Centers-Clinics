<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Center\SeedCenterDefaultsAction;
use App\Enums\RoleName;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Installation defaults: the demo center profile stored in the settings
 * table (written directly, because Settings::set() skips default values;
 * editable later from admin settings), the first branches, the
 * catalog defaults and the hidden Datatech super-admin.
 */
class SettingsSeeder extends Seeder
{
    public function run(SeedCenterDefaultsAction $defaults): void
    {
        $address = 'House 12, Road 5, Dhanmondi, Dhaka 1205';
        $mobile = normalize_bd_mobile('01575888775');

        $profile = [
            'center_name' => 'DT Medix Diagnostic Center',
            'center_name_bn' => 'ডিটি মেডিক্স ডায়াগনস্টিক সেন্টার',
            'center_address' => $address,
            'center_contact_mobile' => $mobile,
            'center_contact_email' => 'info@dtmedix.test',
        ];

        foreach ($profile as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }

        settings()->flush();

        Branch::query()->firstOrCreate(['code' => 'BR01'], ['name' => 'Main Branch', 'address' => $address, 'phone' => $mobile]);
        Branch::query()->firstOrCreate(['code' => 'BR02'], ['name' => 'Mirpur Collection Center', 'address' => 'Mirpur 10, Dhaka']);

        $defaults->execute();

        $this->seedSuperAdmin();
    }

    private function seedSuperAdmin(): void
    {
        $user = User::query()->firstOrCreate(['email' => 'admin@dtmedix.test'], [
            'name' => 'Datatech Support',
            'username' => 'datatech',
            'password' => 'Admin@12345',
        ]);

        $user->syncRoles([RoleName::SuperAdmin->value]);
    }
}
