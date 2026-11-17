<?php

use App\Enums\ReceivableStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\Receivable;
use App\Models\Service;
use App\Models\User;

it('recria o dataset duas vezes sem erro', function () {
    $this->artisan('demo:reset')->assertSuccessful();
    $this->artisan('demo:reset')->assertSuccessful();

    expect(User::count())->toBe(2)
        ->and(Customer::count())->toBe(3)
        ->and(Service::count())->toBe(8)
        ->and(Quotation::count())->toBe(8)
        ->and(Quotation::distinct()->count('number'))->toBe(7)
        ->and(Order::count())->toBe(4)
        ->and(Receivable::count())->toBe(9);

    $overdue = Receivable::all()->filter(fn (Receivable $receivable) => $receivable->status() === ReceivableStatus::Overdue);

    expect($overdue)->toHaveCount(1)
        ->and($overdue->first()->outstandingCents())->toBeGreaterThan(0);

    foreach (Order::with('receivables')->get() as $order) {
        expect($order->receivables->sum('amount_cents'))->toBe($order->total_cents);
    }
});
