<div>
    <div class="mb-6 flex items-center gap-2 text-xs text-muted">
        <a href="{{ route('quotations.index') }}" class="inline-flex items-center gap-1 hover:text-ink">
            <x-ui.icon name="chevron-left" class="size-3.5" /> Orçamentos
        </a>
        @if ($source)
            <span>/</span>
            <a href="{{ route('quotations.show', $source) }}" class="font-mono hover:text-ink">{{ $source->code() }}</a>
        @endif
    </div>

    <x-ui.page-header
        eyebrow="Comercial"
        :title="$title"
        :description="$source ? 'A versão anterior permanece intacta; esta nova versão começa como rascunho.' : 'Escolha o cliente, os serviços e as condições de pagamento. O preço é copiado do catálogo agora e não muda depois.'"
    />

    @error('form')
        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rust/20 bg-rust-soft px-4 py-3 text-sm text-rust">
            <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0" />
            <p>{{ $message }}</p>
        </div>
    @enderror

    <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr] lg:items-start">
        <div class="space-y-6">
            <x-ui.card>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-ui.field label="Cliente" name="customerId">
                            <x-ui.select wire:model="customerId" data-testid="quotation-customer" :invalid="$errors->has('customerId')">
                                <option value="">Selecione…</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </x-ui.select>
                        </x-ui.field>
                        <p class="mt-1.5 text-xs text-muted">Cliente não está aqui? <a href="{{ route('customers.create') }}" class="text-clay underline underline-offset-4">cadastre primeiro</a>.</p>
                    </div>

                    <x-ui.field label="Validade" name="validUntil">
                        <x-ui.input type="date" wire:model="validUntil" :invalid="$errors->has('validUntil')" />
                    </x-ui.field>

                    <x-ui.field label="Parcelamento" name="installments" hint="1 a 12">
                        <x-ui.select wire:model.live="installments" :invalid="$errors->has('installments')">
                            @foreach (range(1, 12) as $option)
                                <option value="{{ $option }}">{{ $option }}×</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Primeiro vencimento" name="firstDueDays" hint="dias após a aprovação">
                        <x-ui.input type="number" min="0" max="90" wire:model.live.debounce.400ms="firstDueDays" :invalid="$errors->has('firstDueDays')" />
                    </x-ui.field>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-ink">Itens</h2>
                    <p class="text-xs text-muted">Preço unitário vem do catálogo</p>
                </div>

                <div class="mt-4 hidden grid-cols-12 gap-3 px-1 text-[10px] uppercase tracking-[0.16em] text-muted sm:grid">
                    <div class="col-span-5">Serviço</div>
                    <div class="col-span-2">Qtd.</div>
                    <div class="col-span-2">Desconto</div>
                    <div class="col-span-2 text-right">Total</div>
                    <div class="col-span-1"></div>
                </div>

                <div class="mt-2 space-y-3">
                    @foreach ($lines as $line)
                        @php $item = $items[$line['index']]; @endphp
                        <div wire:key="quotation-item-{{ $line['index'] }}" class="grid grid-cols-12 items-start gap-3 rounded-xl border border-line-soft bg-surface-soft/40 p-3 sm:border-0 sm:bg-transparent sm:p-1">
                            <div class="col-span-12 sm:col-span-5">
                                <x-ui.select wire:model.live="items.{{ $line['index'] }}.service_id" data-testid="item-{{ $line['index'] }}-service" :invalid="$errors->has('items.'.$line['index'].'.service_id')">
                                    <option value="">Selecione…</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->id }}">{{ $service->name }}</option>
                                    @endforeach
                                </x-ui.select>
                                @error("items.{$line['index']}.service_id")
                                    <span class="mt-1 block text-xs text-rust">{{ $message }}</span>
                                @enderror
                                @if ($line['service'])
                                    <span class="mt-1 block text-[11px] text-muted">
                                        {{ $line['service']->price()->format() }} / {{ $line['service']->unit }}
                                        @if (! $line['service']->active)
                                            <span class="text-amber">· inativo no catálogo</span>
                                        @endif
                                    </span>
                                @endif
                            </div>

                            <div class="col-span-4 sm:col-span-2">
                                <span class="mb-1 block text-[10px] uppercase tracking-wider text-muted sm:hidden">Qtd.</span>
                                <x-ui.input type="number" min="1" max="1000" wire:model.live.debounce.500ms="items.{{ $line['index'] }}.quantity" data-testid="item-{{ $line['index'] }}-quantity" :invalid="$errors->has('items.'.$line['index'].'.quantity')" />
                            </div>

                            <div class="col-span-5 sm:col-span-2">
                                <span class="mb-1 block text-[10px] uppercase tracking-wider text-muted sm:hidden">Desconto (R$)</span>
                                <x-ui.input wire:model.live.debounce.500ms="items.{{ $line['index'] }}.discount" inputmode="decimal" placeholder="0,00" data-testid="item-{{ $line['index'] }}-discount" :invalid="$errors->has('items.'.$line['index'].'.discount')" />
                            </div>

                            <div class="col-span-2 sm:col-span-2 sm:pt-2.5 sm:text-right">
                                <x-ui.money :cents="$line['total_cents']" class="text-sm font-medium" />
                            </div>

                            <div class="col-span-1 flex justify-end sm:pt-1">
                                <button type="button" wire:click="removeItem({{ $line['index'] }})" class="rounded-md p-1.5 text-muted hover:bg-rust-soft hover:text-rust" title="Remover item">
                                    <x-ui.icon name="x" class="size-4" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" wire:click="addItem" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-dashed border-line px-3 py-2 text-xs font-medium text-ink-soft transition-colors hover:border-clay hover:text-clay">
                    <x-ui.icon name="plus" class="size-3.5" />
                    Adicionar item
                </button>
            </x-ui.card>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button wire:click="saveDraft" wire:loading.attr="disabled">
                    Salvar rascunho
                </x-ui.button>
                <x-ui.button variant="ink" wire:click="saveAndSend" wire:loading.attr="disabled" data-testid="save-and-send">
                    <x-ui.icon name="link" class="size-4" />
                    Salvar e enviar
                </x-ui.button>
                <x-ui.button variant="ghost" :href="route('quotations.index')">Cancelar</x-ui.button>
                <span wire:loading class="text-xs text-muted">processando…</span>
            </div>
        </div>

        <div class="space-y-6 lg:sticky lg:top-8">
            <x-ui.card>
                <p class="eyebrow">Resumo</p>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Subtotal</dt>
                        <dd><x-ui.money :cents="$subtotal" /></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-muted">Descontos</dt>
                        <dd><x-ui.money :cents="$discountTotal" :class="$discountTotal > 0 ? 'text-clay-deep' : 'text-muted'" /></dd>
                    </div>
                </dl>

                <div class="mt-4 flex items-end justify-between border-t border-line pt-4">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted">Total</p>
                    <x-ui.money :cents="$total" data-testid="quotation-total" class="serif-display text-3xl text-ink" />
                </div>

                <div class="mt-5 border-t border-line pt-4">
                    <p class="eyebrow">Parcelas</p>
                    @if ($schedule === [])
                        <p class="mt-2 text-xs text-muted">Adicione itens para ver o parcelamento.</p>
                    @else
                        <ul class="mt-2 space-y-1.5 text-xs">
                            @foreach ($schedule as $index => $part)
                                <li class="flex items-center justify-between gap-3">
                                    <span class="text-muted">{{ $index + 1 }}ª · {{ $firstDue->copy()->addMonthsNoOverflow($index)->translatedFormat('d M Y') }}</span>
                                    <x-ui.money :cents="$part->cents" />
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-2 text-[11px] leading-relaxed text-muted">Divisão inteira: a diferença de centavos fica na primeira parcela.</p>
                    @endif
                </div>
            </x-ui.card>

            <div class="rounded-2xl border border-line bg-surface-soft p-5 text-xs leading-relaxed text-muted">
                <p class="flex items-center gap-2 font-medium text-ink-soft"><x-ui.icon name="alert" class="size-4 text-amber" /> Preço congelado</p>
                <p class="mt-2">O valor unitário é copiado do catálogo neste momento. Mudar o preço do serviço depois não altera este orçamento nem o pedido que nascer dele.</p>
            </div>
        </div>
    </div>
</div>
