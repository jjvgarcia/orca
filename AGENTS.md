# AGENTS.md

Orça — Laravel 13 + Livewire 4 + PostgreSQL 16, rodando em DDEV.

## Comandos

- Subir: `ddev start` (URL em `ddev describe`)
- Instalar: `ddev composer install` · `ddev npm install`
- Build de assets: `ddev npm run build`
- Migrar/recriar demo: `ddev artisan demo:reset` (~1 s, idempotente; nunca em produção)
- Testes: `ddev exec ./vendor/bin/pest` (usa o banco `db_test`)
- E2E: `npx playwright test` (contra `ORCA_URL`, padrão `https://orca.ddev.site:30001`)
- Formatação: `ddev exec ./vendor/bin/pint`

## Regras do domínio (não relaxar)

- Dinheiro é `int` de centavos (`*_cents`). Nunca float, `decimal` ou cálculo monetário em
  ponto flutuante; divisão usa `App\Domain\Money::split` (resto na primeira parcela).
- `quotation_items.unit_price_cents` é snapshot. O catálogo pode mudar; orçamento aprovado e
  pedido não.
- Status de recebível é derivado (`paid > overdue > partial > open`); não criar coluna.
- Expiração de orçamento é derivada de `valid_until`; não persistir `expired`.
- Decisão do cliente é terminal. Aprovar cria o pedido e as parcelas na mesma transação;
  `orders.quotation_id` é único.
- Pagamento exige `idempotency_key`: mesma chave + mesmo payload = replay; payload diferente
  = 409; sobrepagamento = 422.
- Operador não arquiva cliente nem altera catálogo/preço. Autorização fica na policy.
- Erros de domínio usam `DomainRuleViolation` com o status HTTP; nunca retornar 200 para
  violação.

## Estilo

- Identificadores em inglês; textos de interface e comentários em pt-BR.
- TypeScript-like strictness não se aplica; PHP com tipos, `final`/`readonly` onde ajuda.
- Componentes Livewire de página em `app/Livewire`, views em `resources/views/livewire`.
- Componentes de UI em `resources/views/components/ui`; não duplicar classes de botão/input.
- Rotas em inglês (`/customers`, `/quotations`), UI em pt-BR.

## Prova antes de pronto

Toda mudança de domínio precisa de teste Pest. Fluxo de UI novo precisa renderizar em
`tests/Feature/ComponentsTest.php` e, se for fluxo de ponta a ponta, um spec em `e2e/`.
Rode Pest + Playwright e `demo:reset` duas vezes antes de considerar concluído.
