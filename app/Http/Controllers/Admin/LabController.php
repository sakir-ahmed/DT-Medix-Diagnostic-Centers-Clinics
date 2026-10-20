<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Lab\AcknowledgeCriticalAction;
use App\Actions\Lab\SaveResultsAction;
use App\Actions\Lab\StoreResultAttachmentAction;
use App\Actions\Lab\SubmitResultsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcknowledgeCriticalRequest;
use App\Http\Requests\ResultAttachmentRequest;
use App\Http\Requests\SaveResultsRequest;
use App\Http\Resources\SampleResource;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ResultAttachment;
use App\Models\Sample;
use App\Models\TestResult;
use App\Services\Lab\ResultRows;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Lab worklist, result entry (grid and narrative), the critical result
 * log and narrative attachments.
 */
class LabController extends Controller
{
    public function __construct(private readonly BranchContext $context) {}

    public function worklist(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $departmentId = (int) $request->query('department', 0);
        $status = (string) $request->query('status', '');
        $urgency = (string) $request->query('urgency', '');

        $samples = Sample::query()
            ->with(['patient', 'items', 'order'])
            ->whereIn('samples.status', ['collected', 'received'])
            ->when($status !== '', fn ($query) => $query->where('samples.status', $status))
            ->when($urgency !== '', fn ($query) => $query->where('orders.urgency', $urgency))
            ->when($departmentId > 0, fn ($query) => $query->whereHas(
                'items',
                fn ($items) => $items->where('department_id', $departmentId)->whereNotIn('order_items.status', ['cancelled']),
            ))
            ->when($search !== '', fn ($query) => $query->where('samples.sample_code', 'like', $search.'%'))
            ->join('orders', 'orders.id', '=', 'samples.order_id')
            ->select('samples.*')
            ->selectRaw("CASE WHEN orders.urgency = 'urgent' THEN 0 ELSE 1 END as urgent_rank")
            ->selectRaw('(select min(expected_at) from order_items where order_items.sample_id = samples.id and order_items.status != ?) as due_at', ['cancelled'])
            ->orderBy('urgent_rank')
            ->orderBy('due_at')
            ->orderBy('samples.id')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        return Inertia::render('Admin/Lab/Worklist', [
            'samples' => SampleResource::collection($samples),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name'])->map(fn (Department $department): array => ['id' => $department->id, 'name' => $department->name])->all(),
            'filters' => ['q' => $search, 'department' => $departmentId, 'status' => $status, 'urgency' => $urgency],
        ]);
    }

    public function show(Sample $sample): Response
    {
        $sample->load(['patient', 'items.test.department', 'order', 'collectedBy', 'receivedBy']);

        return Inertia::render('Admin/Lab/Sample', [
            'sample' => (new SampleResource($sample))->resolve(),
        ]);
    }

    public function results(Request $request, OrderItem $item, ResultRows $rows): Response
    {
        $this->authorizeItem($item);
        $item->loadMissing(['test.parameters', 'test.reportTemplate.phrases', 'sample', 'order.patient', 'attachments', 'narrative', 'results']);

        $template = $item->test->reportTemplate;
        $hasParameters = $item->test->parameters->where('is_active', true)->isNotEmpty();

        return Inertia::render('Admin/Lab/ResultEntry', [
            'item' => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'status' => $item->status->value,
                'expected_at' => $item->expected_at?->toIso8601String(),
                'urgency' => $item->order->urgency,
                'method' => $item->test->method,
                'interpretation' => $item->test->interpretation,
            ],
            'sample' => [
                'id' => $item->sample_id,
                'sample_code' => $item->sample?->sample_code,
                'sample_type' => $item->sample?->sample_type,
                'collected_at' => $item->sample?->collected_at?->toIso8601String(),
                'received_at' => $item->sample?->received_at?->toIso8601String(),
                'status' => $item->sample?->status->value,
            ],
            'patient' => [
                'id' => $item->order->patient->id,
                'name' => $item->order->patient->name,
                'name_bn' => $item->order->patient->name_bn,
                'patient_code' => $item->order->patient->patient_code,
                'sex' => $item->order->patient->sex->value,
                'date_of_birth' => $item->order->patient->date_of_birth?->toDateString(),
                'age_text' => $item->order->patient->ageText(),
                'mobile_display' => $item->order->patient->mobileDisplay(),
                'blood_group' => $item->order->patient->blood_group,
                'allergies' => $item->order->patient->allergies,
            ],
            'is_narrative' => ! $hasParameters,
            'rows' => $rows->forItem($item),
            'narrative' => [
                'findings' => data_get($item, 'narrative.findings') ?? ($hasParameters ? null : $template?->default_findings),
                'impression' => data_get($item, 'narrative.impression') ?? ($hasParameters ? null : $template?->default_impression),
            ],
            'phrases' => $template?->phrases->map(fn ($phrase): array => ['shortcut' => $phrase->shortcut, 'text' => $phrase->text])->all() ?? [],
            'attachments' => $item->attachments->map(fn (ResultAttachment $attachment): array => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'size' => $attachment->size,
            ])->all(),
            'delta_percent' => (float) (center()?->setting('delta_check_percent') ?? 50),
            'next_url' => $this->nextEntryUrl($item),
        ]);
    }

    public function save(OrderItem $item, SaveResultsRequest $request, SaveResultsAction $action): RedirectResponse
    {
        $this->authorizeItem($item);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $action->execute($item, $data, $request->user());

        return back()->with('success', __('lab.draft_saved'));
    }

    public function submit(Request $request, OrderItem $item, SubmitResultsAction $action): RedirectResponse
    {
        $this->authorizeItem($item);
        $action->execute($item, $request->user());

        return redirect()->route('admin.lab.worklist')->with('success', __('lab.submitted', ['test' => $item->name]));
    }

    public function acknowledge(OrderItem $item, AcknowledgeCriticalRequest $request, AcknowledgeCriticalAction $action): RedirectResponse
    {
        $this->authorizeItem($item);
        $action->execute($item, $request->string('informed_name')->toString(), $request->user(), $request->input('informed_at') !== null ? (string) $request->input('informed_at') : null);

        return back()->with('success', __('lab.critical_acknowledged'));
    }

    public function critical(Request $request): Response
    {
        $pending = $request->string('status')->toString() === 'pending';

        $results = TestResult::query()
            ->where('flag', 'C')
            ->when($pending, fn ($query) => $query->whereNull('critical_ack_at'))
            ->with(['item.test', 'item.order.patient', 'item.sample'])
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        return Inertia::render('Admin/Lab/Critical', [
            'results' => $results->through(fn (TestResult $result): array => [
                'id' => $result->id,
                'item_id' => $result->order_item_id,
                'test_name' => $result->item->name,
                'parameter_name' => $result->name,
                'value' => $result->value,
                'unit' => $result->unit,
                'range_text' => $result->range_text,
                'patient' => [
                    'name' => $result->item->order->patient->name,
                    'patient_code' => $result->item->order->patient->patient_code,
                ],
                'sample_code' => $result->item->sample?->sample_code,
                'entered_at' => $result->updated_at?->toIso8601String(),
                'acknowledged' => $result->critical_ack_at !== null,
                'informed_name' => $result->critical_informed_name,
                'informed_at' => $result->critical_informed_at?->toIso8601String(),
                'acknowledged_at' => $result->critical_ack_at?->toIso8601String(),
            ])->toArray(),
            'filters' => ['status' => $pending ? 'pending' : ''],
        ]);
    }

    public function attach(OrderItem $item, ResultAttachmentRequest $request, StoreResultAttachmentAction $action): RedirectResponse
    {
        $this->authorizeItem($item);
        $file = $request->file('file');
        abort_if($file === null, 422);
        $action->execute($item, $file, $request->user());

        return back()->with('success', __('lab.attachment_added'));
    }

    public function destroyAttachment(ResultAttachment $attachment, StoreResultAttachmentAction $action): RedirectResponse
    {
        $this->authorizeAttachment($attachment);
        $action->delete($attachment);

        return back()->with('success', __('lab.attachment_removed'));
    }

    public function download(ResultAttachment $attachment): StreamedResponse
    {
        $this->authorizeAttachment($attachment);

        $disk = Storage::disk((string) config('center.storage_disk'));
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name);
    }

    /**
     * Attachments follow their order item's branch (see authorizeItem).
     */
    private function authorizeAttachment(ResultAttachment $attachment): void
    {
        $item = OrderItem::query()->withoutGlobalScopes()->whereKey($attachment->order_item_id)->firstOrFail();
        $this->authorizeItem($item);
    }

    /**
     * An order item carries no branch column: keep branch users on their
     * own branch through the sample's branch.
     */
    /**
     * An order item carries no branch column: keep branch users on their
     * own branch. The lookup ignores global scopes because the branch
     * scope itself hides the sample from users of other branches.
     */
    private function authorizeItem(OrderItem $item): void
    {
        $branchId = Sample::query()->withoutGlobalScopes()->whereKey($item->sample_id)->value('branch_id');

        if ($branchId === null) {
            $branchId = Order::query()->withoutGlobalScopes()->whereKey($item->order_id)->value('branch_id');
        }

        $allowed = $this->context->branchIds();
        abort_if($allowed !== null && ! in_array((int) $branchId, $allowed, true), 404);
    }

    private function nextEntryUrl(OrderItem $item): string
    {
        $next = OrderItem::query()
            ->where('status', 'received')
            ->whereNot('id', $item->id)
            ->whereHas('sample', fn ($sample) => $sample->whereNotIn('samples.status', ['rejected']))
            ->whereHas('sample.order', fn ($order) => $order->where('branch_id', $item->order->branch_id))
            ->when($item->department_id !== null, fn ($query) => $query->where('department_id', $item->department_id))
            ->orderBy('expected_at')
            ->orderBy('id')
            ->first();

        return $next === null ? route('admin.lab.worklist') : route('admin.lab.results', $next);
    }
}
