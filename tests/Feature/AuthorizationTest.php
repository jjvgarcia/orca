<?php

use App\Livewire\Customers\Form as CustomerForm;
use App\Livewire\Services\Index as ServicesIndex;
use Livewire\Livewire;

it('nega ao operador apagar cliente e alterar preço de catálogo', function () {
    $operator = operator();
    $admin = admin();
    $customer = makeCustomer();
    $service = makeService(100_000);

    expect($operator->can('delete', $customer))->toBeFalse()
        ->and($admin->can('delete', $customer))->toBeTrue()
        ->and($operator->can('update', $service))->toBeFalse()
        ->and($admin->can('update', $service))->toBeTrue()
        ->and($operator->can('create', App\Models\Service::class))->toBeFalse()
        ->and($operator->can('create', App\Models\Customer::class))->toBeTrue();
});

it('bloqueia a ação de arquivar cliente para operador', function () {
    $operator = operator();
    $customer = makeCustomer();

    Livewire::actingAs($operator)
        ->test(CustomerForm::class, ['customer' => $customer])
        ->call('delete')
        ->assertStatus(403);

    expect($customer->fresh()->trashed())->toBeFalse();
});

it('bloqueia a edição de serviço para operador', function () {
    $operator = operator();
    $service = makeService(100_000);

    Livewire::actingAs($operator)
        ->test(ServicesIndex::class)
        ->call('startEdit', $service->id)
        ->assertStatus(403);
});

it('permite ao administrador arquivar cliente sem apagar o histórico', function () {
    $admin = admin();
    $customer = makeCustomer();
    $service = makeService(100_000);
    $quotation = makeQuotation($customer, [['service_id' => $service->id, 'quantity' => 1]]);

    Livewire::actingAs($admin)
        ->test(CustomerForm::class, ['customer' => $customer])
        ->call('delete')
        ->assertRedirect(route('customers.index'));

    expect($customer->fresh()->trashed())->toBeTrue()
        ->and($quotation->fresh())->not->toBeNull();
});
