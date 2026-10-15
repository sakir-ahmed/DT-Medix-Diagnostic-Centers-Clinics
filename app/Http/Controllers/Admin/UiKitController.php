<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hidden showcase of every shared UI-kit component.
 */
class UiKitController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Admin/UiKit', [
            'rows' => collect(range(1, 8))->map(fn (int $i): array => [
                'id' => $i,
                'code' => sprintf('INV-BR01-26-%06d', 120 + $i),
                'name' => __('ui_kit.sample_item', ['number' => $i]),
                'amount' => number_format(1250 * $i, 2, '.', ''),
                'status' => ['paid', 'partial', 'due', 'cancelled'][$i % 4],
                'created_at' => now()->subHours($i * 5)->toIso8601String(),
            ])->all(),
        ]);
    }

    public function validateDemo(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:3'],
            'mobile' => ['required', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        return back()->with('success', __('ui_kit.form_valid'));
    }
}
