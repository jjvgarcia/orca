@props(['cents', 'class' => ''])

<span {{ $attributes->merge(['class' => 'font-mono whitespace-nowrap tabular-nums ' . $class]) }}>{{ \App\Domain\Money::fromCents((int) $cents)->format() }}</span>
