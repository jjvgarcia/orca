@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-clay text-surface hover:bg-clay-deep border border-transparent',
        'ink' => 'bg-ink text-paper hover:bg-ink-soft border border-transparent',
        'secondary' => 'bg-surface text-ink-soft border border-line hover:border-muted/50 hover:bg-surface-soft',
        'ghost' => 'bg-transparent text-ink-soft border border-transparent hover:bg-surface-soft',
        'danger' => 'bg-rust-soft text-rust border border-rust/20 hover:bg-rust/15',
    ];
    $sizes = [
        'sm' => 'h-8 px-3 text-xs gap-1.5',
        'md' => 'h-10 px-4 text-sm gap-2',
        'lg' => 'h-11 px-5 text-sm gap-2',
    ];
    $classes = 'inline-flex items-center justify-center rounded-lg font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay disabled:cursor-not-allowed disabled:opacity-50 '
        . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
