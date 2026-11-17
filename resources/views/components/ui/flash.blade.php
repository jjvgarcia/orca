@if (session('status'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-pine/20 bg-pine-soft px-4 py-3 text-sm text-pine">
        <x-ui.icon name="check" class="mt-0.5 size-4 shrink-0" />
        <p>{{ session('status') }}</p>
    </div>
@endif

@if (session('error'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-rust/20 bg-rust-soft px-4 py-3 text-sm text-rust">
        <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0" />
        <p>{{ session('error') }}</p>
    </div>
@endif

@if ($errors->any() && ! $errors->hasBag('default') === false)
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-rust/20 bg-rust-soft px-4 py-3 text-sm text-rust">
        <x-ui.icon name="alert" class="mt-0.5 size-4 shrink-0" />
        <div class="space-y-1">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    </div>
@endif
