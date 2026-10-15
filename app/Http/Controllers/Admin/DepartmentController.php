<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\SaveCatalogItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Departments/Index', [
            'departments' => Department::query()->withCount('tests')->orderBy('sort_order')->orderBy('name')->get()->map(fn (Department $d): array => [
                'id' => $d->id, 'code' => $d->code, 'name' => $d->name, 'name_bn' => $d->name_bn, 'category' => $d->category->value,
                'report_style' => $d->report_style->value, 'sort_order' => $d->sort_order, 'is_active' => $d->is_active, 'tests_count' => $d->tests_count,
            ]),
        ]);
    }

    public function store(DepartmentRequest $request, SaveCatalogItemAction $save): RedirectResponse
    {
        $save->department($request->validated());

        return back()->with('success', __('catalog.department_saved'));
    }

    public function update(DepartmentRequest $request, Department $department, SaveCatalogItemAction $save): RedirectResponse
    {
        $save->department($request->validated(), $department);

        return back()->with('success', __('catalog.department_saved'));
    }
}
