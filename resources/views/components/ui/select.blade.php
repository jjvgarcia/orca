@props(['invalid' => false])

<select {{ $attributes->merge([
    'class' => 'h-10 w-full appearance-none rounded-lg border bg-surface px-3 text-sm text-ink transition-colors focus:outline-none focus:ring-2 '
        . ($invalid ? 'border-rust focus:border-rust focus:ring-rust/15' : 'border-line focus:border-clay focus:ring-clay/15'),
]) }}>
    {{ $slot }}
</select>
