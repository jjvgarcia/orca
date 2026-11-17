<div>
    <div class="mb-6 flex items-center gap-2 text-xs text-muted">
        <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-1 hover:text-ink">
            <x-ui.icon name="chevron-left" class="size-3.5" /> Pedidos
        </a>
    </div>

    <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Pedido</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h1 class="serif-display text-3xl text-ink sm:text-4xl">{{ $order->number }}</h1>
                <x-ui.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-ui.badge>
            </div>
            <p class="mt-2 text-sm text-muted">{{ $order->customer->name }} · criado em {{ $order->created_at->translatedFormat('d/m/Y') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button variant="secondary" :href="route('quotations.show', $order->quotation)">
                Orçamento {{ $order->quotation->number }} <x-ui.icon name="arrow-up-right" class="size-3.5" />
            </x-ui.button>
            @can('fulfill', $order)
                <x-ui.button variant="ink" wire:click="fulfill" wire:confirm="Concluir este pedido?">
                    <x-ui.icon name="check" class="size-4" />
                    Concluir
                </x-ui.button>
            @endcan
            @can('cancel', $order)
                <x-ui.button variant="danger" wire:click="cancel" wire:confirm="Cancelar o pedido? Só é possível sem nenhum pagamento registrado.">
                    Cancelar pedido
                </x-ui.button>
            @endcan
        </div>
    </header>

    @error('order')
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rust/20 bg-rust-soft px-4 py-3 text-sm text-rust">
            <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0" />
            <p>{{ $message }}</p>
        </div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr] lg:items-start">
        <div class="space-y-6">
            @if ($payingId !== null)
                @php $current = $receivables->firstWhere('id', $payingId); @endphp
                <x-ui.card class="border-clay/30 bg-surface">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="eyebrow">Registrar pagamento</p>
                            <p class="mt-1 text-sm text-ink">
                                Parcela {{ $current?->sequence }} · saldo
                                <x-ui.money :cents="$current?->outstandingCents() ?? 0" class="font-medium text-clay-deep" />
                            </p>
                        </div>
                        <button type="button" wire:click="cancelPayment" class="rounded-md p-1.5 text-muted hover:bg-surface-soft hover:text-ink">
                            <x-ui.icon name="x" class="size-4" />
                        </button>
                    </div>

                    @error('payment')
                        <div class="mt-3 rounded-lg border border-rust/20 bg-rust-soft px-3 py-2 text-xs text-rust">{{ $message }}</div>
                    @enderror

                    <form method="POST" action="{{ route('payments.store') }}" class="mt-4 grid gap-4 sm:grid-cols-3">
                        @csrf
                        <input type="hidden" name="receivable_id" value="{{ $payingId }}">
                        <input type="hidden" name="amount_cents" value="{{ $amountCents }}">
                        <input type="hidden" name="idempotency_key" value="{{ $paymentKey }}">

                        <x-ui.field label="Valor" name="amount" hint="R$">
                            <x-ui.input wire:model.live.debounce.300ms="amount" inputmode="decimal" placeholder="0,00" />
                            @if ($amountCents === null)
                                <span class="mt-1 block text-xs text-rust">Informe um valor maior que zero.</span>
                            @endif
                        </x-ui.field>

                        <x-ui.field label="Forma" name="method">
                            <x-ui.select wire:model="method">
                                @foreach (\App\Enums\PaymentMethod::cases() as $paymentMethod)
                                    <option value="{{ $paymentMethod->value }}">{{ $paymentMethod->label() }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>

                        <x-ui.field label="Data" name="paid_at">
                            <x-ui.input type="date" wire:model="paidAt" />
                        </x-ui.field>

                        <div class="flex flex-wrap items-center gap-2 sm:col-span-3">
                            <x-ui.button type="submit" :disabled="$amountCents === null" wire:loading.attr="disabled" data-testid="submit-payment">
                                <x-ui.icon name="banknote" class="size-4" />
                                Registrar pagamento
                            </x-ui.button>
                            <x-ui.button variant="ghost" type="button" wire:click="cancelPayment">Cancelar</x-ui.button>
                            <p class="ml-auto text-[11px] text-muted">
                                chave <span class="font-mono">{{ \Illuminate\Support\Str::limit($paymentKey, 8, '…') }}</span> · repetir não duplica
                            </p>
                        </div>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.card :padded="false">
                <div class="flex items-center justify-between border-b border-line px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="text-sm font-semibold text-ink">Parcelas</h2>
                        <p class="mt-0.5 text-xs text-muted">Status calculado na leitura: paga, parcial, aberta ou vencida</p>
                    </div>
                    <p class="font-mono text-xs text-muted">{{ $receivables->count() }}×</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="ledger-table" data-testid="schedule-table">
                        <thead>
                            <tr>
                                <th class="px-5 pt-5 sm:px-6">#</th>
                                <th class="px-3 pt-5">Vencimento</th>
                                <th class="px-3 pt-5 text-right">Valor</th>
                                <th class="hidden px-3 pt-5 text-right sm:table-cell">Pago</th>
                                <th class="px-3 pt-5">Status</th>
                                <th class="px-5 pt-5 text-right sm:px-6"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($receivables as $receivable)
                                @php $receivableStatus = $receivable->status(); @endphp
                                <tr>
                                    <td class="px-5 font-mono text-xs text-muted sm:px-6">{{ $receivable->sequence }}</td>
                                    <td class="px-3">
                                        <p class="text-sm text-ink">{{ $receivable->due_date->translatedFormat('d M Y') }}</p>
                                        <p class="text-[11px] text-muted">
                                            @if ($receivableStatus === \App\Enums\ReceivableStatus::Overdue)
                                                {{ $receivable->due_date->diffForHumans() }}
                                            @else
                                                {{ $receivable->payments->count() }} {{ \Illuminate\Support\Str::plural('pagamento', $receivable->payments->count()) }}
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-3 text-right"><x-ui.money :cents="$receivable->amount_cents" class="text-sm" /></td>
                                    <td class="hidden px-3 text-right sm:table-cell">
                                        <x-ui.money :cents="$receivable->paidCents()" :class="$receivable->paidCents() > 0 ? 'text-xs text-pine' : 'text-xs text-muted'" />
                                    </td>
                                    <td class="px-3"><x-ui.badge :tone="$receivableStatus->tone()">{{ $receivableStatus->label() }}</x-ui.badge></td>
                                    <td class="px-5 text-right sm:px-6">
                                        @if ($receivable->outstandingCents() > 0 && $order->status !== \App\Enums\OrderStatus::Cancelled)
                                            <x-ui.button variant="ghost" size="sm" wire:click="startPayment({{ $receivable->id }})">Registrar</x-ui.button>
                                        @else
                                            <span class="text-[11px] text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card :padded="false">
                <div class="border-b border-line px-5 py-4 sm:px-6">
                    <h2 class="text-sm font-semibold text-ink">Pagamentos</h2>
                    <p class="mt-0.5 text-xs text-muted">A mesma chave de idempotência nunca gera duas linhas</p>
                </div>

                @if ($payments->isEmpty())
                    <div class="p-5 sm:p-6">
                        <x-ui.empty icon="banknote" title="Nenhum pagamento registrado" description="Registre um pagamento em qualquer parcela em aberto." />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="ledger-table" data-testid="payments-table">
                            <thead>
                                <tr>
                                    <th class="px-5 pt-5 sm:px-6">Data</th>
                                    <th class="px-3 pt-5">Parcela</th>
                                    <th class="hidden px-3 pt-5 sm:table-cell">Forma</th>
                                    <th class="px-3 pt-5 text-right">Valor</th>
                                    <th class="hidden px-5 pt-5 text-right sm:px-6 md:table-cell">Chave</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td class="px-5 sm:px-6"><span class="text-sm text-ink">{{ $payment->paid_at->format('d/m/Y') }}</span></td>
                                        <td class="px-3 font-mono text-xs text-muted">{{ $payment->receivable->sequence }}</td>
                                        <td class="hidden px-3 text-xs text-ink-soft sm:table-cell">{{ $payment->method->label() }}</td>
                                        <td class="px-3 text-right"><x-ui.money :cents="$payment->amount_cents" class="text-sm" /></td>
                                        <td class="hidden px-5 text-right sm:px-6 md:table-cell"><span class="font-mono text-[10px] text-muted">{{ \Illuminate\Support\Str::limit($payment->idempotency_key, 8, '…') }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-6 lg:sticky lg:top-8">
            <x-ui.card>
                <p class="eyebrow">Recebimento</p>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <div>
                        <x-ui.money :cents="$paidCents" class="serif-display text-3xl text-pine" />
                        <p class="mt-1 text-xs text-muted">pago de <x-ui.money :cents="$order->total_cents" class="text-xs" /></p>
                    </div>
                    <p class="font-mono text-sm text-ink-soft">{{ $order->total_cents > 0 ? intdiv($paidCents * 100, $order->total_cents) : 0 }}%</p>
                </div>
                <x-ui.progress :value="$paidCents" :max="$order->total_cents" class="mt-4" />
                <div class="mt-4 flex items-center justify-between border-t border-line pt-4 text-sm">
                    <span class="text-muted">Saldo</span>
                    <x-ui.money :cents="$order->total_cents - $paidCents" class="font-medium" />
                </div>
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-muted">Total do pedido</span>
                    <x-ui.money :cents="$order->total_cents" data-testid="order-total" class="font-medium" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <p class="eyebrow">Cliente</p>
                <p class="mt-2 text-sm font-medium text-ink">{{ $order->customer->name }}</p>
                <p class="mt-0.5 text-xs text-muted">{{ $order->customer->kindLabel() }} @if ($order->customer->document) · <span class="font-mono">{{ $order->customer->document }}</span> @endif</p>
                <p class="mt-1 text-xs text-muted">{{ $order->customer->email }}</p>
                <p class="text-xs text-muted">{{ $order->customer->phone }}</p>
                <div class="mt-4 border-t border-line pt-4 text-xs">
                    <p class="text-muted">Condições do orçamento</p>
                    <p class="mt-1 text-ink-soft">{{ $order->quotation->installments }}× · 1º vencimento {{ $order->quotation->first_due_days }} dias após aprovação</p>
                </div>
            </x-ui.card>

            <div class="rounded-2xl border border-line bg-surface-soft p-5 text-xs leading-relaxed text-muted">
                <p class="flex items-center gap-2 font-medium text-ink-soft"><x-ui.icon name="alert" class="size-4 text-amber" /> Idempotência</p>
                <p class="mt-2">Cada envio de pagamento carrega uma chave única. Se o cliente clicar duas vezes, o segundo envio devolve o mesmo registro em vez de criar outro. Payload diferente com a mesma chave é rejeitado com conflito.</p>
            </div>
        </div>
    </div>
</div>
