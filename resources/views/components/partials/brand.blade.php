@props(['class' => '', 'markClass' => 'size-7', 'labelClass' => 'text-xl'])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 ' . $class]) }}>
    <svg class="{{ $markClass }} text-clay" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <circle cx="16" cy="16" r="13" stroke="currentColor" stroke-width="1.6" />
        <path d="M8.8 23.2 23.2 8.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        <circle cx="16" cy="16" r="3.2" fill="currentColor" />
    </svg>
    <span class="flex flex-col leading-none">
        <span class="serif-display {{ $labelClass }}">Orça</span>
        <span class="mt-0.5 text-[9px] uppercase tracking-[0.14em] opacity-60">orçamento → recebível</span>
    </span>
</span>
