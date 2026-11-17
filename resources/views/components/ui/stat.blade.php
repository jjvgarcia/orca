@props(['label', 'tone' => 'ink'])

@php
    $tones = [
        'ink' => 'text-ink',
        'clay' => 'text-clay-deep',
        'pine' => 'text-pine',
        'rust' => 'text-rust',
        'amber' => 'text-amber',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-surface p-5']) }}>
    <p class="eyebrow">{{ $label }}</p>
    <p class="mt-3 font-mono text-2xl tabular-nums tracking-tight {{ $tones[$tone] ?? $tones['ink'] }}">{{ $slot }}</p>
    @isset($sub)
        <p class="mt-1.5 text-xs text-muted">{{ $sub }}</p>
    @endisset
</div>
