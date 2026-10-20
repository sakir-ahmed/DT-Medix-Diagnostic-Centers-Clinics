<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorResource;
use App\Http\Responses\ApiResponse;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Doctors
 */
class DoctorController extends Controller
{
    /**
     * Active doctors of the center.
     */
    public function index(Request $request): JsonResponse
    {
        $doctors = Doctor::query()
            ->where('is_active', true)
            ->when($request->input('filter.q'), fn ($q, $term) => $q->where('name', 'like', '%'.$term.'%'))
            ->orderBy('name')
            ->paginate(min(100, (int) $request->query('per_page', 50)));

        return ApiResponse::success(DoctorResource::collection($doctors));
    }
}
