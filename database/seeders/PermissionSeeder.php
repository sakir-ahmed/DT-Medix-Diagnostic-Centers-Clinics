<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Center\SyncPermissionsAction;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(SyncPermissionsAction $sync): void
    {
        $sync->execute();
    }
}
