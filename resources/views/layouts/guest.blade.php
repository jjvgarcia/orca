<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>{{ isset($title) ? $title.' · Orça' : 'Entrar · Orça' }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh">
    <div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
        <aside class="grain relative hidden flex-col justify-between overflow-hidden bg-ink px-12 py-12 text-paper lg:flex 2xl:px-20 2xl:py-16">
            <x-partials.brand mark-class="size-8 2xl:size-9" label-class="text-2xl 2xl:text-3xl" />

            <div class="max-w-md 2xl:max-w-xl">
                <p class="text-[11px] uppercase tracking-[0.24em] text-clay 2xl:text-xs">Peça de portfólio</p>
                <p class="serif-display mt-4 text-4xl leading-[1.1] text-paper 2xl:text-5xl">
                    Do orçamento ao recebível,<br>sem planilha no meio.
                </p>
                <p class="mt-5 text-sm leading-relaxed text-paper/55 2xl:mt-6 2xl:text-base">
                    Preço congelado na aprovação, parcelas que fecham no centavo e pagamento
                    que não duplica quando o cliente clica duas vezes.
                </p>
            </div>

            <ul class="space-y-3 text-xs text-paper/45 2xl:space-y-4 2xl:text-[13px]">
                <li class="flex items-center gap-3"><span class="size-1.5 rounded-full bg-clay"></span> dinheiro em centavos, nunca em float</li>
                <li class="flex items-center gap-3"><span class="size-1.5 rounded-full bg-pine"></span> link assinado, decisão terminal</li>
                <li class="flex items-center gap-3"><span class="size-1.5 rounded-full bg-amber"></span> idempotência no registro de pagamento</li>
            </ul>
        </aside>

        <main class="flex items-center justify-center px-6 py-12 2xl:px-16">
            <div class="w-full max-w-sm 2xl:max-w-md">
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
