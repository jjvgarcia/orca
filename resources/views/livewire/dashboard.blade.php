<div>
    <x-ui.page-header eyebrow="Painel" :title="$greeting.', '.auth()->user()->firstName()" :description="today()->translatedFormat('l, d \d\e F \d\e Y')">
        <x-slot:actions>
            <x-ui.button :href="route('quotations.create')">
                <x-ui.icon name="plus" class="size-4" />
                Novo orçamento
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if ($overdueCount > 0)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-rust/20 bg-rust-soft px-4 py-3">
            <div class="flex items-center gap-3 text-sm text-rust">
                <x-ui.icon name="alert" class="size-4 shrink-0" />
                <p>
                    <strong class="font-semibold">{{ $overdueCount }}</strong>
                    {{ $overdueCount === 1 ? 'parcela vencida' : 'parcelas vencidas' }} ·
                    <x-ui.money :cents="$overdueCents" class="font-semibold" />
                    em atraso
                </p>
            </div>
            <a href="{{ route('orders.index') }}" class="text-xs font-medium text-rust underline underline-offset-4">Ver pedidos</a>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat label="A receber em 30 dias" tone="ink">
            <x-ui.money :cents="$upcomingCents" />
            <x-slot:sub>{{ $upcomingCount }} {{ $upcomingCount === 1 ? 'parcela' : 'parcelas' }} no horizonte</x-slot:sub>
        </x-ui.stat>

        <x-ui.stat label="Vencido" :tone="$overdueCents > 0 ? 'rust' : 'ink'">
            <x-ui.money :cents="$overdueCents" />
            <x-slot:sub>{{ $overdueCount === 0 ? 'Nada em atraso' : $overdueCount.' '.\Illuminate\Support\Str::plural('parcela', $overdueCount).' em atraso' }}</x-slot:sub>
        </x-ui.stat>

        <x-ui.stat label="Aprovação no mês" tone="pine">
            {{ $approvalRate === null ? '—' : $approvalRate.'%' }}
            <x-slot:sub>
                @if ($decidedThisMonth === 0)
                    Nenhuma decisão registrada
                @else
                    {{ $approvedThisMonth }} de {{ $decidedThisMonth }} decididos
                @endif
            </x-slot:sub>
        </x-ui.stat>

        <x-ui.stat label="Ticket médio" tone="clay">
            <x-ui.money :cents="$averageTicket" />
            <x-slot:sub>{{ $orderCount }} {{ \Illuminate\Support\Str::plural('pedido', $orderCount) }} não cancelados</x-slot:sub>
        </x-ui.stat>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1.35fr_1fr] lg:items-start">
        <x-ui.card :padded="false">
            <div class="flex items-center justify-between border-b border-line px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-sm font-semibold text-ink">Vencimentos próximos</h2>
                    <p class="mt-0.5 text-xs text-muted">Parcelas em aberto nos próximos 30 dias</p>
                </div>
                <x-ui.button variant="ghost" size="sm" :href="route('orders.index')">Pedidos <x-ui.icon name="arrow-up-right" class="size-3.5" /></x-ui.button>
            </div>

            @if ($upcoming->isEmpty())
                <div class="p-5 sm:p-6">
                    <x-ui.empty icon="clock" title="Nada vencendo nos próximos 30 dias" description="Quando um orçamento for aprovado, as parcelas aparecem aqui." />
                </div>
            @else
                <ul class="divide-y divide-line-soft">
                    @foreach ($upcoming as $receivable)
                        <li class="flex items-center gap-4 px-5 py-3.5 sm:px-6">
                            <div class="w-12 shrink-0">
                                <p class="text-[10px] uppercase tracking-wider text-muted">{{ $receivable->due_date->translatedFormat('M') }}</p>
                                <p class="font-mono text-lg leading-none tabular-nums text-ink">{{ $receivable->due_date->format('d') }}</p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('orders.show', $receivable->order_id) }}" class="block truncate text-sm font-medium text-ink hover:text-clay">
                                    {{ $receivable->order->customer->name }}
                                </a>
                                <p class="mt-0.5 truncate text-xs text-muted">
                                    Pedido {{ $receivable->order->number }} · parcela {{ $receivable->sequence }}
                                </p>
                            </div>
                            <x-ui.money :cents="$receivable->outstandingCents()" class="text-sm font-medium" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card :padded="false">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-ink">Orçamentos recentes</h2>
                    <p class="mt-0.5 text-xs text-muted">Últimas movimentações</p>
                </div>
                <x-ui.button variant="ghost" size="sm" :href="route('quotations.index')">Todos</x-ui.button>
            </div>

            @if ($recentQuotations->isEmpty())
                <div class="p-5">
                    <x-ui.empty icon="file-text" title="Nenhum orçamento ainda" description="Comece criando um orçamento para um cliente." />
                </div>
            @else
                <ul class="divide-y divide-line-soft">
                    @foreach ($recentQuotations as $quotation)
                        <li class="px-5 py-3.5">
                            <a href="{{ route('quotations.show', $quotation) }}" class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink">{{ $quotation->customer->name }}</p>
                                    <p class="mt-0.5 text-xs text-muted">{{ $quotation->code() }} · {{ $quotation->updated_at->diffForHumans() }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <x-ui.money :cents="$quotation->total_cents" class="text-sm" />
                                    <div class="mt-1 flex justify-end">
                                        <x-ui.badge :tone="$quotation->effectiveStatus()->tone()">{{ $quotation->effectiveStatus()->label() }}</x-ui.badge>
                                    </div>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card class="mt-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-ink">Fluxo previsto</h2>
                <p class="mt-0.5 text-xs text-muted">Saldo em aberto por mês de vencimento</p>
            </div>
            <p class="text-xs text-muted">{{ $openOrders }} {{ $openOrders === 1 ? 'pedido aberto' : 'pedidos abertos' }}</p>
        </div>

        <div class="mt-6 grid grid-cols-6 items-end gap-2 sm:gap-4">
            @foreach ($months as $month)
                <div class="flex flex-col items-center gap-2">
                    <div class="flex h-32 w-full items-end">
                        <div
                            class="w-full rounded-t-md bg-clay/80 transition-all hover:bg-clay"
                            style="height: {{ max(3, (int) round(($month['cents'] / $chartMax) * 100)) }}%"
                            title="{{ $month['full'] }}: {{ \App\Domain\Money::fromCents($month['cents'])->format() }}"
                        ></div>
                    </div>
                    <span class="text-[10px] uppercase tracking-wider text-muted">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
    </x-ui.card>
</div>
