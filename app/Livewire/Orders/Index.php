<?php

namespace App\Livewire\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Pedidos')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = '';

    public function updating(string $property): void
    {
        if ($property === 'status') {
            $this->resetPage();
        }
    }

    public function render()
    {
        Gate::authorize('viewAny', Order::class);

        $orders = Order::query()
            ->with(['customer', 'receivables' => fn ($query) => $query->withSum('payments as paid_cents', 'amount_cents')])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('livewire.orders.index', [
            'orders' => $orders,
            'counts' => [
                '' => Order::count(),
                'open' => Order::where('status', OrderStatus::Open)->count(),
                'fulfilled' => Order::where('status', OrderStatus::Fulfilled)->count(),
                'cancelled' => Order::where('status', OrderStatus::Cancelled)->count(),
            ],
            'filters' => [
                '' => 'Todos',
                'open' => 'Abertos',
                'fulfilled' => 'Concluídos',
                'cancelled' => 'Cancelados',
            ],
        ]);
    }
}
