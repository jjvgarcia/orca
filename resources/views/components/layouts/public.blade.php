@props(['title' => null])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · Orça' : 'Orçamento · Orça' }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-paper">
    <header class="no-print border-b border-line">
        <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-5 py-5">
            <x-partials.brand mark-class="size-7" label-class="text-xl" />
            <span class="text-[11px] uppercase tracking-[0.2em] text-muted">Orçamento</span>
        </div>
    </header>

    <main class="mx-auto w-full max-w-4xl px-5 py-8 sm:py-12">
        {{ $slot }}
    </main>

    <footer class="no-print mx-auto w-full max-w-4xl px-5 pb-10 pt-2 text-xs text-muted">
        <p>Orça · documento gerado do orçamento original. Em caso de dúvida, responda o contato que enviou este link.</p>
    </footer>
</body>
</html>
