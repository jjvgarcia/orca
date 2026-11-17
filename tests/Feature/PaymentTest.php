<?php

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\PaymentService;

function paymentPayload(int $receivableId, array $overrides = []): array
{
    return [
        'receivable_id' => $receivableId,
        'amount_cents' => 100_000,
        'method' => 'pix',
        'paid_at' => today()->toDateString(),
        'idempotency_key' => 'clique-unico-1',
        ...$overrides,
    ];
}

it('registra pagamento e devolve o mesmo resultado ao repetir a chave', function () {
    $user = admin();
    $order = createApprovedOrder();
    $receivable = $order->receivables->first();

    $this->actingAs($user)
        ->postJson(route('payments.store'), paymentPayload($receivable->id))
        ->assertCreated()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('replayed', false);

    $this->actingAs($user)
        ->postJson(route('payments.store'), paymentPayload($receivable->id))
        ->assertOk()
        ->assertJsonPath('replayed', true);

    expect(Payment::count())->toBe(1)
        ->and($receivable->fresh()->status())->toBe(ReceivableStatus::Paid);
});

it('rejeita a mesma chave com payload diferente', function () {
    $user = admin();
    $order = createApprovedOrder();
    $receivable = $order->receivables->first();

    $this->actingAs($user)
        ->postJson(route('payments.store'), paymentPayload($receivable->id, ['amount_cents' => 40_000]))
        ->assertCreated();

    $this->actingAs($user)
        ->postJson(route('payments.store'), paymentPayload($receivable->id))
        ->assertStatus(409)
        ->assertJsonPath('ok', false);

    expect(Payment::count())->toBe(1);
});

it('rejeita sobrepagamento e valor inválido', function () {
    $user = admin();
    $order = createApprovedOrder(unitPrice: 100_000);
    $receivable = $order->receivables->first();

    $this->actingAs($user)
        ->postJson(route('payments.store'), paymentPayload($receivable->id, ['amount_cents' => 100_001]))
        ->assertStatus(422);

    $this->actingAs($user)
        ->postJson(route('payments.store'), paymentPayload($receivable->id, ['amount_cents' => 0]))
        ->assertStatus(422);

    expect(Payment::count())->toBe(0);
});

it('exige chave de idempotência', function () {
    $user = admin();
    $order = createApprovedOrder();
    $receivable = $order->receivables->first();
    $payload = paymentPayload($receivable->id);
    unset($payload['idempotency_key']);

    $this->actingAs($user)
        ->postJson(route('payments.store'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('idempotency_key');
});

it('marca parcela como parcial e calcula o saldo', function () {
    $order = createApprovedOrder(unitPrice: 100_000);
    $receivable = $order->receivables->first();

    app(PaymentService::class)->register(
        $receivable,
        40_000,
        PaymentMethod::Transfer,
        today(),
        'parcial-1',
    );

    $receivable = $receivable->fresh();

    expect($receivable->paidCents())->toBe(40_000)
        ->and($receivable->outstandingCents())->toBe(60_000)
        ->and($receivable->status())->toBe(ReceivableStatus::Partial);
});

it('bloqueia pagamento em pedido cancelado', function () {
    $user = admin();
    $order = createApprovedOrder();

    app(OrderService::class)->cancel($order);

    $this->actingAs($user)
        ->postJson(route('payments.store'), paymentPayload($order->receivables->first()->id))
        ->assertStatus(422);

    expect(Payment::count())->toBe(0);
});

it('não excede o total mesmo somando pagamentos parciais', function () {
    $user = admin();
    $order = createApprovedOrder(unitPrice: 100_000);
    $receivable = $order->receivables->first();

    $this->actingAs($user)->postJson(route('payments.store'), paymentPayload($receivable->id, [
        'amount_cents' => 60_000,
        'idempotency_key' => 'parcial-a',
    ]))->assertCreated();

    $this->actingAs($user)->postJson(route('payments.store'), paymentPayload($receivable->id, [
        'amount_cents' => 60_000,
        'idempotency_key' => 'parcial-b',
    ]))->assertStatus(422);

    expect(Payment::count())->toBe(1);
});
