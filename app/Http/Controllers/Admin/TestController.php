<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\SaveTestAction;
use App\Enums\ResultType;
use App\Http\Controllers\Controller;
use App\Http\Requests\TestRequest;
use App\Http\Resources\TestResource;
use App\Models\Branch;
use App\Models\BranchTestPrice;
use App\Models\Department;
use App\Models\Package;
use App\Models\ReferenceRange;
use App\Models\ReportTemplate;
use App\Models\Test;
use App\Services\Catalog\PriceResolver;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $tests = Test::query()
            ->with('department')
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('code', 'like', "{$search}%")->orWhere('name', 'like', "%{$search}%")))
            ->when($request->query('department'), fn (Builder $q, $id) => $q->where('department_id', $id))
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 50))
            ->withQueryString();

        return Inertia::render('Admin/Tests/Index', [
            'tests' => TestResource::collection($tests),
            'filters' => ['q' => $search, 'department' => $request->query('department')],
            'departments' => $this->departments(),
        ]);
    }

    public function create(): Response
    {
        return $this->form(null);
    }

    public function edit(Test $test): Response
    {
        return $this->form($test);
    }

    public function store(TestRequest $request, SaveTestAction $save): RedirectResponse
    {
        $test = $save->execute($request->validated());

        return redirect()->route('admin.tests.edit', $test)->with('success', __('catalog.test_saved'));
    }

    public function update(TestRequest $request, Test $test, SaveTestAction $save): RedirectResponse
    {
        $save->execute($request->validated(), $test);

        return back()->with('success', __('catalog.test_saved'));
    }

    /**
     * Type-ahead for the reception counter: tests and packages (packages
     * expand into their tests) with price, TAT, sample and preparation.
     */
    public function lookup(Request $request, PriceResolver $prices, TenantContext $context): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $branchId = $context->currentBranchId();

        $tests = Test::query()->with('department')->where('is_active', true)
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w->where('code', 'like', "{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->orderBy('name')->limit(15)->get();

        $packages = Package::query()->with('tests')->where('is_active', true)
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w->where('code', 'like', "{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->orderBy('name')->limit(5)->get();

        $results = [];

        foreach ($packages as $package) {
            $results[] = [
                'value' => 'package-'.$package->id,
                'type' => 'package',
                'id' => $package->id,
                'code' => $package->code,
                'label' => $package->name,
                'price' => money($package->price),
                'meta' => money($package->price),
                'description' => __('catalog.package_of', ['count' => $package->tests->count()]),
                'tests' => $package->tests->map(fn (Test $t): array => $this->testOption($t, $prices, $branchId))->values()->all(),
            ];
        }

        foreach ($tests as $test) {
            $results[] = $this->testOption($test, $prices, $branchId);
        }

        return response()->json(['results' => $results]);
    }

    /**
     * @return array<string, mixed>
     */
    private function testOption(Test $test, PriceResolver $prices, ?int $branchId): array
    {
        $price = $prices->forTest($test, $branchId);

        return [
            'value' => 'test-'.$test->id,
            'type' => 'test',
            'id' => $test->id,
            'code' => $test->code,
            'label' => $test->name,
            'price' => $price,
            'meta' => $price,
            'vat_applicable' => (bool) $test->vat_applicable,
            'tat_hours' => $test->tat_hours,
            'sample_type' => $test->sample_type,
            'preparation' => $test->preparation,
            'department' => $test->relationLoaded('department') ? $test->department->name : null,
            'description' => trim($test->code.' | '.__('catalog.tat_hours', ['hours' => $test->tat_hours]).($test->sample_type ? ' | '.__('catalog.samples.'.$test->sample_type) : '').($test->preparation ? ' | '.$test->preparation : '')),
        ];
    }

    private function form(?Test $test): Response
    {
        $test?->load(['parameters.ranges']);

        return Inertia::render('Admin/Tests/Form', [
            'test' => $test === null ? null : (new TestResource($test))->resolve(),
            'branchPrices' => $test === null ? (object) [] : BranchTestPrice::query()->where('test_id', $test->id)->pluck('price', 'branch_id'),
            'departments' => $this->departments(),
            'templates' => ReportTemplate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn (ReportTemplate $t): array => ['value' => $t->id, 'label' => $t->name]),
            'branches' => Branch::query()->orderBy('code')->get(['id', 'code', 'name']),
            'sampleTypes' => Test::SAMPLE_TYPES,
            'containers' => Test::CONTAINERS,
            'resultTypes' => array_map(fn (ResultType $t): string => $t->value, ResultType::cases()),
            'ageUnits' => ReferenceRange::AGE_UNITS,
        ]);
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    private function departments(): array
    {
        return Department::query()->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (Department $d): array => ['value' => $d->id, 'label' => $d->name])->values()->all();
    }
}
