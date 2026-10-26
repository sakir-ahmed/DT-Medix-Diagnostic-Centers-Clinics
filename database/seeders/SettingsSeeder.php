<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Center\SeedCenterDefaultsAction;
use App\Enums\RoleName;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Installation defaults: settings from the CENTER_* env values, the
 * first branches, the catalog defaults and the hidden Datatech
 * super-admin with fixed credentials.
 */
class SettingsSeeder extends Seeder
{
    public function run(SeedCenterDefaultsAction $defaults): void
    {
        settings()->set(array_filter([
            'center_name' => config('center.name'),
            'center_name_bn' => config('center.name_bn'),
            'center_address' => config('center.address'),
            'center_contact_mobile' => normalize_bd_mobile(config('center.mobile')),
            'center_contact_email' => config('center.email'),
        ], fn (mixed $value): bool => ! blank($value)));

        Branch::query()->firstOrCreate(['code' => 'BR01'], ['name' => 'Main Branch', 'address' => config('center.address'), 'phone' => normalize_bd_mobile(config('center.mobile'))]);
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
