<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Lab\CollectSampleAction;
use App\Actions\Lab\ReceiveSampleAction;
use App\Actions\Lab\RejectSampleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectSampleRequest;
use App\Http\Resources\SampleResource;
use App\Models\Sample;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sample collection counter and the lab receive (scan) screen.
 */
class SampleController extends Controller
{
    public function collection(Request $request): Response
    {
        $samples = Sample::query()
            ->with(['patient', 'items.test', 'order'])
            ->pending()
            ->orderBy('id')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        return Inertia::render('Admin/Samples/Collection', [
            'samples' => SampleResource::collection($samples),
        ]);
    }

    public function collect(Request $request, Sample $sample, CollectSampleAction $action): RedirectResponse
    {
        $action->execute($sample, $request->user());

        return back()->with('success', __('lab.collected', ['code' => $sample->sample_code]));
    }

    public function receive(Request $request): Response
    {
        $awaiting = Sample::query()
            ->with(['patient', 'items.test', 'order'])
            ->awaitingReceive()
            ->orderBy('id')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        $received = Sample::query()
            ->with(['patient', 'items'])
            ->where('status', 'received')
            ->orderByDesc('received_at')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Samples/Receive', [
            'awaiting' => SampleResource::collection($awaiting),
            'received' => SampleResource::collection($received)->resolve(),
        ]);
    }

    public function scan(Request $request, ReceiveSampleAction $action): RedirectResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $code = strtoupper(trim($validated['code']));

        /** @var Sample|null $sample */
        $sample = Sample::query()->where('sample_code', $code)->first();

        if ($sample === null) {
            return back()->with('error', __('lab.sample_not_found', ['code' => $code]))->withInput();
        }

        $action->execute($sample, $request->user());

        return back()->with('success', __('lab.received_sample', ['code' => $sample->sample_code]));
    }

    public function reject(RejectSampleRequest $request, Sample $sample, RejectSampleAction $action): RedirectResponse
    {
        $action->execute($sample, $request->string('reason')->toString(), $request->boolean('request_recollection'), $request->user());

        return back()->with('success', __('lab.rejected', ['code' => $sample->sample_code]));
    }
}
