<?php

use App\Livewire\Customers\Form as CustomerForm;
use App\Livewire\Customers\Index as CustomersIndex;
use App\Livewire\Dashboard;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Livewire\Orders\Show as OrderShow;
use App\Livewire\Quotations\Builder;
use App\Livewire\Quotations\Index as QuotationsIndex;
use App\Livewire\Quotations\Show as QuotationShow;
use App\Livewire\Services\Index as ServicesIndex;
use App\Models\Quotation;
use Livewire\Livewire;

it('renderiza todas as telas principais', function () {
    $user = admin();
    $customer = makeCustomer();
    $service = makeService(100_000);
    $quotation = makeQuotation($customer, [['service_id' => $service->id, 'quantity' => 1]]);
    $order = createApprovedOrder();

    Livewire::actingAs($user)->test(Dashboard::class)->assertOk();
    Livewire::actingAs($user)->test(CustomersIndex::class)->assertOk()->assertSee($customer->name);
    Livewire::actingAs($user)->test(CustomerForm::class)->assertOk();
    Livewire::actingAs($user)->test(CustomerForm::class, ['customer' => $customer])->assertOk()->assertSee('Editar cliente')->assertSee($quotation->number);
    Livewire::actingAs($user)->test(ServicesIndex::class)->assertOk()->assertSee($service->name);
    Livewire::actingAs($user)->test(QuotationsIndex::class)->assertOk()->assertSee($quotation->number);
    Livewire::actingAs($user)->test(QuotationShow::class, ['quotation' => $quotation])->assertOk()->assertSee($quotation->number);
    Livewire::actingAs($user)->test(OrdersIndex::class)->assertOk()->assertSee($order->number);
    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])->assertOk()->assertSee($order->number);
});

it('cria orçamento pelo formulário do painel', function () {
    $user = admin();
    $customer = makeCustomer();
    $service = makeService(120_000);

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->set('customerId', $customer->id)
        ->set('validUntil', today()->addDays(10)->toDateString())
        ->set('installments', 3)
        ->set('items.0.service_id', $service->id)
        ->set('items.0.quantity', 2)
        ->call('saveDraft')
        ->assertRedirect();

    $quotation = Quotation::firstOrFail();

    expect($quotation->customer_id)->toBe($customer->id)
        ->and($quotation->total_cents)->toBe(240_000)
        ->and($quotation->installments)->toBe(3)
        ->and($quotation->status->value)->toBe('draft');
});

it('abre o formulário de pagamento com chave de idempotência', function () {
    $user = admin();
    $order = createApprovedOrder(unitPrice: 100_000);
    $receivable = $order->receivables->first();

    Livewire::actingAs($user)
        ->test(OrderShow::class, ['order' => $order])
        ->call('startPayment', $receivable->id)
        ->assertSet('payingId', $receivable->id)
        ->assertSet('amount', '1.000,00')
        ->assertSee('Registrar pagamento');
});

it('mostra parcela vencida no painel sem job agendado', function () {
    $user = admin();
    $customer = makeCustomer(['name' => 'Cliente Atrasado']);
    $service = makeService(100_000);
    $quotation = makeSentQuotation($customer, [['service_id' => $service->id, 'quantity' => 1]]);
    app(App\Services\QuotationService::class)->decide($quotation, true, '127.0.0.1', 'Pest UA');

    App\Models\Receivable::where('order_id', App\Models\Order::first()->id)->update(['due_date' => today()->subDays(4)]);

    Livewire::actingAs($user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Cliente Atrasado')
        ->assertSee('em atraso');
});
