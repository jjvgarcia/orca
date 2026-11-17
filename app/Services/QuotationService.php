<?php

namespace App\Services;

use App\Domain\Exceptions\DomainRuleViolation;
use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\QuotationEvent;
use App\Models\Service;
use App\Support\Sequence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class QuotationService
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    /**
     * @param  array<int, array{service_id: int, quantity: int, discount_cents?: int|null, description?: string|null}>  $items
     */
    public function create(
        Customer $customer,
        array $items,
        string $validUntil,
        int $installments,
        int $firstDueDays,
        string $actor,
    ): Quotation {
        return DB::transaction(function () use ($customer, $items, $validUntil, $installments, $firstDueDays, $actor): Quotation {
            $rows = $this->prepareItems($items);
            $date = CarbonImmutable::parse($validUntil)->startOfDay();

            if ($date->isBefore(today())) {
                throw DomainRuleViolation::unprocessable('A validade precisa ser hoje ou uma data futura.');
            }

            $quotation = Quotation::create([
                'customer_id' => $customer->id,
                'number' => Sequence::nextQuotationNumber((int) now()->year),
                'version' => 1,
                'status' => QuotationStatus::Draft,
                'valid_until' => $date,
                'installments' => $this->clampInstallments($installments),
                'first_due_days' => $this->clampFirstDueDays($firstDueDays),
                ...$this->totals($rows),
            ]);

            $quotation->items()->createMany($rows);
            $this->recordEvent($quotation, null, QuotationStatus::Draft, $actor, ['source' => 'create']);

            return $quotation->load('items');
        });
    }

    /**
     * Editar gera uma nova versão; a anterior permanece como histórico imutável.
     *
     * @param  array<int, array{service_id: int, quantity: int, discount_cents?: int|null, description?: string|null}>  $items
     */
    public function revise(
        Quotation $source,
        array $items,
        string $validUntil,
        int $installments,
        int $firstDueDays,
        string $actor,
    ): Quotation {
        if (! $source->isEditable()) {
            throw DomainRuleViolation::conflict('Um orçamento já decidido não pode receber nova versão.');
        }

        return DB::transaction(function () use ($source, $items, $validUntil, $installments, $firstDueDays, $actor): Quotation {
            $rows = $this->prepareItems($items);
            $date = CarbonImmutable::parse($validUntil)->startOfDay();

            if ($date->isBefore(today())) {
                throw DomainRuleViolation::unprocessable('A validade precisa ser hoje ou uma data futura.');
            }

            $versions = Quotation::where('number', $source->number)->lockForUpdate()->pluck('version');
            $version = ((int) $versions->max()) + 1;

            $quotation = Quotation::create([
                'customer_id' => $source->customer_id,
                'number' => $source->number,
                'version' => $version,
                'status' => QuotationStatus::Draft,
                'valid_until' => $date,
                'installments' => $this->clampInstallments($installments),
                'first_due_days' => $this->clampFirstDueDays($firstDueDays),
                'parent_id' => $source->parent_id ?? $source->id,
                ...$this->totals($rows),
            ]);

            $quotation->items()->createMany($rows);
            $this->recordEvent($quotation, null, QuotationStatus::Draft, $actor, [
                'source' => 'revision',
                'previous_version' => $source->version,
            ]);

            return $quotation->load('items');
        });
    }

    public function send(Quotation $quotation, string $actor): Quotation
    {
        if ($quotation->effectiveStatus() !== QuotationStatus::Draft) {
            throw DomainRuleViolation::conflict('Somente rascunhos podem ser enviados.');
        }

        $quotation->update(['status' => QuotationStatus::Sent]);
        $this->recordEvent($quotation, QuotationStatus::Draft, QuotationStatus::Sent, $actor, ['source' => 'send']);

        return $quotation;
    }

    /**
     * Decisão do cliente é terminal. O lock na linha impede duas decisões
     * concorrentes e dois pedidos para o mesmo orçamento.
     */
    public function decide(Quotation $quotation, bool $approve, ?string $ip, ?string $userAgent): Quotation
    {
        return DB::transaction(function () use ($quotation, $approve, $ip, $userAgent): Quotation {
            $locked = Quotation::whereKey($quotation->getKey())->lockForUpdate()->firstOrFail();
            $status = $locked->effectiveStatus();

            if ($status->isTerminal()) {
                throw DomainRuleViolation::conflict('Este orçamento já foi decidido.');
            }

            if ($status === QuotationStatus::Expired) {
                throw DomainRuleViolation::conflict('Este orçamento expirou.');
            }

            if ($status !== QuotationStatus::Sent) {
                throw DomainRuleViolation::conflict('Este orçamento ainda não foi enviado.');
            }

            $newStatus = $approve ? QuotationStatus::Approved : QuotationStatus::Rejected;

            $locked->update([
                'status' => $newStatus,
                'decided_at' => now(),
                'decided_ip' => $ip,
                'decided_user_agent' => $userAgent === null ? null : mb_substr($userAgent, 0, 1000),
            ]);

            $this->recordEvent($locked, QuotationStatus::Sent, $newStatus, 'public-link', [
                'ip' => $ip,
            ]);

            if ($approve) {
                $this->orders->openFrom($locked);
            }

            return $locked->refresh();
        });
    }

    /**
     * @param  array<int, array{service_id: int, quantity: int, discount_cents?: int|null, description?: string|null}>  $inputs
     * @return array<int, array{service_id: int, description: string, quantity: int, unit_price_cents: int, discount_cents: int, total_cents: int}>
     */
    private function prepareItems(array $inputs): array
    {
        if ($inputs === []) {
            throw DomainRuleViolation::unprocessable('Inclua ao menos um item no orçamento.');
        }

        $serviceIds = collect($inputs)->pluck('service_id')->filter()->unique()->all();
        $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');
        $prepared = [];

        foreach ($inputs as $input) {
            $service = $services->get($input['service_id'] ?? null);

            if ($service === null) {
                throw DomainRuleViolation::unprocessable('Serviço inexistente no catálogo.');
            }

            $quantity = (int) ($input['quantity'] ?? 0);

            if ($quantity < 1) {
                throw DomainRuleViolation::unprocessable('A quantidade precisa ser maior que zero.');
            }

            $gross = $service->unit_price_cents * $quantity;
            $discount = (int) ($input['discount_cents'] ?? 0);

            if ($discount < 0 || $discount > $gross) {
                throw DomainRuleViolation::unprocessable('O desconto do item não pode ser negativo nem maior que o valor bruto.');
            }

            $description = trim((string) ($input['description'] ?? ''));

            $prepared[] = [
                'service_id' => $service->id,
                'description' => $description === '' ? $service->name : mb_substr($description, 0, 255),
                'quantity' => $quantity,
                'unit_price_cents' => $service->unit_price_cents,
                'discount_cents' => $discount,
                'total_cents' => $gross - $discount,
            ];
        }

        return $prepared;
    }

    /**
     * @param  array<int, array{quantity: int, unit_price_cents: int, discount_cents: int}>  $rows
     * @return array{subtotal_cents: int, discount_cents: int, total_cents: int}
     */
    private function totals(array $rows): array
    {
        $subtotal = 0;
        $discount = 0;

        foreach ($rows as $row) {
            $subtotal += $row['quantity'] * $row['unit_price_cents'];
            $discount += $row['discount_cents'];
        }

        return [
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents' => $subtotal - $discount,
        ];
    }

    private function clampInstallments(int $installments): int
    {
        if ($installments < 1 || $installments > 12) {
            throw DomainRuleViolation::unprocessable('O parcelamento deve ficar entre 1 e 12 vezes.');
        }

        return $installments;
    }

    private function clampFirstDueDays(int $days): int
    {
        if ($days < 0 || $days > 90) {
            throw DomainRuleViolation::unprocessable('O primeiro vencimento deve ficar entre hoje e 90 dias.');
        }

        return $days;
    }

    private function recordEvent(
        Quotation $quotation,
        ?QuotationStatus $from,
        QuotationStatus $to,
        string $actor,
        array $meta = [],
    ): void {
        QuotationEvent::create([
            'quotation_id' => $quotation->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor' => $actor,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
