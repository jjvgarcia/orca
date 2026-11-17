<?php

use App\Enums\PaymentMethod;
use App\Enums\ReceivableStatus;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\QuotationService;
use Illuminate\Support\Facades\Schema;

it('divide o pedido em parcelas que somam exatamente o total', function (int $total, int $installments) {
    $service = makeService($total);
    $quotation = makeSentQuotation(makeCustomer(), [
        ['service_id' => $service->id, 'quantity' => 1],
    ], $installments);

    app(QuotationService::class)->decide($quotation, true, '127.0.0.1', 'Pest UA');

    $order = Order::where('quotation_id', $quotation->id)->firstOrFail();
    $receivables = $order->receivables;

    expect($receivables)->toHaveCount($installments)
        ->and($receivables->sum('amount_cents'))->toBe($total);

    $first = $receivables->first()->amount_cents;
    $others = $receivables->slice(1)->pluck('amount_cents');

    expect($first)->toBeGreaterThanOrEqual($others->max())
        ->and($others->unique())->toHaveCount(1);
})->with([
    '1000,00 em 3x' => [100_000, 3],
    '1000,00 em 7x' => [100_000, 7],
    '1000,00 em 11x' => [100_000, 11],
]);

it('deriva os quatro estados na leitura', function () {
    $order = createApprovedOrder(unitPrice: 300_000, installments: 3);
    [$first, $second, $third] = $order->receivables->all();
    $payments = app(PaymentService::class);

    $payments->register($first, $first->amount_cents, PaymentMethod::Pix, today(), 'paga');
    $payments->register($second, intdiv($second->amount_cents, 2), PaymentMethod::Cash, today(), 'parcial');
    $third->update(['due_date' => today()->subDays(3)]);

    expect($first->fresh()->status())->toBe(ReceivableStatus::Paid)
        ->and($second->fresh()->status())->toBe(ReceivableStatus::Partial)
        ->and($third->fresh()->status())->toBe(ReceivableStatus::Overdue);
});

it('considera vencida a parcela parcialmente paga e atrasada', function () {
    $order = createApprovedOrder(unitPrice: 100_000);
    $receivable = $order->receivables->first();

    app(PaymentService::class)->register($receivable, 30_000, PaymentMethod::Pix, today(), 'atrasada');
    $receivable->update(['due_date' => today()->subDays(2)]);

    expect($receivable->fresh()->status())->toBe(ReceivableStatus::Overdue)
        ->and($receivable->fresh()->outstandingCents())->toBe(70_000);
});

it('não grava coluna de status: qualquer parcela tem status derivado', function () {
    $columns = Schema::getColumnListing('receivables');

    expect($columns)->not->toContain('status');
});
