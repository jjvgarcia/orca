<?php

namespace App\Services;

use App\Domain\Exceptions\DomainRuleViolation;
use App\Domain\Money;
use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\Receivable;
use App\Support\Sequence;
use Illuminate\Support\Facades\DB;
use LogicException;

final class OrderService
{
    /**
     * Um orçamento aprovado gera no máximo um pedido (garantido pelo índice
     * único em orders.quotation_id). As parcelas são persistidas na mesma
     * transação do pedido: ou existe o pedido inteiro, ou nada.
     */
    public function openFrom(Quotation $quotation): Order
    {
        if ($quotation->effectiveStatus() !== QuotationStatus::Approved) {
            throw DomainRuleViolation::conflict('O pedido só pode nascer de um orçamento aprovado.');
        }

        if ($quotation->order()->exists()) {
            throw DomainRuleViolation::conflict('Este orçamento já gerou um pedido.');
        }

        return DB::transaction(function () use ($quotation): Order {
            $order = Order::create([
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'number' => Sequence::nextOrderNumber((int) now()->year),
                'status' => OrderStatus::Open,
                'total_cents' => $quotation->total_cents,
            ]);

            $total = Money::fromCents($quotation->total_cents);
            $installments = max(1, min(12, $quotation->installments));
            $parts = $total->split($installments);

            $sum = 0;
            foreach ($parts as $index => $part) {
                Receivable::create([
                    'order_id' => $order->id,
                    'sequence' => $index + 1,
                    'due_date' => today()->copy()->addDays($quotation->first_due_days)->addMonthsNoOverflow($index),
                    'amount_cents' => $part->cents,
                ]);
                $sum += $part->cents;
            }

            if ($sum !== $total->cents) {
                throw new LogicException('A soma das parcelas divergiu do total do pedido.');
            }

            return $order->load('receivables');
        });
    }

    public function fulfill(Order $order): void
    {
        if ($order->status !== OrderStatus::Open) {
            throw DomainRuleViolation::conflict('Somente pedidos abertos podem ser concluídos.');
        }

        $order->update(['status' => OrderStatus::Fulfilled]);
    }

    public function cancel(Order $order): void
    {
        if ($order->status !== OrderStatus::Open) {
            throw DomainRuleViolation::conflict('Somente pedidos abertos podem ser cancelados.');
        }

        if ($order->hasPayments()) {
            throw DomainRuleViolation::conflict('Pedido com recebível pago não pode ser cancelado.');
        }

        $order->update(['status' => OrderStatus::Cancelled]);
    }
}
