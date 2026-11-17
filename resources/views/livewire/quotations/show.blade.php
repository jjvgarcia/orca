<div>
    <div class="mb-6 flex items-center gap-2 text-xs text-muted">
        <a href="{{ route('quotations.index') }}" class="inline-flex items-center gap-1 hover:text-ink">
            <x-ui.icon name="chevron-left" class="size-3.5" /> Orçamentos
        </a>
    </div>

    <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Orçamento</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h1 class="serif-display text-3xl text-ink sm:text-4xl">{{ $quotation->number }}</h1>
                <span class="rounded-md border border-line bg-surface px-2 py-0.5 font-mono text-xs text-ink-soft">v{{ $quotation->version }}</span>
                <x-ui.badge :tone="$status->tone()">{{ $status->label() }}</x-ui.badge>
            </div>
            <p class="mt-2 text-sm text-muted">
                {{ $quotation->customer->name }} ·
                válido até {{ $quotation->valid_until->translatedFormat('d \d\e F \d\e Y') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-ui.button variant="secondary" :href="$pdfLink" target="_blank">
                <x-ui.icon name="download" class="size-4" />
                PDF
            </x-ui.button>

            @if ($quotation->isEditable())
                <x-ui.button variant="secondary" :href="route('quotations.revise', $quotation)">
                    Nova versão
                </x-ui.button>
            @endif

            @if ($status === \App\Enums\QuotationStatus::Draft)
                <x-ui.button wire:click="send" wire:loading.attr="disabled">
                    <x-ui.icon name="link" class="size-4" />
                    Enviar ao cliente
                </x-ui.button>
            @endif
        </div>
    </header>

    @error('send')
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rust/20 bg-rust-soft px-4 py-3 text-sm text-rust">
            <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0" />
            <p>{{ $message }}</p>
        </div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-[1.55fr_1fr] lg:items-start">
        <div class="space-y-6">
            <x-ui.card class="grain">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
                    <div>
                        <p class="eyebrow">Preparado para</p>
                        <p class="serif-display mt-1 text-2xl text-ink">{{ $quotation->customer->name }}</p>
                        <p class="mt-1 text-xs text-muted">
                            {{ $quotation->customer->kindLabel() }}
                            @if ($quotation->customer->document) · <span class="font-mono">{{ $quotation->customer->document }}</span> @endif
                        </p>
                        @if ($quotation->customer->email || $quotation->customer->phone)
                            <p class="mt-0.5 text-xs text-muted">{{ $quotation->customer->email }} {{ $quotation->customer->phone ? '· '.$quotation->customer->phone : '' }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="eyebrow">Total</p>
                        <x-ui.money :cents="$quotation->total_cents" class="serif-display mt-1 block text-3xl text-ink" />
                        <p class="mt-1 text-xs text-muted">{{ $quotation->installments }}× · 1º venc. em {{ $quotation->first_due_days }} dias</p>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="ledger-table">
                        <thead>
                            <tr>
                                <th class="w-full pr-3">Item</th>
                                <th class="px-3 text-right">Qtd.</th>
                                <th class="hidden px-3 text-right sm:table-cell">Unitário</th>
                                <th class="hidden px-3 text-right sm:table-cell">Desconto</th>
                                <th class="pl-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($quotation->items as $item)
                                <tr>
                                    <td class="pr-3">
                                        <p class="text-sm font-medium text-ink">{{ $item->description }}</p>
                                        @if ($item->service && $item->service->name !== $item->description)
                                            <p class="text-xs text-muted">{{ $item->service->name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 text-right font-mono text-xs tabular-nums text-ink-soft">{{ $item->quantity }}</td>
                                    <td class="hidden px-3 text-right sm:table-cell"><x-ui.money :cents="$item->unit_price_cents" class="text-xs text-ink-soft" /></td>
                                    <td class="hidden px-3 text-right sm:table-cell">
                                        <x-ui.money :cents="$item->discount_cents" :class="$item->discount_cents > 0 ? 'text-xs text-clay-deep' : 'text-xs text-muted'" />
                                    </td>
                                    <td class="pl-3 text-right"><x-ui.money :cents="$item->total_cents" class="text-sm font-medium" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex justify-end">
                    <dl class="w-full max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted">Subtotal</dt>
                            <dd><x-ui.money :cents="$quotation->subtotal_cents" /></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted">Descontos</dt>
                            <dd><x-ui.money :cents="$quotation->discount_cents" :class="$quotation->discount_cents > 0 ? 'text-clay-deep' : 'text-muted'" /></dd>
                        </div>
                        <div class="flex items-end justify-between border-t border-line pt-3">
                            <dt class="text-xs uppercase tracking-[0.18em] text-muted">Total</dt>
                            <dd><x-ui.money :cents="$quotation->total_cents" data-testid="quotation-total" class="serif-display text-2xl text-ink" /></dd>
                        </div>
                    </dl>
                </div>
            </x-ui.card>

            <x-ui.card :padded="false">
                <div class="border-b border-line px-5 py-4 sm:px-6">
                    <h2 class="text-sm font-semibold text-ink">Linha do tempo</h2>
                    <p class="mt-0.5 text-xs text-muted">Toda transição de status registrada com autor</p>
                </div>

                <ol class="px-5 py-4 sm:px-6">
                    @foreach ($quotation->events as $event)
                        <li class="relative flex gap-4 pb-4 last:pb-0">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-clay/70"></span>
                            <div class="min-w-0 flex-1 border-b border-line-soft pb-3 last:border-0 last:pb-0">
                                <p class="text-sm text-ink">
                                    @if ($event->from_status)
                                        {{ $event->from_status->label() }} → {{ $event->to_status->label() }}
                                    @else
                                        Criado como {{ $event->to_status->label() }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs text-muted">
                                    {{ $event->actor }} · {{ $event->created_at?->translatedFormat('d/m/Y H:i') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card>
                <div class="flex items-center justify-between">
                    <p class="eyebrow">Link do cliente</p>
                    @if ($status === \App\Enums\QuotationStatus::Approved)
                        <span class="seal seal-approved text-sm">aprovado</span>
                    @elseif ($status === \App\Enums\QuotationStatus::Rejected)
                        <span class="seal seal-rejected text-sm">recusado</span>
                    @endif
                </div>

                @if ($status === \App\Enums\QuotationStatus::Draft)
                    <p class="mt-3 text-xs leading-relaxed text-muted">Use <span class="text-ink">Enviar ao cliente</span> para gerar o link público assinado. Antes disso não existe URL para o cliente.</p>
                @else
                    @if ($status === \App\Enums\QuotationStatus::Expired)
                        <div class="mt-3 rounded-lg border border-amber/20 bg-amber-soft px-3 py-2 text-xs text-amber">
                            Este orçamento expirou em {{ $quotation->valid_until->translatedFormat('d/m/Y') }}. O link não aceita mais decisão.
                        </div>
                    @endif

                    <div class="mt-3 flex gap-2">
                        <input id="public-link" readonly value="{{ $publicLink }}" class="h-9 min-w-0 flex-1 rounded-lg border border-line bg-surface-soft px-2 font-mono text-[10px] text-muted">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('public-link').value); this.dataset.copied='1'; this.textContent='ok'" class="h-9 shrink-0 rounded-lg border border-line px-2 text-xs text-ink-soft hover:bg-surface-soft">copiar</button>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-[11px] text-muted">
                        <span>30 dias · sem login</span>
                        <a href="{{ $publicLink }}" target="_blank" class="inline-flex items-center gap-1 text-clay hover:underline">abrir <x-ui.icon name="arrow-up-right" class="size-3" /></a>
                    </div>
                @endif

                @if ($quotation->decided_at)
                    <dl class="mt-4 space-y-2 border-t border-line pt-4 text-xs">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">Decidido em</dt>
                            <dd class="text-ink-soft">{{ $quotation->decided_at->translatedFormat('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">IP</dt>
                            <dd class="font-mono text-ink-soft">{{ $quotation->decided_ip ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">Navegador</dt>
                            <dd class="max-w-[60%] truncate text-right text-ink-soft" title="{{ $quotation->decided_user_agent }}">{{ $quotation->decided_user_agent ?: '—' }}</dd>
                        </div>
                    </dl>
                @endif
            </x-ui.card>

            @if ($quotation->order)
                <x-ui.card>
                    <p class="eyebrow">Pedido gerado</p>
                    <a href="{{ route('orders.show', $quotation->order) }}" data-testid="order-link" class="mt-2 flex items-center justify-between gap-3">
                        <div>
                            <p class="font-mono text-lg text-ink">{{ $quotation->order->number }}</p>
                            <p class="text-xs text-muted">{{ $quotation->order->status->label() }} · {{ $quotation->order->receivables()->count() }} parcelas</p>
                        </div>
                        <x-ui.money :cents="$quotation->order->total_cents" class="text-sm" />
                    </a>
                    <x-ui.button variant="ghost" size="sm" class="mt-3" :href="route('orders.show', $quotation->order)">
                        Abrir pedido <x-ui.icon name="arrow-up-right" class="size-3.5" />
                    </x-ui.button>
                </x-ui.card>
            @endif

            <x-ui.card :padded="false">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-ink">Versões</h2>
                    <p class="mt-0.5 text-xs text-muted">Histórico imutável do mesmo número</p>
                </div>
                <ul class="divide-y divide-line-soft">
                    @foreach ($versions as $version)
                        @php $versionStatus = $version->effectiveStatus(); @endphp
                        <li>
                            <a href="{{ route('quotations.show', $version) }}" @class(['flex items-center justify-between gap-3 px-5 py-3 hover:bg-surface-soft/60', 'bg-surface-soft/40' => $version->id === $quotation->id])>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs text-ink-soft">v{{ $version->version }}</span>
                                    @if ($version->id === $quotation->id)
                                        <span class="text-[10px] uppercase tracking-wider text-clay">atual</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui.money :cents="$version->total_cents" class="text-xs text-ink-soft" />
                                    <x-ui.badge :tone="$versionStatus->tone()">{{ $versionStatus->label() }}</x-ui.badge>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>

            <div class="rounded-2xl border border-line bg-surface-soft p-5 text-xs leading-relaxed text-muted">
                <p class="flex items-center gap-2 font-medium text-ink-soft"><x-ui.icon name="alert" class="size-4 text-amber" /> Decisão terminal</p>
                <p class="mt-2">Aprovar cria o pedido e as parcelas na mesma transação. Recusar encerra o orçamento. Em ambos os casos o link não reabre e não há segunda decisão.</p>
            </div>
        </div>
    </div>
</div>
