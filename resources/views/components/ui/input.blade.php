@props(['invalid' => false])

<input {{ $attributes->merge([
    'class' => 'h-10 w-full rounded-lg border bg-surface px-3 text-sm text-ink placeholder:text-muted/60 transition-colors focus:outline-none focus:ring-2 '
        . ($invalid ? 'border-rust focus:border-rust focus:ring-rust/15' : 'border-line focus:border-clay focus:ring-clay/15'),
]) }} />
