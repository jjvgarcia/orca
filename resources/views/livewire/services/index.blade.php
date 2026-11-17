<div>
    <x-ui.page-header eyebrow="Catálogo" title="Serviços" description="Preço unitário em reais, congelado no item do orçamento no momento da criação. Inativar não apaga histórico.">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button wire:click="startCreate">
                    <x-ui.icon name="plus" class="size-4" />
                    Novo serviço
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    @if ($showForm)
        <x-ui.card class="mb-6">
            <form wire:submit="save" class="space-y-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-ink">{{ $editingId ? 'Editar serviço' : 'Novo serviço' }}</h2>
                    <button type="button" wire:click="cancel" class="text-muted hover:text-ink"><x-ui.icon name="x" class="size-4" /></button>
                </div>

                <div class="grid gap-5 sm:grid-cols-[2fr_1fr_1fr]">
                    <x-ui.field label="Nome" name="name">
                        <x-ui.input wire:model="name" placeholder="Identidade visual" :invalid="$errors->has('name')" autofocus />
                    </x-ui.field>

                    <x-ui.field label="Unidade" name="unit">
                        <x-ui.select wire:model="unit" :invalid="$errors->has('unit')">
                            @foreach (['unidade', 'hora', 'mês', 'projeto', 'página'] as $unitOption)
                                <option value="{{ $unitOption }}">{{ ucfirst($unitOption) }}</option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>

                    <x-ui.field label="Preço unitário" name="price" hint="R$">
                        <x-ui.input wire:model="price" inputmode="decimal" placeholder="1.200,00" :invalid="$errors->has('price')" />
                    </x-ui.field>
                </div>

                <div class="flex flex-wrap items-center gap-4 border-t border-line-soft pt-5">
                    <label class="flex items-center gap-2 text-sm text-ink-soft">
                        <input type="checkbox" wire:model="active" class="size-4 rounded border-line text-clay focus:ring-clay/30">
                        Ativo no catálogo
                    </label>

                    <div class="ml-auto flex items-center gap-2">
                        <x-ui.button variant="ghost" type="button" wire:click="cancel">Cancelar</x-ui.button>
                        <x-ui.button type="submit">
                            <x-ui.icon name="check" class="size-4" />
                            Salvar
                        </x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card :padded="false">
        <div class="overflow-x-auto">
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th class="px-5 pt-5 sm:px-6">Serviço</th>
                        <th class="hidden px-3 pt-5 sm:table-cell">Unidade</th>
                        <th class="px-3 pt-5 text-right">Preço</th>
                        <th class="hidden px-3 pt-5 text-right md:table-cell">Usos</th>
                        <th class="px-3 pt-5">Status</th>
                        <th class="px-5 pt-5 sm:px-6"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr @class(['opacity-60' => ! $service->active])>
                            <td class="px-5 sm:px-6">
                                <p class="text-sm font-medium text-ink">{{ $service->name }}</p>
                            </td>
                            <td class="hidden px-3 text-xs text-muted sm:table-cell">{{ ucfirst($service->unit) }}</td>
                            <td class="px-3 text-right">
                                <x-ui.money :cents="$service->unit_price_cents" class="text-sm" />
                            </td>
                            <td class="hidden px-3 text-right font-mono text-xs tabular-nums text-muted md:table-cell">{{ $service->quotation_items_count }}</td>
                            <td class="px-3">
                                @if ($service->active)
                                    <x-ui.badge tone="pine">Ativo</x-ui.badge>
                                @else
                                    <x-ui.badge>Inativo</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-5 text-right sm:px-6">
                                @if ($canManage)
                                    <div class="flex items-center justify-end gap-1">
                                        <x-ui.button variant="ghost" size="sm" wire:click="startEdit({{ $service->id }})">Editar</x-ui.button>
                                        <x-ui.button variant="ghost" size="sm" wire:click="toggle({{ $service->id }})">
                                            {{ $service->active ? 'Inativar' : 'Ativar' }}
                                        </x-ui.button>
                                    </div>
                                @else
                                    <span class="text-[11px] text-muted">somente leitura</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 sm:px-6">
                                <x-ui.empty icon="briefcase" title="Catálogo vazio" description="Cadastre os serviços que você vende para montar orçamentos em segundos.">
                                    <x-slot:action>
                                        @if ($canManage)
                                            <x-ui.button size="sm" wire:click="startCreate">Cadastrar serviço</x-ui.button>
                                        @endif
                                    </x-slot:action>
                                </x-ui.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if (! $canManage)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-line bg-surface-soft px-4 py-3 text-xs leading-relaxed text-muted">
            <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0 text-amber" />
            <p>Seu papel é operador: o catálogo e seus preços só podem ser alterados por um administrador.</p>
        </div>
    @endif
</div>
