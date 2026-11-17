@props(['value' => 0, 'max' => 100, 'tone' => 'pine'])

@php
    $max = max(1, (int) $max);
    $percent = min(100, max(0, intdiv((int) $value * 100, $max)));
    $tones = ['pine' => 'bg-pine', 'clay' => 'bg-clay', 'amber' => 'bg-amber', 'rust' => 'bg-rust'];
@endphp

<div {{ $attributes->merge(['class' => 'h-1.5 w-full overflow-hidden rounded-full bg-line-soft']) }}>
    <div class="h-full rounded-full {{ $tones[$tone] ?? $tones['pine'] }}" style="width: {{ $percent }}%"></div>
</div>
