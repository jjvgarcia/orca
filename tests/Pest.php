<?php

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Services\QuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Demo');

function admin(): User
{
    return User::factory()->admin()->create();
}

function operator(): User
{
    return User::factory()->create();
}

function makeCustomer(array $attributes = []): Customer
{
    return Customer::factory()->create($attributes);
}

function makeService(int $unitPriceCents = 100_000, array $attributes = []): Service
{
    return Service::factory()->create([...$attributes, 'unit_price_cents' => $unitPriceCents]);
}

/**
 * @param  array<int, array{service_id: int, quantity: int, discount_cents?: int}>  $items
 */
function makeQuotation(Customer $customer, array $items, int $installments = 1, int $firstDueDays = 30): Quotation
{
    return app(QuotationService::class)->create(
        $customer,
        $items,
        today()->addDays(15)->toDateString(),
        $installments,
        $firstDueDays,
        'user:test@orca.test',
    );
}

/**
 * @param  array<int, array{service_id: int, quantity: int, discount_cents?: int}>  $items
 */
function makeSentQuotation(Customer $customer, array $items, int $installments = 1, int $firstDueDays = 30): Quotation
{
    $quotation = makeQuotation($customer, $items, $installments, $firstDueDays);

    return app(QuotationService::class)->send($quotation, 'user:test@orca.test');
}

function createApprovedOrder(int $unitPrice = 100_000, int $quantity = 1, int $installments = 1): Order
{
    $service = makeService($unitPrice);
    $quotation = makeSentQuotation(makeCustomer(), [
        ['service_id' => $service->id, 'quantity' => $quantity],
    ], $installments);

    app(QuotationService::class)->decide($quotation, true, '127.0.0.1', 'Pest UA');

    return Order::where('quotation_id', $quotation->id)->firstOrFail()->load('receivables');
}
