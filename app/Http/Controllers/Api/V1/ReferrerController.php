<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Referrers\SaveReferrerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReferrerRequest;
use App\Http\Resources\ReferrerResource;
use App\Http\Responses\ApiResponse;
use App\Models\Referrer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Referrers
 */
class ReferrerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $referrers = Referrer::query()
            ->when($request->input('filter.q'), fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->paginate(min(100, (int) $request->query('per_page', 25)));

        return ApiResponse::success(ReferrerResource::collection($referrers));
    }

    public function store(ReferrerRequest $request, SaveReferrerAction $save): JsonResponse
    {
        return ApiResponse::success(new ReferrerResource($save->execute($request->validated())), __('referrers.created'), 201);
    }

    public function update(ReferrerRequest $request, Referrer $referrer, SaveReferrerAction $save): JsonResponse
    {
        return ApiResponse::success(new ReferrerResource($save->execute($request->validated(), $referrer)), __('referrers.updated'));
    }
}
