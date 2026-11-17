<?php

namespace App\Livewire\Orders;

use App\Domain\Exceptions\DomainRuleViolation;
use App\Domain\Money;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Receivable;
use App\Services\OrderService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pedido')]
class Show extends Component
{
    public Order $order;

    public ?int $payingId = null;

    public string $amount = '';

    public string $method = 'pix';

    public string $paidAt = '';

    public string $paymentKey = '';

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);

        $this->order = $order;
        $this->paidAt = today()->toDateString();
    }

    public function startPayment(int $receivableId): void
    {
        $receivable = $this->order->receivables()->findOrFail($receivableId);

        if ($receivable->outstandingCents() <= 0) {
            return;
        }

        $this->payingId = $receivable->id;
        $this->amount = Money::fromCents($receivable->outstandingCents())->toDecimalString();
        $this->method = 'pix';
        $this->paidAt = today()->toDateString();
        $this->paymentKey = (string) Str::uuid();
        $this->resetValidation();
    }

    public function cancelPayment(): void
    {
        $this->reset('payingId', 'amount', 'paymentKey');
        $this->resetValidation();
    }

    public function fulfill(): void
    {
        Gate::authorize('fulfill', $this->order);

        try {
            app(OrderService::class)->fulfill($this->order);
        } catch (DomainRuleViolation $violation) {
            $this->addError('order', $violation->getMessage());

            return;
        }

        session()->flash('status', 'Pedido concluído.');
        $this->redirect(route('orders.show', $this->order));
    }

    public function cancel(): void
    {
        Gate::authorize('cancel', $this->order);

        try {
            app(OrderService::class)->cancel($this->order);
        } catch (DomainRuleViolation $violation) {
            $this->addError('order', $violation->getMessage());

            return;
        }

        session()->flash('status', 'Pedido cancelado. Nenhuma parcela paga foi afetada.');
        $this->redirect(route('orders.show', $this->order));
    }

    public function amountCents(): ?int
    {
        try {
            $money = Money::fromDecimalString($this->amount);

            return $money->cents > 0 ? $money->cents : null;
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function render()
    {
        $this->order->load(['customer', 'quotation']);

        $receivables = $this->order->receivables()
            ->with('payments')
            ->withSum('payments as paid_cents', 'amount_cents')
            ->get();

        $payments = Payment::query()
            ->with('receivable')
            ->whereHas('receivable', fn ($query) => $query->where('order_id', $this->order->id))
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        return view('livewire.orders.show', [
            'receivables' => $receivables,
            'payments' => $payments,
            'paidCents' => (int) $receivables->sum(fn (Receivable $receivable) => $receivable->paidCents()),
            'amountCents' => $this->amountCents(),
        ])->title('Pedido '.$this->order->number);
    }
}
