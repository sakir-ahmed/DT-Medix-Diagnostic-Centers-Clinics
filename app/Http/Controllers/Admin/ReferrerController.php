<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Referrers\SaveReferrerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReferrerRequest;
use App\Http\Resources\ReferrerResource;
use App\Models\Referrer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferrerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $referrers = Referrer::query()
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('name', 'like', "%{$search}%")->orWhere('code', $search)->orWhere('mobile', 'like', '%'.ltrim($search, '0').'%')))
            ->when($request->query('type'), fn (Builder $q, $type) => $q->where('type', $type))
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        return Inertia::render('Admin/Referrers/Index', [
            'referrers' => ReferrerResource::collection($referrers),
            'filters' => ['q' => $search, 'type' => $request->query('type')],
        ]);
    }

    /**
     * Type-ahead for the reception counter.
     */
    public function lookup(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $results = Referrer::query()
            ->where('is_active', true)
            ->when($q !== '', fn (Builder $b) => $b->where(fn (Builder $w) => $w->where('name', 'like', "%{$q}%")->orWhere('code', $q)))
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(fn (Referrer $r): array => ['value' => $r->id, 'label' => $r->name, 'description' => $r->organization ?? $r->specialty, 'meta' => $r->code]);

        return response()->json(['results' => $results]);
    }

    public function store(ReferrerRequest $request, SaveReferrerAction $save): RedirectResponse
    {
        $save->execute($request->validated());

        return back()->with('success', __('referrers.created'));
    }

    public function update(ReferrerRequest $request, Referrer $referrer, SaveReferrerAction $save): RedirectResponse
    {
        $save->execute($request->validated(), $referrer);

        return back()->with('success', __('referrers.updated'));
    }
}
