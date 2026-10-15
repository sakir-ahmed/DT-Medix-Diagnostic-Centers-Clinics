<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Catalog\SaveCatalogItemAction;
use App\Actions\Catalog\SaveTestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PackageRequest;
use App\Http\Requests\ReportTemplateRequest;
use App\Http\Requests\TestRequest;
use App\Http\Resources\PackageResource;
use App\Http\Resources\TestResource;
use App\Http\Responses\ApiResponse;
use App\Models\Package;
use App\Models\ReportTemplate;
use App\Models\Test;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Test master, parameters with reference ranges, packages and report templates.
 *
 * @tags Catalog
 */
class CatalogController extends Controller
{
    public function tests(Request $request): JsonResponse
    {
        $tests = Test::query()->with('department')
            ->when($request->input('filter.q'), fn ($q, $term) => $q->where(fn ($w) => $w->where('code', 'like', $term.'%')->orWhere('name', 'like', '%'.$term.'%')))
            ->when($request->input('filter.department'), fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('name')
            ->paginate(min(100, (int) $request->query('per_page', 50)));

        return ApiResponse::success(TestResource::collection($tests));
    }

    public function storeTest(TestRequest $request, SaveTestAction $save): JsonResponse
    {
        $test = $save->execute($request->validated())->load('parameters.ranges');

        return ApiResponse::success(new TestResource($test), __('catalog.test_saved'), 201);
    }

    public function updateTest(TestRequest $request, Test $test, SaveTestAction $save): JsonResponse
    {
        return ApiResponse::success(new TestResource($save->execute($request->validated(), $test)->load('parameters.ranges')), __('catalog.test_saved'));
    }

    /**
     * Parameters of a test with their reference ranges.
     */
    public function parameters(Test $test): JsonResponse
    {
        $test->load('parameters.ranges');

        return ApiResponse::success($test->parameters->map(fn ($p) => TestResource::parameter($p))->values());
    }

    public function packages(): JsonResponse
    {
        return ApiResponse::success(PackageResource::collection(Package::query()->with('tests')->orderBy('name')->get()));
    }

    public function storePackage(PackageRequest $request, SaveCatalogItemAction $save): JsonResponse
    {
        return ApiResponse::success(new PackageResource($save->package($request->validated())->load('tests')), __('catalog.package_saved'), 201);
    }

    public function updatePackage(PackageRequest $request, Package $package, SaveCatalogItemAction $save): JsonResponse
    {
        return ApiResponse::success(new PackageResource($save->package($request->validated(), $package)->load('tests')), __('catalog.package_saved'));
    }

    public function reportTemplates(): JsonResponse
    {
        return ApiResponse::success(ReportTemplate::query()->with('phrases')->orderBy('name')->get()->map(fn (ReportTemplate $t): array => [
            'id' => $t->id, 'name' => $t->name, 'type' => $t->type->value, 'department_id' => $t->department_id, 'options' => $t->resolvedOptions(),
            'default_findings' => $t->default_findings, 'default_impression' => $t->default_impression, 'is_active' => $t->is_active,
            'phrases' => $t->phrases->map->only(['shortcut', 'section', 'text'])->values(),
        ]));
    }

    public function storeReportTemplate(ReportTemplateRequest $request, SaveCatalogItemAction $save): JsonResponse
    {
        $template = $save->reportTemplate($request->validated());

        return ApiResponse::success(['id' => $template->id], __('catalog.template_saved'), 201);
    }

    public function updateReportTemplate(ReportTemplateRequest $request, ReportTemplate $reportTemplate, SaveCatalogItemAction $save): JsonResponse
    {
        $save->reportTemplate($request->validated(), $reportTemplate);

        return ApiResponse::success(['id' => $reportTemplate->id], __('catalog.template_saved'));
    }
}
