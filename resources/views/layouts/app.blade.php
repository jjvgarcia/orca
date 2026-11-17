@php
    $navigation = [
        ['label' => 'Painel', 'route' => 'dashboard', 'icon' => 'grid', 'match' => 'dashboard'],
        ['label' => 'Clientes', 'route' => 'customers.index', 'icon' => 'users', 'match' => 'customers.*'],
        ['label' => 'Serviços', 'route' => 'services.index', 'icon' => 'briefcase', 'match' => 'services.*'],
        ['label' => 'Orçamentos', 'route' => 'quotations.index', 'icon' => 'file-text', 'match' => 'quotations.*'],
        ['label' => 'Pedidos', 'route' => 'orders.index', 'icon' => 'receipt', 'match' => 'orders.*'],
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · Orça' : 'Orça — do orçamento ao recebível' }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh">
    <div class="lg:grid lg:min-h-dvh lg:grid-cols-[264px_minmax(0,1fr)]">
        <aside class="no-print sticky top-0 hidden h-dvh flex-col bg-ink px-5 py-7 text-paper lg:flex">
            <a href="{{ route('dashboard') }}" class="block">
                <x-partials.brand />
            </a>

            <nav class="mt-10 flex flex-1 flex-col gap-1">
                <p class="mb-2 px-3 text-[10px] uppercase tracking-[0.14em] text-paper/35">Operação</p>
                @foreach ($navigation as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors',
                           'bg-white/10 text-paper' => $active,
                           'text-paper/60 hover:bg-white/5 hover:text-paper' => ! $active,
                       ])>
                        <x-ui.icon :name="$item['icon']" @class(['size-4', 'text-clay' => $active, 'text-paper/40' => ! $active]) />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-white/10 pt-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-full bg-paper/10 font-serif text-sm text-paper">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm text-paper">{{ auth()->user()->name }}</p>
                        <p class="text-[11px] uppercase tracking-wider text-paper/40">{{ auth()->user()->role->label() }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md p-1.5 text-paper/40 transition-colors hover:bg-white/10 hover:text-paper" title="Sair">
                            <x-ui.icon name="logout" class="size-4" />
                            <span class="sr-only">Sair</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="no-print sticky top-0 z-20 border-b border-line bg-paper/95 px-5 py-3 backdrop-blur lg:hidden">
                <div class="flex items-center justify-between">
                    <a href="{{ route('dashboard') }}"><x-partials.brand mark-class="size-6" label-class="text-lg" /></a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md p-2 text-muted hover:bg-surface-soft">
                            <x-ui.icon name="logout" class="size-4" />
                            <span class="sr-only">Sair</span>
                        </button>
                    </form>
                </div>
                <nav class="mt-3 flex flex-wrap gap-1 pb-0.5">
                    @foreach ($navigation as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-medium',
                               'bg-ink text-paper' => $active,
                               'text-muted hover:bg-surface-soft' => ! $active,
                           ])>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            </header>

            <main class="mx-auto w-full max-w-6xl px-5 py-8 lg:px-10 lg:py-12">
                <x-ui.flash />
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
