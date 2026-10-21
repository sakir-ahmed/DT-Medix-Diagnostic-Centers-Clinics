<?php

declare(strict_types=1);

use App\Actions\Catalog\SaveTestAction;
use App\Actions\Center\SeedCenterDefaultsAction;
use App\Enums\ApprovalStatus;
use App\Enums\RoleName;
use App\Models\Department;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Refund;
use App\Models\Test;
use Spatie\LaravelPdf\Facades\Pdf;

beforeEach(function (): void {
    makeCenter();
    value(fn () => app(SeedCenterDefaultsAction::class)->execute());
    $this->branch = mainBranch();
    $this->other = makeBranch('BR02');
    $this->department = value(fn () => Department::query()->where('code', 'BIO')->firstOrFail());
    $this->receptionist = makeUser(RoleName::Receptionist);
    $this->manager = makeUser(RoleName::BranchManager, attributes: [
        'username' => 'counter-manager',
        'approval_pin' => '2468',
    ]);
    $this->patient = value(fn () => Patient::factory()->create(['name' => 'Karim Ali']));
    $this->test = value(fn () => app(SaveTestAction::class)->execute([
        'code' => 'CBC',
        'name' => 'CBC test',
        'department_id' => $this->department->id,
        'price' => '500',
        'tat_hours' => 4,
        'sample_type' => 'blood',
        'container' => 'edta',
    ]));
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function orderPayload(Patient $patient, Test $test, array $overrides = []): array
{
    return $overrides + [
        'patient_id' => $patient->id,
        'test_ids' => [$test->id],
        'payments' => [['amount' => '200.00', 'method' => 'cash']],
    ];
}

it('saves a cash order from the counter and opens the print view', function (): void {
    $this->actingAs($this->receptionist)->get(route('admin.reception.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Reception/Index'));

    $this->actingAs($this->receptionist)->post(route('admin.reception.store'), orderPayload($this->patient, $this->test))
        ->assertRedirect(route('admin.reception.index'))
        ->assertSessionHas('print_order_id');

    $order = value(fn () => Order::query()->with(['invoice', 'samples'])->firstOrFail());

    expect($order->invoice->due)->toBe('300.00')->and($order->invoice->paid)->toBe('200.00');

    $this->actingAs($this->receptionist)->get(route('admin.orders.print', $order))
        ->assertOk()
        ->assertSee($order->invoice->invoice_no)
        ->assertSee($order->samples[0]->sample_code)
        ->assertDontSee('portal');

    $this->actingAs($this->receptionist)->get(route('admin.patients.show', $this->patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('ordered_tests', 1)->has('invoices', 1)->where('invoices.0.due', '300.00'));
});

it('rejects a payment above the due and a missing idempotency key', function (): void {
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;
    $created = $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/orders", orderPayload($this->patient, $this->test, ['payments' => []]), ['Idempotency-Key' => 'order-1'])
        ->assertCreated();
    $invoiceId = $created->json('data.invoice.id');

    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/invoices/{$invoiceId}/payments", [
        'lines' => [['amount' => '900.00', 'method' => 'cash']],
    ], ['Idempotency-Key' => 'pay-over'])->assertStatus(422)->assertJsonPath('error.code', 'PAYMENT_EXCEEDS_DUE');

    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/orders", orderPayload($this->patient, $this->test))
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_REQUIRED');
});

it('holds an over-limit discount until a manager approves it', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.reception.store'), orderPayload($this->patient, $this->test, [
        'payments' => [],
        'discount_type' => 'percent',
        'discount_value' => '50',
        'discount_reason' => 'Staff relative',
    ]))->assertRedirect();

    $invoice = value(fn () => Invoice::query()->firstOrFail());
    expect($invoice->total)->toBe('500.00');
    $request = value(fn () => DiscountRequest::query()->firstOrFail());
    expect($request->status)->toBe(ApprovalStatus::Pending);

    $this->actingAs($this->manager)->post(route('admin.approvals.decide', $request), [
        'approve' => true,
        'approval_login' => 'counter-manager',
        'approval_pin' => '2468',
    ])->assertRedirect();

    expect($invoice->fresh()->discount_amount)->toBe('250.00');
});

it('cancels an unordered line and approves the refund of cash already taken', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.reception.store'), orderPayload($this->patient, $this->test, [
        'payments' => [['amount' => '500.00', 'method' => 'cash']],
    ]))->assertRedirect();

    $order = value(fn () => Order::query()->with('items')->firstOrFail());

    $this->actingAs($this->receptionist)->post(route('admin.orders.cancel', $order), [
        'item_ids' => [$order->items[0]->id],
        'reason' => 'Patient left',
    ])->assertRedirect();

    $refund = value(fn () => Refund::query()->firstOrFail());
    expect($refund->status)->toBe(ApprovalStatus::Pending)->and($refund->amount)->toBe('500.00');

    $this->actingAs($this->manager)->post(route('admin.refunds.decide', $refund), [
        'approve' => true,
        'approval_login' => 'counter-manager',
        'approval_pin' => '2468',
    ])->assertRedirect();

    expect($refund->fresh()->status)->toBe(ApprovalStatus::Approved);
});

it('returns a pdf invoice without a portal qr', function (): void {
    Pdf::fake();
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;
    $invoiceId = $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/orders", orderPayload($this->patient, $this->test, ['payments' => []]), ['Idempotency-Key' => 'order-pdf'])
        ->assertCreated()
        ->json('data.invoice.id');

    $this->withToken($token)->get("/api/v1/branches/{$this->branch->id}/invoices/{$invoiceId}/print?format=a5")->assertOk();

    Pdf::assertRespondedWithPdf(fn ($pdf): bool => $pdf->viewName === 'prints.invoice');
});

it('returns 404 for another branch', function (): void {
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;
    $orderId = $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/orders", orderPayload($this->patient, $this->test, ['payments' => []]), ['Idempotency-Key' => 'order-iso'])
        ->json('data.id');
    $invoiceId = value(fn () => Invoice::query()->value('id'));

    $this->withToken($token)->getJson("/api/v1/branches/{$this->other->id}/orders/{$orderId}")->assertNotFound();
    $this->withToken($token)->postJson("/api/v1/branches/{$this->other->id}/orders", orderPayload($this->patient, $this->test), ['Idempotency-Key' => 'order-branch'])->assertNotFound();
    $this->withToken($token)->postJson("/api/v1/branches/{$this->other->id}/orders/{$orderId}/cancel", ['item_ids' => [1], 'reason' => 'No'])->assertNotFound();
    $this->withToken($token)->postJson("/api/v1/branches/{$this->other->id}/invoices/{$invoiceId}/payments", ['lines' => [['amount' => '10.00']]], ['Idempotency-Key' => 'pay-branch'])->assertNotFound();
    $this->withToken($token)->getJson("/api/v1/branches/{$this->other->id}/invoices/{$invoiceId}/print")->assertNotFound();
    $this->withToken($token)->postJson("/api/v1/branches/{$this->other->id}/refunds", ['invoice_id' => $invoiceId, 'amount' => '1.00', 'reason' => 'No'])->assertNotFound();

    $this->withoutToken()->actingAs($this->receptionist)->get(route('admin.orders.show', $orderId))->assertOk();
    $foreignOrder = value(fn () => Order::query()->create([
        'branch_id' => $this->other->id,
        'patient_id' => Patient::factory()->create()->id,
        'urgency' => 'routine',
        'status' => 'active',
    ]));
    $this->actingAs($this->receptionist)->get(route('admin.orders.show', $foreignOrder))->assertNotFound();
});

it('refuses the counter to a role that cannot create orders', function (): void {
    $doctor = makeUser(RoleName::Doctor);

    $this->actingAs($doctor)->get(route('admin.reception.index'))->assertForbidden();
    $this->actingAs($doctor)->post(route('admin.reception.store'), orderPayload($this->patient, $this->test))->assertForbidden();
});
