<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Center\SeedCenterDefaultsAction;
use App\Enums\RoleName;
use App\Models\Branch;
use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The center profile of this installation, its first branches, the
 * catalog defaults and the hidden Datatech super-admin from .env.
 */
class CenterSeeder extends Seeder
{
    public function run(SeedCenterDefaultsAction $defaults): void
    {
        if (Center::query()->doesntExist()) {
            Center::query()->create([
                'name' => (string) config('center.name'),
                'name_bn' => config('center.name_bn'),
                'address' => config('center.address'),
                'contact_mobile' => normalize_bd_mobile(config('center.mobile')),
                'contact_email' => config('center.email'),
            ]);
        }

        forget_center();

        Branch::query()->firstOrCreate(['code' => 'BR01'], ['name' => 'Main Branch', 'address' => config('center.address'), 'phone' => normalize_bd_mobile(config('center.mobile'))]);
        Branch::query()->firstOrCreate(['code' => 'BR02'], ['name' => 'Mirpur Collection Center', 'address' => 'Mirpur 10, Dhaka']);

        $defaults->execute();

        $this->seedSuperAdmin();
    }

    private function seedSuperAdmin(): void
    {
        $email = config('center.super_admin.email');
        $password = config('center.super_admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('SUPER_ADMIN_EMAIL or SUPER_ADMIN_PASSWORD not set: the super-admin was skipped.');

            return;
        }

        $user = User::query()->firstOrCreate(['email' => $email], [
            'name' => (string) config('center.super_admin.name'),
            'username' => 'datatech',
            'password' => (string) $password,
        ]);

        $user->syncRoles([RoleName::SuperAdmin->value]);
    }
}
