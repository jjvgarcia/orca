<div>
    <div class="mb-6 flex items-center gap-2 text-xs text-muted">
        <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-1 hover:text-ink">
            <x-ui.icon name="chevron-left" class="size-3.5" /> Clientes
        </a>
    </div>

    <x-ui.page-header eyebrow="Cadastro" :title="$title" description="Documento e contato são usados no orçamento e no link público." />

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <x-ui.card>
            <form wire:submit="save" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-ui.field label="Nome" name="name">
                            <x-ui.input wire:model="name" placeholder="Ateliê Norte · ou nome da pessoa" :invalid="$errors->has('name')" autofocus />
                        </x-ui.field>
                    </div>

                    <div>
                        <span class="mb-1.5 block text-sm font-medium text-ink-soft">Tipo</span>
                        <div class="grid grid-cols-2 gap-2">
                            <label @class(['flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm', 'border-clay bg-clay-soft text-clay-deep' => $kind === 'pf', 'border-line text-ink-soft' => $kind !== 'pf'])>
                                <input type="radio" wire:model.live="kind" value="pf" class="sr-only">
                                Pessoa física
                            </label>
                            <label @class(['flex cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm', 'border-clay bg-clay-soft text-clay-deep' => $kind === 'pj', 'border-line text-ink-soft' => $kind !== 'pj'])>
                                <input type="radio" wire:model.live="kind" value="pj" class="sr-only">
                                Pessoa jurídica
                            </label>
                        </div>
                        @error('kind') <span class="mt-1.5 block text-xs text-rust">{{ $message }}</span> @enderror
                    </div>

                    <x-ui.field label="Documento" name="document" hint="CPF ou CNPJ">
                        <x-ui.input wire:model="document" placeholder="00.000.000/0000-00" :invalid="$errors->has('document')" />
                    </x-ui.field>

                    <x-ui.field label="E-mail" name="email">
                        <x-ui.input type="email" wire:model="email" placeholder="contato@cliente.com" :invalid="$errors->has('email')" />
                    </x-ui.field>

                    <x-ui.field label="Telefone" name="phone">
                        <x-ui.input wire:model="phone" placeholder="(11) 99999-0000" :invalid="$errors->has('phone')" />
                    </x-ui.field>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-line-soft pt-5">
                    <x-ui.button type="submit">
                        <x-ui.icon name="check" class="size-4" />
                        {{ $customer?->exists ? 'Salvar alterações' : 'Cadastrar cliente' }}
                    </x-ui.button>
                    <x-ui.button variant="ghost" :href="route('customers.index')">Cancelar</x-ui.button>

                    @if ($customer?->exists)
                        @can('delete', $customer)
                            <x-ui.button type="button" variant="danger" class="ml-auto" wire:click="delete" wire:confirm="Arquivar este cliente? O histórico de orçamentos e pedidos permanece.">
                                Arquivar cliente
                            </x-ui.button>
                        @endcan
                    @endif
                </div>
            </form>
        </x-ui.card>

        <div class="space-y-6">
            @if ($customer?->exists)
                <x-ui.card :padded="false">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="text-sm font-semibold text-ink">Histórico</h2>
                        <p class="mt-0.5 text-xs text-muted">Últimos orçamentos deste cliente</p>
                    </div>
                    @if ($quotations->isEmpty())
                        <div class="p-5">
                            <x-ui.empty icon="file-text" title="Sem orçamentos ainda" />
                        </div>
                    @else
                        <ul class="divide-y divide-line-soft">
                            @foreach ($quotations as $quotation)
                                <li class="px-5 py-3">
                                    <a href="{{ route('quotations.show', $quotation) }}" class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-ink">{{ $quotation->code() }}</p>
                                            <p class="text-xs text-muted">{{ $quotation->items_count }} {{ \Illuminate\Support\Str::plural('item', $quotation->items_count) }} · {{ $quotation->valid_until->translatedFormat('d M') }}</p>
                                        </div>
                                        <div class="text-right">
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
            @endif

            <div class="rounded-2xl border border-line bg-surface-soft p-5 text-xs leading-relaxed text-muted">
                <p class="flex items-center gap-2 font-medium text-ink-soft"><x-ui.icon name="alert" class="size-4 text-amber" /> Permissões</p>
                <p class="mt-2">Operadores podem criar e editar clientes, mas apenas administradores arquivam um cliente.</p>
            </div>
        </div>
    </div>
</div>
