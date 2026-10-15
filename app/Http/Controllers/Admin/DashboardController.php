<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'checklist' => [
                ['key' => 'branch', 'done' => true],
                ['key' => 'doctors', 'done' => false],
                ['key' => 'catalog', 'done' => false],
                ['key' => 'templates', 'done' => false],
                ['key' => 'staff', 'done' => false],
                ['key' => 'import', 'done' => false],
            ],
        ]);
    }
}
