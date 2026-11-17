<?php

namespace App\Livewire\Quotations;

use App\Domain\Exceptions\DomainRuleViolation;
use App\Domain\Money;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Service;
use App\Services\QuotationService;
use App\Support\Actor;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
class Builder extends Component
{
    public ?Quotation $source = null;

    public ?int $customerId = null;

    public string $validUntil = '';

    public int $installments = 1;

    public int $firstDueDays = 30;

    /** @var array<int, array{service_id: int|null, quantity: int|string, discount: string}> */
    public array $items = [];

    public function mount(?Quotation $quotation = null): void
    {
        $this->validUntil = today()->addDays(15)->toDateString();

        if ($quotation !== null && $quotation->exists) {
            Gate::authorize('revise', $quotation);
            $quotation->load('items');

            $this->source = $quotation;
            $this->customerId = $quotation->customer_id;
            $this->installments = $quotation->installments;
            $this->firstDueDays = $quotation->first_due_days;
            $this->items = $quotation->items->map(fn ($item) => [
                'service_id' => $item->service_id,
                'quantity' => $item->quantity,
                'discount' => Money::fromCents($item->discount_cents)->toDecimalString(),
            ])->values()->all();

            return;
        }

        Gate::authorize('create', Quotation::class);

        $this->items = [
            ['service_id' => null, 'quantity' => 1, 'discount' => '0,00'],
        ];
    }

    public function addItem(): void
    {
        $this->items[] = ['service_id' => null, 'quantity' => 1, 'discount' => '0,00'];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->addItem();
        }
    }

    public function saveDraft(): void
    {
        $this->persist(send: false);
    }

    public function saveAndSend(): void
    {
        $this->persist(send: true);
    }

    private function persist(bool $send): void
    {
        $this->validate([
            'customerId' => ['required', 'exists:customers,id'],
            'validUntil' => ['required', 'date', 'after_or_equal:today'],
            'installments' => ['required', 'integer', 'between:1,12'],
            'firstDueDays' => ['required', 'integer', 'between:0,90'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'exists:services,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.discount' => ['nullable', 'string', 'max:24'],
        ], [
            'customerId.required' => 'Escolha o cliente do orçamento.',
            'validUntil.after_or_equal' => 'A validade precisa ser hoje ou uma data futura.',
            'items.required' => 'Inclua ao menos um item.',
        ]);

        $rows = [];

        foreach ($this->items as $index => $item) {
            $rawDiscount = trim((string) ($item['discount'] ?? ''));

            try {
                $discount = Money::fromDecimalString($rawDiscount === '' ? '0' : $rawDiscount);
            } catch (InvalidArgumentException) {
                $this->addError("items.$index.discount", 'Desconto inválido.');

                return;
            }

            $service = Service::find($item['service_id'] ?? null);

            if ($service === null) {
                $this->addError("items.$index.service_id", 'Serviço inválido.');

                return;
            }

            $gross = $service->unit_price_cents * (int) $item['quantity'];

            if ($discount->isNegative() || $discount->cents > $gross) {
                $this->addError("items.$index.discount", 'O desconto deve ficar entre zero e o valor bruto do item.');

                return;
            }

            $rows[] = [
                'service_id' => $service->id,
                'quantity' => (int) $item['quantity'],
                'discount_cents' => $discount->cents,
            ];
        }

        $service = app(QuotationService::class);
        $actor = Actor::from(auth()->user());

        try {
            $quotation = $this->source !== null
                ? $service->revise($this->source, $rows, $this->validUntil, $this->installments, $this->firstDueDays, $actor)
                : $service->create(
                    Customer::findOrFail((int) $this->customerId),
                    $rows,
                    $this->validUntil,
                    $this->installments,
                    $this->firstDueDays,
                    $actor,
                );

            if ($send) {
                $service->send($quotation, $actor);
            }
        } catch (DomainRuleViolation $violation) {
            $this->addError('form', $violation->getMessage());

            return;
        }

        session()->flash('status', $send
            ? 'Orçamento enviado. O link público de 30 dias está na página do orçamento.'
            : 'Rascunho salvo. Você pode enviar quando quiser.');

        $this->redirect(route('quotations.show', $quotation));
    }

    public function render()
    {
        $sourceServiceIds = collect($this->items)->pluck('service_id')->filter()->all();

        $services = Service::query()
            ->where(fn ($query) => $query->where('active', true)->orWhereIn('id', $sourceServiceIds))
            ->orderBy('name')
            ->get();

        $servicesById = $services->keyBy('id');

        $subtotal = 0;
        $discountTotal = 0;
        $lines = [];

        foreach ($this->items as $index => $item) {
            $service = $servicesById->get($item['service_id'] ?? 0);
            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            $gross = $service === null ? 0 : $service->unit_price_cents * $quantity;

            try {
                $discount = Money::fromDecimalString((string) ($item['discount'] ?? '0'))->cents;
            } catch (InvalidArgumentException) {
                $discount = 0;
            }

            $discount = min(max(0, $discount), $gross);

            $lines[] = [
                'index' => $index,
                'service' => $service,
                'quantity' => $quantity,
                'gross_cents' => $gross,
                'discount_cents' => $discount,
                'total_cents' => $gross - $discount,
            ];

            $subtotal += $gross;
            $discountTotal += $discount;
        }

        $total = $subtotal - $discountTotal;
        $schedule = $total > 0 ? Money::fromCents($total)->split($this->installments) : [];

        $title = $this->source !== null
            ? 'Nova versão de '.$this->source->number
            : 'Novo orçamento';

        return view('livewire.quotations.builder', [
            'customers' => Customer::orderBy('name')->get(),
            'services' => $services,
            'lines' => $lines,
            'subtotal' => $subtotal,
            'discountTotal' => $discountTotal,
            'total' => $total,
            'schedule' => $schedule,
            'firstDue' => today()->addDays($this->firstDueDays),
            'title' => $title,
        ])->title($title);
    }
}
