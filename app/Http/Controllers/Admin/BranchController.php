<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Branches\SaveBranchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Branches/Index', [
            'branches' => Branch::query()->withCount('users')->orderBy('code')->get()->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
                'name_bn' => $branch->name_bn,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'email' => $branch->email,
                'license_no' => $branch->license_no,
                'is_active' => $branch->is_active,
                'status' => $branch->is_active ? 'active' : 'inactive',
                'users_count' => $branch->users_count,
            ]),
        ]);
    }

    public function store(BranchRequest $request, SaveBranchAction $save): RedirectResponse
    {
        $save->execute($request->validated());

        return back()->with('success', __('branches.created'));
    }

    public function update(BranchRequest $request, Branch $branch, SaveBranchAction $save): RedirectResponse
    {
        $save->execute($request->validated(), $branch);

        return back()->with('success', __('branches.updated'));
    }

    public function toggle(Request $request, Branch $branch, SaveBranchAction $save): RedirectResponse
    {
        $active = $request->boolean('active');
        $save->setActive($branch, $active);
        activity('branches')->performedOn($branch)->log($active ? 'branch.activated' : 'branch.deactivated');

        return back()->with('success', $active ? __('branches.activated') : __('branches.deactivated'));
    }
}
