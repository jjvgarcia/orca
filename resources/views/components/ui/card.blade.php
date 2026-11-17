@props(['padded' => true])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-surface ' . ($padded ? 'p-5 sm:p-6' : '')]) }}>
    {{ $slot }}
</section>
