@props(['eyebrow' => null, 'title', 'description' => null])

<header class="mb-8 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="serif-display mt-1 text-3xl leading-tight text-ink sm:text-4xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
