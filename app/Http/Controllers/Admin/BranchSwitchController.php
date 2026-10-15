<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate(['branch_id' => ['required', 'integer']]);

        // Branch global scopes return 404 for other tenants and inaccessible branches.
        $branch = Branch::query()->where('is_active', true)->findOrFail($validated['branch_id']);

        $request->session()->put('branch_id', $branch->id);

        return back();
    }
}
