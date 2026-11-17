@props(['title', 'description' => null, 'icon' => 'file-text'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-xl border border-dashed border-line px-6 py-10 text-center']) }}>
    <span class="flex size-10 items-center justify-center rounded-full bg-surface-soft text-muted">
        <x-ui.icon :name="$icon" class="size-5" />
    </span>
    <p class="mt-3 text-sm font-medium text-ink">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-xs leading-relaxed text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
