<?php

use App\Enums\QuotationStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PublicLink;

it('abre o link assinado sem login', function () {
    $service = makeService(100_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 2]]);

    $this->get(PublicLink::to($quotation))
        ->assertOk()
        ->assertSee($quotation->number)
        ->assertSee('Aprovar orçamento');
});

it('recusa link sem assinatura ou expirado', function () {
    $service = makeService(100_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);

    $this->get(route('quotation.public', $quotation->id))->assertForbidden();

    $url = PublicLink::to($quotation);
    $this->travel(31)->days();

    $this->get($url)->assertForbidden();
});

it('aprova pelo link e cria o pedido com o total do orçamento', function () {
    $service = makeService(150_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 2]], installments: 2);

    $this->post(PublicLink::decision($quotation), ['decision' => 'approve'])->assertRedirect();

    $quotation->refresh();
    $order = Order::where('quotation_id', $quotation->id)->firstOrFail();

    expect($quotation->status)->toBe(QuotationStatus::Approved)
        ->and($order->total_cents)->toBe($quotation->total_cents)
        ->and($order->receivables->sum('amount_cents'))->toBe($order->total_cents)
        ->and($quotation->decided_ip)->not->toBeNull()
        ->and($quotation->decided_at)->not->toBeNull();

    $this->get(PublicLink::to($quotation))->assertOk()->assertSee('aprovado');
});

it('não reabre a decisão em um segundo envio', function () {
    $service = makeService(150_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);
    $url = PublicLink::decision($quotation);

    $this->post($url, ['decision' => 'approve'])->assertRedirect();
    $this->post($url, ['decision' => 'approve'])->assertRedirect()->assertSessionHas('error');

    expect(Order::count())->toBe(1)
        ->and(Payment::count())->toBe(0);
});

it('recusa orçamento pelo link sem gerar pedido', function () {
    $service = makeService(150_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);

    $this->post(PublicLink::decision($quotation), ['decision' => 'reject'])->assertRedirect();

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Rejected)
        ->and(Order::count())->toBe(0);
});

it('não aceita decisão em orçamento expirado', function () {
    $service = makeService(150_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);
    $url = PublicLink::decision($quotation);

    $quotation->update(['valid_until' => today()->subDay()]);

    $this->post($url, ['decision' => 'approve'])->assertRedirect()->assertSessionHas('error');

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Sent)
        ->and(Order::count())->toBe(0);
});

it('entrega o PDF do orçamento por link assinado', function () {
    $service = makeService(150_000);
    $quotation = makeSentQuotation(makeCustomer(), [['service_id' => $service->id, 'quantity' => 1]]);

    $response = $this->get(PublicLink::pdf($quotation));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
