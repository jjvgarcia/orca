@props(['label', 'name' => null, 'hint' => null])

<label {{ $attributes->only('class')->merge(['class' => 'block']) }}>
    <span class="mb-1.5 flex items-baseline justify-between gap-3">
        <span class="text-sm font-medium text-ink-soft">{{ $label }}</span>
        @if ($hint)
            <span class="text-xs text-muted">{{ $hint }}</span>
        @endif
    </span>
    {{ $slot }}
    @if ($name)
        @error($name)
            <span class="mt-1.5 block text-xs text-rust">{{ $message }}</span>
        @enderror
    @endif
</label>
