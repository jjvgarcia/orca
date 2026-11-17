<?php

use App\Domain\Exceptions\DomainRuleViolation;
use App\Enums\QuotationStatus;
use App\Models\Order;
use App\Models\Quotation;
use App\Services\OrderService;
use App\Services\QuotationService;

it('cria orçamento com snapshot de preço e totais calculados', function () {
    $customer = makeCustomer();
    $identity = makeService(640_000);
    $manual = makeService(320_000, ['name' => 'Manual de marca']);

    $quotation = makeQuotation($customer, [
        ['service_id' => $identity->id, 'quantity' => 1, 'discount_cents' => 50_000],
        ['service_id' => $manual->id, 'quantity' => 2],
    ]);

    expect($quotation->status)->toBe(QuotationStatus::Draft)
        ->and($quotation->version)->toBe(1)
        ->and($quotation->number)->toStartWith('Q-')
        ->and($quotation->subtotal_cents)->toBe(1_280_000)
        ->and($quotation->discount_cents)->toBe(50_000)
        ->and($quotation->total_cents)->toBe(1_230_000)
        ->and($quotation->items)->toHaveCount(2)
        ->and($quotation->items->first()->unit_price_cents)->toBe(640_000)
        ->and($quotation->items->first()->total_cents)->toBe(590_000)
        ->and($quotation->items->last()->description)->toBe('Manual de marca')
        ->and($quotation->events()->count())->toBe(1);
});

it('revisa gerando nova versão sem alterar a anterior', function () {
    $service = makeService(100_000);
    $customer = makeCustomer();
    $v1 = makeQuotation($customer, [['service_id' => $service->id, 'quantity' => 2]]);
    $v1Total = $v1->total_cents;

    $service->update(['unit_price_cents' => 250_000]);

    $v2 = app(QuotationService::class)->revise(
        $v1,
        [['service_id' => $service->id, 'quantity' => 1]],
        today()->addDays(10)->toDateString(),
        3,
        20,
        'user:test@orca.test',
    );

    expect($v2->version)->toBe(2)
        ->and($v2->parent_id)->toBe($v1->id)
        ->and($v2->number)->toBe($v1->number)
        ->and($v2->total_cents)->toBe(250_000)
        ->and($v1->fresh()->total_cents)->toBe($v1Total)
        ->and($v1->fresh()->items->first()->unit_price_cents)->toBe(100_000);
});

it('envia apenas rascunho e registra o evento', function () {
    $service = makeService(100_000);
    $quotation = makeQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);

    $sent = app(QuotationService::class)->send($quotation, 'user:test@orca.test');

    expect($sent->status)->toBe(QuotationStatus::Sent)
        ->and($sent->events()->count())->toBe(2)
        ->and($sent->events->last()->to_status)->toBe(QuotationStatus::Sent);
});

it('deriva expiração pela data sem gravar status expirado', function () {
    $service = makeService(100_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);

    $quotation->update(['valid_until' => today()->subDay()]);

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Sent)
        ->and($quotation->fresh()->effectiveStatus())->toBe(QuotationStatus::Expired)
        ->and($quotation->fresh()->isEditable())->toBeTrue();
});

it('aprova, cria pedido com parcelas e congela o preço', function () {
    $service = makeService(100_000);
    $quotation = makeSentQuotation(makeCustomer(), [
        ['service_id' => $service->id, 'quantity' => 3],
    ], installments: 3);

    $decided = app(QuotationService::class)->decide($quotation, true, '203.0.113.9', 'Pest UA');

    $order = Order::where('quotation_id', $quotation->id)->firstOrFail();

    expect($decided->status)->toBe(QuotationStatus::Approved)
        ->and($decided->decided_ip)->toBe('203.0.113.9')
        ->and($decided->decided_user_agent)->toBe('Pest UA')
        ->and($order->total_cents)->toBe(300_000)
        ->and($order->receivables)->toHaveCount(3)
        ->and($order->receivables->sum('amount_cents'))->toBe(300_000)
        ->and($order->receivables->first()->amount_cents)->toBe(100_000);

    $service->update(['unit_price_cents' => 999_999]);

    expect($order->fresh()->total_cents)->toBe(300_000)
        ->and($quotation->fresh()->items->first()->unit_price_cents)->toBe(100_000)
        ->and($quotation->fresh()->total_cents)->toBe(300_000);
});

it('não aceita segunda decisão nem segundo pedido', function () {
    $service = makeService(100_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 2]]);
    $serviceLayer = app(QuotationService::class);

    $serviceLayer->decide($quotation, true, '127.0.0.1', 'Pest UA');

    try {
        $serviceLayer->decide($quotation->fresh(), false, '127.0.0.1', 'Pest UA');
        $this->fail('A segunda decisão deveria falhar.');
    } catch (DomainRuleViolation $violation) {
        expect($violation->status)->toBe(409);
    }

    expect(Order::count())->toBe(1);

    try {
        app(OrderService::class)->openFrom($quotation->fresh());
        $this->fail('O segundo pedido deveria falhar.');
    } catch (DomainRuleViolation $violation) {
        expect($violation->status)->toBe(409);
    }

    expect(Order::count())->toBe(1);
});

it('bloqueia revisão de orçamento já decidido', function () {
    $service = makeService(100_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);
    $serviceLayer = app(QuotationService::class);
    $decided = $serviceLayer->decide($quotation, true, '127.0.0.1', 'Pest UA');

    expect(fn () => $serviceLayer->revise(
        $decided,
        [['service_id' => $service->id, 'quantity' => 1]],
        today()->addDays(5)->toDateString(),
        1,
        30,
        'user:test@orca.test',
    ))->toThrow(DomainRuleViolation::class);

    expect(Quotation::count())->toBe(1);
});

it('rejeita orçamento sem criar pedido', function () {
    $service = makeService(100_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);

    $decided = app(QuotationService::class)->decide($quotation, false, '127.0.0.1', 'Pest UA');

    expect($decided->status)->toBe(QuotationStatus::Rejected)
        ->and(Order::count())->toBe(0);
});
