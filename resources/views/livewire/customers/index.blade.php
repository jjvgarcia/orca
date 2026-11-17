<div>
    <x-ui.page-header eyebrow="Cadastro" title="Clientes" description="Quem recebe orçamento. PF ou PJ, com documento e contato — o histórico nunca é apagado.">
        <x-slot:actions>
            <x-ui.button :href="route('customers.create')">
                <x-ui.icon name="plus" class="size-4" />
                Novo cliente
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 flex items-center gap-3">
        <div class="relative w-full max-w-sm">
            <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Buscar por nome, documento ou e-mail" class="pl-9" />
        </div>
        @if ($search !== '')
            <button wire:click="$set('search', '')" class="text-xs text-muted underline underline-offset-4 hover:text-ink">limpar</button>
        @endif
    </div>

    @if ($customers->isEmpty())
        <x-ui.card>
            <x-ui.empty
                icon="users"
                :title="$search === '' ? 'Nenhum cliente cadastrado' : 'Nenhum cliente encontrado'"
                :description="$search === '' ? 'Cadastre o primeiro cliente para começar um orçamento.' : 'Tente outro nome, documento ou e-mail.'"
            >
                <x-slot:action>
                    @if ($search === '')
                        <x-ui.button :href="route('customers.create')" size="sm">Cadastrar cliente</x-ui.button>
                    @endif
                </x-slot:action>
            </x-ui.empty>
        </x-ui.card>
    @else
        <x-ui.card :padded="false">
            <div class="overflow-x-auto">
                <table class="ledger-table">
                    <thead>
                        <tr>
                            <th class="px-5 pt-5 sm:px-6">Cliente</th>
                            <th class="hidden px-3 pt-5 sm:table-cell">Documento</th>
                            <th class="hidden px-3 pt-5 md:table-cell">Contato</th>
                            <th class="px-3 pt-5 text-right">Orçamentos</th>
                            <th class="px-5 pt-5 sm:px-6"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr class="group">
                                <td class="px-5 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full border border-line bg-surface-soft text-xs font-medium text-ink-soft">
                                            {{ mb_substr($customer->name, 0, 2) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-ink">{{ $customer->name }}</p>
                                            <p class="mt-0.5 text-xs text-muted">{{ $customer->kindLabel() }} · {{ $customer->orders_count }} {{ \Illuminate\Support\Str::plural('pedido', $customer->orders_count) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden px-3 font-mono text-xs text-ink-soft sm:table-cell">{{ $customer->document ?: '—' }}</td>
                                <td class="hidden px-3 md:table-cell">
                                    <p class="text-xs text-ink-soft">{{ $customer->email ?: '—' }}</p>
                                    <p class="text-xs text-muted">{{ $customer->phone }}</p>
                                </td>
                                <td class="px-3 text-right font-mono text-sm tabular-nums text-ink-soft">{{ $customer->quotations_count }}</td>
                                <td class="px-5 text-right sm:px-6">
                                    <x-ui.button variant="ghost" size="sm" :href="route('customers.edit', $customer)">Editar</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div class="mt-5">{{ $customers->links() }}</div>
    @endif
</div>
