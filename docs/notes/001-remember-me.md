# 001 — "Manter conectado" tem efeito real?

**Data:** 2026-11-17
**Status:** comportamento verificado; sem teste automatizado dedicado
**Arquivos:** `app/Livewire/Auth/Login.php`, `routes/web.php`, `config/session.php`

## Pergunta

O checkbox "Manter conectado" ativa alguma funcionalidade ou é apenas decoração de login?

## Resposta

Ativa. O checkbox usa `wire:model="remember"` e o valor booleano é passado como segundo
argumento do guard:

```php
// app/Livewire/Auth/Login.php
if (! Auth::attempt($credentials, $this->remember)) {
```

Não existe lógica própria de "remember me": é o comportamento padrão do Laravel, com
`remember_token` na tabela `users` e o cookie `remember_web_*` emitido pelo `SessionGuard`.

## Comportamento observado

| Checkbox | Cookie de sessão | Cookie de remember | Depois de apagar `orca-session` |
|---|---|---|---|
| desmarcado | `orca-session`, 120 min | não existe | volta para `/login` |
| marcado | `orca-session`, 120 min | `remember_web_*`, 400 dias | continua em `/dashboard` e recebe uma sessão nova |

- `SESSION_LIFETIME=120` em `config/session.php` (padrão do `.env`).
- 400 dias é o padrão do framework (`AuthManager` → 576000 minutos).
- No logout, `Auth::logout()` cicla o `remember_token` e limpa o cookie de remember antes de
  `session()->invalidate()` (`routes/web.php`).

## Evidência

Verificação manual com Playwright em 2026-11-17, contra o demo:

1. login com o checkbox desmarcado → nenhum `remember_web_*`; ao apagar `orca-session`,
   `/dashboard` redireciona para `/login`.
2. login com o checkbox marcado → `remember_web_*` presente com expiração de 400 dias; ao
   apagar `orca-session`, `/dashboard` responde 200 e o browser recebe um novo `orca-session`.

## Lacuna

A suíte não tem um teste específico para o checkbox. Os fluxos de login, autorização e
pagamento estão cobertos; este comportamento foi provado só na verificação acima. Se virar
requisito, o teste natural é um E2E com dois contextos (com e sem o checkbox) que apaga o
cookie de sessão e afirma o destino.

## Como reproduzir

```bash
ddev start
ddev artisan demo:reset
```

Abra `/login`, inspecione os cookies no DevTools, entre com e sem o checkbox e compare
`orca-session` e `remember_web_*`. Para simular a perda da sessão, apague apenas
`orca-session` e recarregue `/dashboard`.
