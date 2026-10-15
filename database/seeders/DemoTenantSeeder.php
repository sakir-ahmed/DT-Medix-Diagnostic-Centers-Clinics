<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Tenants\CreateTenantAction;
use App\Enums\Plan;
use App\Enums\RoleName;
use App\Enums\TenantStatus;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

/**
 * Two demo tenants with one user per role (username "{slug}.{role}").
 * Uses DEMO_PASSWORD from .env and is skipped when it is not set.
 */
class DemoTenantSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    public const ROLE_USERNAMES = [
        'owner' => 'owner',
        'branch-manager' => 'manager',
        'receptionist' => 'reception',
        'sample-collector' => 'collector',
        'lab-technician' => 'tech',
        'pathologist' => 'pathologist',
        'doctor' => 'doctor',
        'accountant' => 'accountant',
    ];

    public function run(CreateTenantAction $create, TenantContext $context): void
    {
        $password = config('tenancy.demo_password');

        if (blank($password)) {
            $this->command?->warn('DEMO_PASSWORD not set: demo tenants skipped.');

            return;
        }

        foreach ([
            ['name' => 'Popular Demo Diagnostic', 'name_bn' => 'পপুলার ডেমো ডায়াগনস্টিক', 'slug' => 'demo', 'mobile' => '01710000001'],
            ['name' => 'City Care Clinic', 'name_bn' => 'সিটি কেয়ার ক্লিনিক', 'slug' => 'citycare', 'mobile' => '01710000002'],
        ] as $index => $data) {
            if (Tenant::query()->where('slug', $data['slug'])->exists()) {
                continue;
            }

            $tenant = $create->execute([
                'name' => $data['name'],
                'name_bn' => $data['name_bn'],
                'slug' => $data['slug'],
                'plan' => Plan::Pro->value,
                'contact_mobile' => $data['mobile'],
                'address' => 'Dhanmondi, Dhaka',
                'message_credits' => 500,
                'owner' => [
                    'name' => $data['name'].' Owner',
                    'username' => $data['slug'].'.owner',
                    'email' => 'owner@'.$data['slug'].'.test',
                    'password' => (string) $password,
                ],
            ]);

            $tenant->forceFill(['status' => TenantStatus::Active])->save();

            $context->run($tenant, function () use ($data, $password, $index): void {
                $main = Branch::query()->where('code', 'BR01')->firstOrFail();
                $second = Branch::query()->create([
                    'code' => 'BR02',
                    'name' => $index === 0 ? 'Mirpur Collection Center' : 'Uttara Branch',
                    'address' => $index === 0 ? 'Mirpur 10, Dhaka' : 'Uttara Sector 7, Dhaka',
                ]);

                $sequence = 10;

                foreach (self::ROLE_USERNAMES as $role => $short) {
                    if ($role === RoleName::Owner->value) {
                        continue;
                    }

                    $user = User::query()->create([
                        'name' => ucfirst($short).' '.$data['name'],
                        'username' => $data['slug'].'.'.$short,
                        'email' => $short.'@'.$data['slug'].'.test',
                        'mobile' => sprintf('88017%d%07d', $index + 1, $sequence++),
                        'password' => (string) $password,
                        'approval_pin' => $role === RoleName::BranchManager->value ? '1234' : null,
                    ]);

                    $user->assignRole($role);
                    $user->branches()->attach($main->id);
                }

                User::query()->where('username', $data['slug'].'.reception')->firstOrFail()->branches()->attach($second->id);
            });
        }
    }
}
