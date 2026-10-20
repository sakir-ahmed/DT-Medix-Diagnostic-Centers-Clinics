<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Lab\CollectSampleAction;
use App\Actions\Lab\ReceiveSampleAction;
use App\Actions\Lab\RejectSampleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectSampleRequest;
use App\Http\Resources\SampleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Branch;
use App\Models\Sample;
use App\Services\Billing\PrintDocument;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * @tags Samples
 */
class SampleController extends Controller
{
    /**
     * Mark a sample as collected (prints from the collection screen).
     */
    public function collect(Branch $branch, Sample $sample, Request $request, CollectSampleAction $action): JsonResponse
    {
        abort_if($sample->branch_id !== $branch->id, 404);

        return ApiResponse::success(new SampleResource($action->execute($sample, $request->user())));
    }

    public function receive(Branch $branch, Sample $sample, Request $request, ReceiveSampleAction $action): JsonResponse
    {
        abort_if($sample->branch_id !== $branch->id, 404);

        return ApiResponse::success(new SampleResource($action->execute($sample, $request->user())));
    }

    public function reject(Branch $branch, Sample $sample, RejectSampleRequest $request, RejectSampleAction $action): JsonResponse
    {
        abort_if($sample->branch_id !== $branch->id, 404);

        return ApiResponse::success(new SampleResource($action->execute(
            $sample,
            $request->string('reason')->toString(),
            $request->boolean('request_recollection'),
            $request->user(),
        )));
    }

    /**
     * The Code 128 sample label as a PDF.
     */
    public function label(Branch $branch, Sample $sample, PrintDocument $documents): Responsable
    {
        abort_if($sample->branch_id !== $branch->id, 404);
        $sample->loadMissing(['patient', 'items', 'branch']);

        return Pdf::view('prints.label-single', ['label' => $documents->label($sample)])
            ->name($sample->sample_code.'.pdf')
            ->inline($sample->sample_code.'.pdf');
    }
}
