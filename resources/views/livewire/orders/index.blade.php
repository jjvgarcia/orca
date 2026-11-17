<div>
    <x-ui.page-header eyebrow="Financeiro" title="Pedidos" description="Todo pedido nasce de um orçamento aprovado. As parcelas são geradas na mesma transação — a soma sempre fecha com o total." />

    <div class="mb-5 flex flex-wrap items-center gap-2">
        @foreach ($filters as $value => $label)
            <button
                wire:click="$set('status', '{{ $value }}')"
                @class([
                    'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
                    'border-ink bg-ink text-paper' => $status === $value,
                    'border-line bg-surface text-ink-soft hover:border-muted/40' => $status !== $value,
                ])
            >
                {{ $label }}
                <span @class(['font-mono text-[10px]', 'text-paper/60' => $status === $value, 'text-muted' => $status !== $value])>{{ $counts[$value] }}</span>
            </button>
        @endforeach
    </div>

    @if ($orders->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="receipt" title="Nenhum pedido neste filtro" description="Pedidos aparecem quando um orçamento é aprovado pelo link público." />
        </x-ui.card>
    @else
        <x-ui.card :padded="false">
            <div class="overflow-x-auto">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th class="px-5 pt-5 sm:px-6">Pedido</th>
                            <th class="hidden px-3 pt-5 md:table-cell">Cliente</th>
                            <th class="hidden px-3 pt-5 lg:table-cell">Recebimento</th>
                            <th class="px-3 pt-5 text-right">Total</th>
                            <th class="px-3 pt-5">Status</th>
                            <th class="px-5 pt-5 sm:px-6"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $paid = $order->paidCents();
                                $total = $order->total_cents;
                            @endphp
                            <tr class="group hover:bg-surface-soft/60">
                                <td class="px-5 sm:px-6">
                                    <a href="{{ route('orders.show', $order) }}" class="block">
                                        <p class="font-mono text-sm tabular-nums text-ink">{{ $order->number }}</p>
                                        <p class="mt-0.5 text-xs text-muted">{{ $order->created_at->translatedFormat('d M Y') }}</p>
                                    </a>
                                </td>
                                <td class="hidden px-3 md:table-cell">
                                    <a href="{{ route('orders.show', $order) }}" class="text-sm text-ink-soft group-hover:text-clay">{{ $order->customer->name }}</a>
                                </td>
                                <td class="hidden px-3 lg:table-cell">
                                    <div class="w-36">
                                        <x-ui.progress :value="$paid" :max="$total" />
                                        <p class="mt-1 text-[11px] text-muted">
                                            <x-ui.money :cents="$paid" class="text-[11px]" /> de <x-ui.money :cents="$total" class="text-[11px]" />
                                        </p>
                                    </div>
                                </td>
                                <td class="px-3 text-right"><x-ui.money :cents="$total" class="text-sm" /></td>
                                <td class="px-3"><x-ui.badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-ui.badge></td>
                                <td class="px-5 text-right sm:px-6">
                                    <a href="{{ route('orders.show', $order) }}" class="inline-flex text-muted group-hover:text-clay">
                                        <x-ui.icon name="chevron-right" class="size-4" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div class="mt-5">{{ $orders->links() }}</div>
    @endif
</div>
