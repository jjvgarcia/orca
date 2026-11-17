@props(['tone' => 'neutral', 'dot' => true])

@php
    $tones = [
        'neutral' => 'bg-surface-soft text-muted border-line',
        'clay' => 'bg-clay-soft text-clay-deep border-clay/15',
        'pine' => 'bg-pine-soft text-pine border-pine/15',
        'amber' => 'bg-amber-soft text-amber border-amber/20',
        'rust' => 'bg-rust-soft text-rust border-rust/20',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[11px] font-medium tracking-wide ' . ($tones[$tone] ?? $tones['neutral'])]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full bg-current opacity-70"></span>
    @endif
    {{ $slot }}
</span>
