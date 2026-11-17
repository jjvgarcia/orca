<div>
    <x-ui.page-header eyebrow="Comercial" title="Orçamentos" description="Cada edição gera uma nova versão. Aprovado e recusado são terminais; expirado é calculado pela data de validade.">
        <x-slot:actions>
            <x-ui.button :href="route('quotations.create')">
                <x-ui.icon name="plus" class="size-4" />
                Novo orçamento
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

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

        <div class="relative ml-auto w-full sm:w-64">
            <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Número ou cliente" class="pl-9" />
        </div>
    </div>

    @if ($quotations->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="file-text" title="Nenhum orçamento neste filtro" description="Crie um orçamento ou troque o filtro para ver outros status.">
                <x-slot:action>
                    <x-ui.button :href="route('quotations.create')" size="sm">Novo orçamento</x-ui.button>
                </x-slot:action>
            </x-ui.empty>
        </x-ui.card>
    @else
        <x-ui.card :padded="false">
            <div class="overflow-x-auto">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th class="px-5 pt-5 sm:px-6">Orçamento</th>
                            <th class="hidden px-3 pt-5 md:table-cell">Cliente</th>
                            <th class="px-3 pt-5 text-right">Total</th>
                            <th class="hidden px-3 pt-5 sm:table-cell">Validade</th>
                            <th class="px-3 pt-5">Status</th>
                            <th class="px-5 pt-5 sm:px-6"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quotations as $quotation)
                            @php $effective = $quotation->effectiveStatus(); @endphp
                            <tr class="group cursor-pointer hover:bg-surface-soft/60">
                                <td class="px-5 sm:px-6">
                                    <a href="{{ route('quotations.show', $quotation) }}" class="block">
                                        <p class="font-mono text-sm tabular-nums text-ink">{{ $quotation->number }}</p>
                                        <p class="mt-0.5 text-xs text-muted">versão {{ $quotation->version }} · {{ $quotation->items_count }} {{ \Illuminate\Support\Str::plural('item', $quotation->items_count) }}</p>
                                    </a>
                                </td>
                                <td class="hidden px-3 md:table-cell">
                                    <a href="{{ route('quotations.show', $quotation) }}" class="block text-sm text-ink-soft group-hover:text-clay">{{ $quotation->customer->name }}</a>
                                </td>
                                <td class="px-3 text-right">
                                    <x-ui.money :cents="$quotation->total_cents" class="text-sm" />
                                </td>
                                <td class="hidden px-3 sm:table-cell">
                                    <p class="text-xs {{ $effective === \App\Enums\QuotationStatus::Expired ? 'text-rust' : 'text-muted' }}">
                                        {{ $quotation->valid_until->translatedFormat('d M Y') }}
                                    </p>
                                </td>
                                <td class="px-3">
                                    <x-ui.badge :tone="$effective->tone()">{{ $effective->label() }}</x-ui.badge>
                                </td>
                                <td class="px-5 text-right sm:px-6">
                                    <a href="{{ route('quotations.show', $quotation) }}" class="inline-flex text-muted group-hover:text-clay">
                                        <x-ui.icon name="chevron-right" class="size-4" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div class="mt-5">{{ $quotations->links() }}</div>
    @endif
</div>
