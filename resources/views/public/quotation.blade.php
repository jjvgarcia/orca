<x-layouts.public :title="'Orçamento '.$quotation->code()">
    @if (session('status'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-pine/20 bg-pine-soft px-4 py-3 text-sm text-pine">
            <x-ui.icon name="check" class="mt-0.5 size-4 shrink-0" />
            <p>{{ session('status') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-rust/20 bg-rust-soft px-4 py-3 text-sm text-rust">
            <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0" />
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-muted">
            <span class="font-mono">{{ $quotation->number }}</span>
            <span>·</span>
            <span>versão {{ $quotation->version }}</span>
        </div>
        <x-ui.badge :tone="$status->tone()">{{ $status->label() }}</x-ui.badge>
    </div>

    <article class="grain relative rounded-3xl border border-line bg-surface px-6 py-8 sm:px-10 sm:py-10">
        @if ($status === \App\Enums\QuotationStatus::Approved)
            <div class="absolute right-6 top-6 hidden sm:block"><span class="seal seal-approved">aprovado</span></div>
        @elseif ($status === \App\Enums\QuotationStatus::Rejected)
            <div class="absolute right-6 top-6 hidden sm:block"><span class="seal seal-rejected">recusado</span></div>
        @endif

        <header class="border-b border-line pb-6">
            <p class="eyebrow">Proposta comercial</p>
            <h1 class="serif-display mt-2 text-3xl leading-tight text-ink sm:text-4xl">Orçamento {{ $quotation->code() }}</h1>
            <p class="mt-2 text-sm text-muted">Preparado para <span class="text-ink">{{ $quotation->customer->name }}</span></p>
        </header>

        <div class="mt-6 grid gap-4 border-b border-line pb-6 text-sm sm:grid-cols-3">
            <div>
                <p class="eyebrow">Emitido em</p>
                <p class="mt-1 text-ink">{{ $quotation->created_at->translatedFormat('d/m/Y') }}</p>
            </div>
            <div>
                <p class="eyebrow">Válido até</p>
                <p class="mt-1 text-ink">{{ $quotation->valid_until->translatedFormat('d/m/Y') }}</p>
            </div>
            <div>
                <p class="eyebrow">Condições</p>
                <p class="mt-1 text-ink">{{ $quotation->installments }}× · 1º vencimento em {{ $quotation->first_due_days }} dias</p>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th class="w-full pr-3">Item</th>
                        <th class="px-2 text-right">Qtd.</th>
                        <th class="hidden px-2 text-right sm:table-cell">Unitário</th>
                        <th class="pl-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotation->items as $item)
                        <tr>
                            <td class="pr-3">
                                <p class="text-sm text-ink">{{ $item->description }}</p>
                            </td>
                            <td class="px-2 text-right font-mono text-xs tabular-nums text-ink-soft">{{ $item->quantity }}</td>
                            <td class="hidden px-2 text-right sm:table-cell"><x-ui.money :cents="$item->unit_price_cents" class="text-xs text-ink-soft" /></td>
                            <td class="pl-3 text-right">
                                <x-ui.money :cents="$item->total_cents" class="text-sm font-medium" />
                                @if ($item->discount_cents > 0)
                                    <p class="text-[11px] text-clay-deep">− <x-ui.money :cents="$item->discount_cents" class="text-[11px]" /> de desconto</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <dl class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd><x-ui.money :cents="$quotation->subtotal_cents" /></dd></div>
                <div class="flex justify-between"><dt class="text-muted">Descontos</dt><dd><x-ui.money :cents="$quotation->discount_cents" /></dd></div>
                <div class="flex items-end justify-between border-t border-line pt-3">
                    <dt class="text-xs uppercase tracking-[0.18em] text-muted">Total</dt>
                    <dd><x-ui.money :cents="$quotation->total_cents" class="serif-display text-3xl text-ink" /></dd>
                </div>
            </dl>
        </div>
    </article>

    <div class="mt-6 grid gap-6 md:grid-cols-[1.15fr_1fr] md:items-start">
        <x-ui.card>
            <p class="eyebrow">{{ $scheduleIsProjection ? 'Parcelamento previsto' : 'Cronograma do pedido' }}</p>
            <p class="mt-1 text-xs text-muted">
                {{ $scheduleIsProjection ? 'Gerado na aprovação · o resto dos centavos fica na primeira parcela' : 'Datas e valores já registrados no pedido' }}
            </p>
            <ul class="mt-4 divide-y divide-line-soft">
                @foreach ($schedule as $part)
                    <li class="flex items-center justify-between py-2 text-sm">
                        <span class="text-muted">{{ $part['sequence'] }}ª parcela · {{ $part['due_date']->translatedFormat('d M Y') }}</span>
                        <x-ui.money :cents="$part['amount_cents']" />
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <div class="space-y-4">
            @if ($canDecide)
                <x-ui.card class="border-clay/30">
                    <p class="eyebrow">Sua decisão</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">Aprovar gera o pedido com as parcelas acima. A decisão é definitiva — este link não reabre.</p>
                    <form method="POST" action="{{ $decisionUrl }}" class="mt-4 space-y-2">
                        @csrf
                        <button type="submit" name="decision" value="approve" data-testid="approve-button" onclick="return confirm('Aprovar o orçamento e gerar o pedido? A decisão não pode ser desfeita.')" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-clay text-sm font-medium text-surface transition-colors hover:bg-clay-deep">
                            <x-ui.icon name="check" class="size-4" />
                            Aprovar orçamento
                        </button>
                        <button type="submit" name="decision" value="reject" onclick="return confirm('Recusar o orçamento? A decisão não pode ser desfeita.')" class="inline-flex h-10 w-full items-center justify-center rounded-lg border border-line text-sm font-medium text-ink-soft transition-colors hover:border-rust/40 hover:text-rust">
                            Recusar
                        </button>
                    </form>
                </x-ui.card>
            @elseif ($status === \App\Enums\QuotationStatus::Expired)
                <x-ui.card class="border-amber/25 bg-amber-soft/40">
                    <p class="eyebrow text-amber">Expirado</p>
                    <p class="mt-2 text-sm leading-relaxed text-ink-soft">Este orçamento venceu em {{ $quotation->valid_until->translatedFormat('d/m/Y') }}. Fale com quem enviou para receber uma nova versão.</p>
                </x-ui.card>
            @endif

            @if ($quotation->decided_at)
                <x-ui.card>
                    <p class="eyebrow">Decisão registrada</p>
                    <div class="mt-3 flex items-center gap-3">
                        <span @class(['seal text-xs', 'seal-approved' => $status === \App\Enums\QuotationStatus::Approved, 'seal-rejected' => $status === \App\Enums\QuotationStatus::Rejected])>
                            {{ $status->label() }}
                        </span>
                        <p class="text-xs text-muted">{{ $quotation->decided_at->translatedFormat('d/m/Y \à\s H:i') }}</p>
                    </div>
                    @if ($quotation->order)
                        <p class="mt-4 text-sm text-ink-soft">Pedido gerado: <span class="font-mono text-ink">{{ $quotation->order->number }}</span></p>
                    @endif
                </x-ui.card>
            @endif

            <x-ui.button variant="secondary" :href="$pdfUrl" target="_blank" class="w-full">
                <x-ui.icon name="download" class="size-4" />
                Baixar PDF do orçamento
            </x-ui.button>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-line bg-surface-soft p-5 text-xs leading-relaxed text-muted">
        <p class="flex items-center gap-2 font-medium text-ink-soft"><x-ui.icon name="link" class="size-4 text-clay" /> Link assinado</p>
        <p class="mt-2">Este endereço carrega uma assinatura com validade de 30 dias e não exige senha. Depois da decisão ele mostra apenas o resultado — não é possível aprovar ou recusar duas vezes.</p>
    </div>
</x-layouts.public>
