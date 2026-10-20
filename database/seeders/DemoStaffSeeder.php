<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One demo account per role (username "demo.{role}") sharing
 * DEMO_PASSWORD. Skipped when the password is not set.
 */
class DemoStaffSeeder extends Seeder
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

    public function run(): void
    {
        $password = config('center.demo_password');

        if (blank($password)) {
            $this->command?->warn('DEMO_PASSWORD not set: demo accounts skipped.');

            return;
        }

        if (User::query()->where('username', 'demo.owner')->exists()) {
            return;
        }

        $main = Branch::query()->where('code', 'BR01')->firstOrFail();
        $second = Branch::query()->where('code', 'BR02')->first();
        $sequence = 10;

        $names = [
            'owner' => 'Mahbub Alam', 'manager' => 'Rafiqul Islam', 'reception' => 'Sadia Afrin', 'collector' => 'Imran Hossain',
            'tech' => 'Nazmul Haque', 'pathologist' => 'Dr. Kamrul Hasan', 'doctor' => 'Dr. Ayesha Rahman', 'accountant' => 'Shafiqur Rahman',
        ];

        foreach (self::ROLE_USERNAMES as $role => $short) {
            $user = User::query()->create([
                'name' => $names[$short],
                'username' => 'demo.'.$short,
                'email' => $short.'@demo.test',
                'mobile' => sprintf('880171%07d', $sequence++),
                'password' => (string) $password,
                'approval_pin' => $role === RoleName::BranchManager->value ? '1234' : null,
            ]);

            $user->assignRole($role);
            $user->branches()->attach($main->id);

            if ($second !== null && in_array($short, ['reception', 'collector'], true)) {
                $user->branches()->attach($second->id);
            }
        }
    }
}
