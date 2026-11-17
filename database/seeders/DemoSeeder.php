<?php

namespace Database\Seeders;

use App\Domain\Money;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\QuotationStatus;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\QuotationEvent;
use App\Models\Receivable;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Ana Ribeiro',
            'email' => 'demo@orca.test',
            'password' => 'password',
            'role' => Role::Admin,
        ]);

        User::create([
            'name' => 'Caio Menezes',
            'email' => 'operador@orca.test',
            'password' => 'password',
            'role' => Role::Operator,
        ]);

        $vertice = Customer::create([
            'name' => 'Studio Vértice',
            'kind' => 'pj',
            'document' => '12.345.678/0001-90',
            'email' => 'contato@vertice.studio',
            'phone' => '(11) 98877-1020',
        ]);

        $bianca = Customer::create([
            'name' => 'Bianca Ferraz',
            'kind' => 'pf',
            'document' => '321.654.987-00',
            'email' => 'bianca@ferraz.me',
            'phone' => '(21) 99654-3311',
        ]);

        $atelier = Customer::create([
            'name' => 'Ateliê Norte',
            'kind' => 'pj',
            'document' => '45.678.912/0001-33',
            'email' => 'ola@atelienorte.com.br',
            'phone' => '(31) 99120-8844',
        ]);

        $services = collect([
            'identity' => Service::create(['name' => 'Identidade visual', 'unit' => 'projeto', 'unit_price_cents' => 640_000]),
            'site' => Service::create(['name' => 'Site institucional', 'unit' => 'projeto', 'unit_price_cents' => 890_000]),
            'landing' => Service::create(['name' => 'Landing page', 'unit' => 'página', 'unit_price_cents' => 240_000]),
            'consulting' => Service::create(['name' => 'Consultoria de marca', 'unit' => 'hora', 'unit_price_cents' => 38_000]),
            'social' => Service::create(['name' => 'Gestão de redes sociais', 'unit' => 'mês', 'unit_price_cents' => 210_000]),
            'photo' => Service::create(['name' => 'Fotografia de produto', 'unit' => 'unidade', 'unit_price_cents' => 18_000]),
            'manual' => Service::create(['name' => 'Manual de marca', 'unit' => 'projeto', 'unit_price_cents' => 320_000]),
            'video' => Service::create(['name' => 'Vídeo institucional', 'unit' => 'projeto', 'unit_price_cents' => 550_000, 'active' => false]),
        ]);

        $year = now()->year;
        $operator = 'user:'.$admin->email;
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Safari/537.36';

        $makeQuotation = function (array $definition) use ($services, $year, $operator, $userAgent): Quotation {
            $rows = [];
            $subtotal = 0;
            $discountTotal = 0;

            foreach ($definition['items'] as [$serviceKey, $quantity, $discountCents, $description]) {
                $service = $services[$serviceKey];
                $gross = $service->unit_price_cents * $quantity;
                $subtotal += $gross;
                $discountTotal += $discountCents;

                $rows[] = [
                    'service_id' => $service->id,
                    'description' => $description ?? $service->name,
                    'quantity' => $quantity,
                    'unit_price_cents' => $service->unit_price_cents,
                    'discount_cents' => $discountCents,
                    'total_cents' => $gross - $discountCents,
                ];
            }

            $quotation = Quotation::create([
                'customer_id' => $definition['customer']->id,
                'number' => 'Q-'.$year.'-'.str_pad((string) $definition['sequence'], 4, '0', STR_PAD_LEFT),
                'version' => $definition['version'],
                'status' => $definition['status'],
                'valid_until' => $definition['valid_until'],
                'installments' => $definition['installments'],
                'first_due_days' => $definition['first_due_days'],
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discountTotal,
                'total_cents' => $subtotal - $discountTotal,
                'parent_id' => $definition['parent_id'] ?? null,
                'decided_at' => $definition['decided_at'] ?? null,
                'decided_ip' => $definition['decided_ip'] ?? null,
                'decided_user_agent' => isset($definition['decided_at']) ? $userAgent : null,
            ]);

            $quotation->items()->createMany($rows);

            DB::table('quotations')->where('id', $quotation->id)->update([
                'created_at' => $definition['created_at'],
                'updated_at' => $definition['updated_at'] ?? $definition['created_at'],
            ]);

            QuotationEvent::create([
                'quotation_id' => $quotation->id,
                'from_status' => null,
                'to_status' => QuotationStatus::Draft,
                'actor' => $operator,
                'meta' => ['source' => $definition['version'] > 1 ? 'revision' : 'create'],
                'created_at' => $definition['created_at'],
            ]);

            if ($definition['status'] !== QuotationStatus::Draft) {
                QuotationEvent::create([
                    'quotation_id' => $quotation->id,
                    'from_status' => QuotationStatus::Draft,
                    'to_status' => QuotationStatus::Sent,
                    'actor' => $operator,
                    'meta' => ['source' => 'send'],
                    'created_at' => $definition['sent_at'],
                ]);
            }

            if (isset($definition['decided_at'])) {
                QuotationEvent::create([
                    'quotation_id' => $quotation->id,
                    'from_status' => QuotationStatus::Sent,
                    'to_status' => $definition['status'],
                    'actor' => 'public-link',
                    'meta' => ['ip' => $definition['decided_ip']],
                    'created_at' => $definition['decided_at'],
                ]);
            }

            return $quotation;
        };

        $openOrder = function (Quotation $quotation, int $sequence, $decidedAt, array $payments = [], OrderStatus $status = OrderStatus::Open): void {
            $order = Order::create([
                'quotation_id' => $quotation->id,
                'customer_id' => $quotation->customer_id,
                'number' => $decidedAt->year.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
                'status' => $status,
                'total_cents' => $quotation->total_cents,
            ]);

            DB::table('orders')->where('id', $order->id)->update([
                'created_at' => $decidedAt,
                'updated_at' => $decidedAt,
            ]);

            $parts = Money::fromCents($quotation->total_cents)->split($quotation->installments);
            $firstDue = $decidedAt->copy()->addDays($quotation->first_due_days);

            if (array_sum(array_map(fn (Money $part) => $part->cents, $parts)) !== $quotation->total_cents) {
                throw new LogicException('A soma das parcelas do seed divergiu do total.');
            }

            foreach ($parts as $index => $part) {
                $receivable = Receivable::create([
                    'order_id' => $order->id,
                    'sequence' => $index + 1,
                    'due_date' => $firstDue->copy()->addMonthsNoOverflow($index),
                    'amount_cents' => $part->cents,
                ]);

                foreach ($payments as $payment) {
                    if ($payment['sequence'] !== $receivable->sequence) {
                        continue;
                    }

                    Payment::create([
                        'receivable_id' => $receivable->id,
                        'amount_cents' => $payment['amount_cents'],
                        'method' => $payment['method'],
                        'paid_at' => $payment['paid_at'],
                        'idempotency_key' => (string) Str::uuid(),
                        'fingerprint' => hash('sha256', implode('|', [
                            $payment['amount_cents'],
                            $payment['method']->value,
                            $payment['paid_at']->toDateString(),
                        ])),
                    ]);
                }
            }
        };

        // Q-0001 v1: enviado e vencido; v2: revisão em rascunho.
        $expired = $makeQuotation([
            'sequence' => 1,
            'version' => 1,
            'customer' => $vertice,
            'status' => QuotationStatus::Sent,
            'valid_until' => today()->subDays(6),
            'created_at' => now()->subDays(35)->setTime(9, 40),
            'sent_at' => now()->subDays(34)->setTime(11, 5),
            'updated_at' => now()->subDays(6)->setTime(18, 12),
            'installments' => 3,
            'first_due_days' => 30,
            'items' => [
                ['identity', 1, 0, null],
                ['manual', 1, 50_000, null],
            ],
        ]);

        $makeQuotation([
            'sequence' => 1,
            'version' => 2,
            'customer' => $vertice,
            'status' => QuotationStatus::Draft,
            'valid_until' => today()->addDays(12),
            'created_at' => now()->subDays(4)->setTime(14, 20),
            'updated_at' => now()->subDays(4)->setTime(14, 20),
            'installments' => 3,
            'first_due_days' => 30,
            'parent_id' => $expired->id,
            'items' => [
                ['site', 1, 0, null],
                ['landing', 1, 0, null],
                ['consulting', 10, 60_000, 'Oficina de posicionamento (10 h)'],
            ],
        ]);

        // Q-0002: aprovado, pedido 0001, pago parcial.
        $approvedOne = $makeQuotation([
            'sequence' => 2,
            'version' => 1,
            'customer' => $atelier,
            'status' => QuotationStatus::Approved,
            'valid_until' => today()->subDays(2),
            'created_at' => now()->subDays(28)->setTime(10, 12),
            'sent_at' => now()->subDays(26)->setTime(16, 48),
            'updated_at' => now()->subDays(12)->setTime(9, 26),
            'decided_at' => now()->subDays(12)->setTime(9, 26),
            'decided_ip' => '187.45.12.9',
            'installments' => 3,
            'first_due_days' => 30,
            'items' => [
                ['identity', 1, 0, null],
                ['manual', 1, 40_000, null],
            ],
        ]);

        $orderOneTotal = Money::fromCents($approvedOne->total_cents)->split(3);

        $openOrder($approvedOne, 1, now()->subDays(12)->setTime(9, 26), [
            ['sequence' => 1, 'amount_cents' => $orderOneTotal[0]->cents, 'method' => PaymentMethod::Pix, 'paid_at' => today()->subDays(8)],
            ['sequence' => 2, 'amount_cents' => 120_000, 'method' => PaymentMethod::Transfer, 'paid_at' => today()->subDays(3)],
        ]);

        // Q-0003: aprovado, pedido 0002 concluído e pago.
        $approvedTwo = $makeQuotation([
            'sequence' => 3,
            'version' => 1,
            'customer' => $bianca,
            'status' => QuotationStatus::Approved,
            'valid_until' => today()->addDays(4),
            'created_at' => now()->subDays(24)->setTime(15, 5),
            'sent_at' => now()->subDays(22)->setTime(9, 10),
            'updated_at' => now()->subDays(8)->setTime(20, 41),
            'decided_at' => now()->subDays(8)->setTime(20, 41),
            'decided_ip' => '201.17.88.54',
            'installments' => 2,
            'first_due_days' => 30,
            'items' => [
                ['landing', 1, 0, null],
                ['photo', 6, 18_000, 'Ensaio de produto (6 peças)'],
            ],
        ]);

        $orderTwoTotal = Money::fromCents($approvedTwo->total_cents)->split(2);

        $openOrder($approvedTwo, 2, now()->subDays(8)->setTime(20, 41), [
            ['sequence' => 1, 'amount_cents' => $orderTwoTotal[0]->cents, 'method' => PaymentMethod::Pix, 'paid_at' => today()->subDays(5)],
        ], OrderStatus::Fulfilled);

        // Q-0004: aprovado, pedido 0003 com a parcela vencida.
        $approvedThree = $makeQuotation([
            'sequence' => 4,
            'version' => 1,
            'customer' => $vertice,
            'status' => QuotationStatus::Approved,
            'valid_until' => today()->subDays(1),
            'created_at' => now()->subDays(30)->setTime(11, 30),
            'sent_at' => now()->subDays(29)->setTime(8, 15),
            'updated_at' => now()->subDays(21)->setTime(17, 3),
            'decided_at' => now()->subDays(21)->setTime(17, 3),
            'decided_ip' => '187.45.12.9',
            'installments' => 3,
            'first_due_days' => 10,
            'items' => [
                ['site', 1, 0, null],
                ['consulting', 6, 38_000, 'Mentoria de conteúdo (6 h)'],
            ],
        ]);

        $openOrder($approvedThree, 3, now()->subDays(21)->setTime(17, 3));

        // Q-0005: aprovado há 3 dias, pedido 0004 com parcela única.
        $approvedFour = $makeQuotation([
            'sequence' => 5,
            'version' => 1,
            'customer' => $atelier,
            'status' => QuotationStatus::Approved,
            'valid_until' => today()->addDays(12),
            'created_at' => now()->subDays(10)->setTime(9, 0),
            'sent_at' => now()->subDays(9)->setTime(10, 30),
            'updated_at' => now()->subDays(3)->setTime(12, 48),
            'decided_at' => now()->subDays(3)->setTime(12, 48),
            'decided_ip' => '177.92.44.101',
            'installments' => 1,
            'first_due_days' => 5,
            'items' => [
                ['social', 3, 0, 'Gestão de redes · trimestre'],
            ],
        ]);

        $openOrder($approvedFour, 4, now()->subDays(3)->setTime(12, 48));

        // Q-0006: recusado.
        $makeQuotation([
            'sequence' => 6,
            'version' => 1,
            'customer' => $bianca,
            'status' => QuotationStatus::Rejected,
            'valid_until' => today()->addDays(10),
            'created_at' => now()->subDays(14)->setTime(13, 20),
            'sent_at' => now()->subDays(12)->setTime(9, 5),
            'updated_at' => now()->subDays(5)->setTime(19, 22),
            'decided_at' => now()->subDays(5)->setTime(19, 22),
            'decided_ip' => '201.17.88.54',
            'installments' => 2,
            'first_due_days' => 30,
            'items' => [
                ['manual', 1, 0, null],
                ['consulting', 8, 24_000, 'Workshop de identidade (8 h)'],
            ],
        ]);

        // Q-0007: enviado, aguardando decisão (link público ativo).
        $makeQuotation([
            'sequence' => 7,
            'version' => 1,
            'customer' => $vertice,
            'status' => QuotationStatus::Sent,
            'valid_until' => today()->addDays(20),
            'created_at' => now()->subDays(6)->setTime(8, 55),
            'sent_at' => now()->subDays(5)->setTime(14, 10),
            'updated_at' => now()->subDays(5)->setTime(14, 10),
            'installments' => 2,
            'first_due_days' => 25,
            'items' => [
                ['landing', 1, 0, null],
                ['photo', 4, 0, 'Banco de imagens (4 peças)'],
            ],
        ]);
    }
}
