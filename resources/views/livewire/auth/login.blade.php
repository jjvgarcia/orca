<div>
    <div class="mb-8 lg:hidden">
        <x-partials.brand />
    </div>

    <p class="eyebrow">Acesso ao painel</p>
    <h1 class="serif-display mt-2 text-3xl text-ink">Entrar na operação</h1>
    <p class="mt-2 text-sm leading-relaxed text-muted">
        O cliente final não precisa de conta: ele recebe um link assinado do orçamento.
    </p>

    <form wire:submit="login" class="mt-8 space-y-4">
        <x-ui.field label="E-mail" name="email">
            <x-ui.input type="email" wire:model="email" autocomplete="username" :invalid="$errors->has('email')" autofocus />
        </x-ui.field>

        <x-ui.field label="Senha" name="password">
            <x-ui.input type="password" wire:model="password" autocomplete="current-password" :invalid="$errors->has('password')" />
        </x-ui.field>

        <label class="flex items-center gap-2 text-sm text-ink-soft">
            <input type="checkbox" wire:model="remember" class="size-4 rounded border-line text-clay focus:ring-clay/30">
            Manter conectado
        </label>

        <x-ui.button type="submit" class="w-full" size="lg">
            Entrar
            <x-ui.icon name="arrow-up-right" class="size-4" />
        </x-ui.button>
    </form>

    <div class="mt-8 rounded-xl border border-dashed border-line bg-surface-soft/60 px-4 py-3 text-xs leading-relaxed text-muted">
        <p class="font-medium text-ink-soft">Ambiente de demonstração</p>
        <p class="mt-1">Administrador: <span class="font-mono">demo@orca.test</span> · senha <span class="font-mono">password</span></p>
        <p>Operador: <span class="font-mono">operador@orca.test</span> · senha <span class="font-mono">password</span></p>
        <p class="mt-2 text-[11px]">Rode <span class="font-mono">ddev artisan demo:reset</span> para recriar os dados.</p>
    </div>
</div>
