<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\SaveCatalogItemAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PackageRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Models\Test;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Packages/Index', [
            'packages' => PackageResource::collection(Package::query()->with('tests')->orderBy('name')->get())->resolve(),
            'tests' => Test::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'price'])
                ->map(fn (Test $t): array => ['value' => $t->id, 'label' => "{$t->code} - {$t->name}", 'price' => money($t->price)]),
        ]);
    }

    public function store(PackageRequest $request, SaveCatalogItemAction $save): RedirectResponse
    {
        $save->package($request->validated());

        return back()->with('success', __('catalog.package_saved'));
    }

    public function update(PackageRequest $request, Package $package, SaveCatalogItemAction $save): RedirectResponse
    {
        $save->package($request->validated(), $package);

        return back()->with('success', __('catalog.package_saved'));
    }
}
