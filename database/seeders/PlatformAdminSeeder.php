<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the root platform account from .env (PLATFORM_ADMIN_EMAIL and
 * PLATFORM_ADMIN_PASSWORD). Skipped when they are not set.
 */
class PlatformAdminSeeder extends Seeder
{
    public function run(PermissionRegistrar $registrar): void
    {
        $email = config('tenancy.platform_admin.email');
        $password = config('tenancy.platform_admin.password');

        if (blank($email) || blank($password)) {
            $this->command?->warn('PLATFORM_ADMIN_EMAIL / PLATFORM_ADMIN_PASSWORD not set: platform admin skipped.');

            return;
        }

        $admin = User::query()->withoutGlobalScopes()->firstOrCreate(
            ['email' => $email],
            ['name' => config('tenancy.platform_admin.name'), 'username' => 'platform', 'password' => $password],
        );

        $registrar->setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $admin->assignRole(RoleName::SuperAdmin->value);
    }
}
