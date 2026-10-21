<?php

declare(strict_types=1);

use App\Actions\Center\SyncPermissionsAction;
use App\Enums\RoleName;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/**
 * Boot this installation for a test: the role set, a main branch (BR01)
 * and a center profile in the settings table.
 */
function makeCenter(): void
{
    app(SyncPermissionsAction::class)->execute();
    Branch::query()->firstOrCreate(['code' => 'BR01'], ['name' => 'Main']);
    settings()->set(['center_name' => 'Test Diagnostic Center']);
}

function mainBranch(): Branch
{
    return Branch::query()->withoutGlobalScopes()->where('code', 'BR01')->firstOrFail();
}

function makeBranch(string $code = 'BR02'): Branch
{
    return Branch::query()->withoutGlobalScopes()->firstOrCreate(['code' => $code], ['name' => 'Branch '.$code]);
}

/**
 * A staff user with the given role, attached to a branch (main by default).
 *
 * @param  array<string, mixed>  $attributes
 */
function makeUser(RoleName|string $role = RoleName::Owner, ?Branch $branch = null, array $attributes = []): User
{
    $roleName = $role instanceof RoleName ? $role->value : $role;

    /** @var User $user */
    $user = User::factory()->create($attributes);
    $user->assignRole($roleName);
    $user->branches()->attach(($branch ?? mainBranch())->id);

    return $user->fresh();
}
