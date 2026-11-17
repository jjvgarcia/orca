<?php

namespace App\Services;

use App\Domain\Exceptions\DomainRuleViolation;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Receivable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class PaymentService
{
    /**
     * Registro idempotente: a mesma chave com o mesmo payload devolve o
     * pagamento original; a mesma chave com payload diferente é conflito.
     *
     * @return array{payment: Payment, replayed: bool}
     */
    public function register(
        Receivable $receivable,
        int $amountCents,
        PaymentMethod $method,
        CarbonInterface $paidAt,
        string $idempotencyKey,
    ): array {
        $idempotencyKey = trim($idempotencyKey);

        if ($idempotencyKey === '') {
            throw DomainRuleViolation::unprocessable('A chave de idempotência é obrigatória.');
        }

        if ($amountCents <= 0) {
            throw DomainRuleViolation::unprocessable('O valor do pagamento precisa ser maior que zero.');
        }

        $fingerprint = $this->fingerprint($amountCents, $method, $paidAt);

        return DB::transaction(function () use ($receivable, $amountCents, $method, $paidAt, $idempotencyKey, $fingerprint): array {
            $locked = Receivable::whereKey($receivable->getKey())->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($locked->order_id)->firstOrFail();

            if ($order->status === OrderStatus::Cancelled) {
                throw DomainRuleViolation::unprocessable('Não é possível registrar pagamento em pedido cancelado.');
            }

            $existing = Payment::where('receivable_id', $locked->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                if (! hash_equals($existing->fingerprint, $fingerprint)) {
                    throw DomainRuleViolation::conflict('A mesma chave de idempotência foi usada com outro pagamento.');
                }

                return ['payment' => $existing, 'replayed' => true];
            }

            $paid = (int) $locked->payments()->sum('amount_cents');

            if ($paid + $amountCents > $locked->amount_cents) {
                throw DomainRuleViolation::unprocessable('O pagamento excede o saldo da parcela.');
            }

            $payment = Payment::create([
                'receivable_id' => $locked->id,
                'amount_cents' => $amountCents,
                'method' => $method,
                'paid_at' => $paidAt->toDateString(),
                'idempotency_key' => $idempotencyKey,
                'fingerprint' => $fingerprint,
            ]);

            return ['payment' => $payment, 'replayed' => false];
        });
    }

    private function fingerprint(int $amountCents, PaymentMethod $method, CarbonInterface $paidAt): string
    {
        return hash('sha256', implode('|', [$amountCents, $method->value, $paidAt->toDateString()]));
    }
}
