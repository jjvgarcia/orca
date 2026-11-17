# SPEC — Orça (implementado)

> Revisão executada da SPEC de portfólio. As decisões abaixo são as que o código segue;
> divergências em relação ao rascunho original estão marcadas com **Δ**.

## 1. Problema

Prestador de serviço fecha negócio por planilha, WhatsApp e boca. O recorte é o ciclo
comercial curto que quebra primeiro quando há dinheiro, versão de preço e prazo:

**orçamento → pedido → parcela a receber.**

## 2. Stack (Δ)

| Camada | Escolha | Motivo |
|---|---|---|
| Framework | Laravel 13 · PHP 8.4 | stack pedida pelo comprador de sistema |
| UI do painel | **Livewire 4 + Tailwind 4 + Blade** (sem Filament) | UI própria e domínio visível; Filament ficaria com cara de gerador |
| Banco | PostgreSQL 16 | `CHECK`, índice único, locks e advisory locks |
| PDF | `barryvdh/laravel-dompdf` | PHP puro, sem Chromium |
| Link público | `URL::temporarySignedRoute` | sem senha e sem tabela de tokens |
| Testes | Pest 5 + `RefreshDatabase` em `db_test` | feature/unit dos invariantes |
| E2E | Playwright 1.61 | aprovação e pagamento repetido |
| Dev | **DDEV** (Δ de Docker Compose) | ambiente do restante do portfólio; Postgres 16 e Node 22 no container |

## 3. Modelo de dados

```
customers          id, name, kind('pf'|'pj'), document, email, phone, deleted_at
services           id, name, unit, unit_price_cents, active, deleted_at
quotations         id, customer_id, number, version, status, valid_until,
                   installments(1..12), first_due_days(0..90),
                   subtotal_cents, discount_cents, total_cents,
                   decided_at, decided_ip, decided_user_agent, parent_id
quotation_items    id, quotation_id, service_id, description, quantity,
                   unit_price_cents, discount_cents, total_cents
orders             id, quotation_id UNIQUE, customer_id, number UNIQUE,
                   status, total_cents, created_at
receivables        id, order_id, sequence, due_date, amount_cents
                   UNIQUE(order_id, sequence)
payments           id, receivable_id, amount_cents, method, paid_at,
                   idempotency_key, fingerprint
                   UNIQUE(receivable_id, idempotency_key)
quotation_events   id, quotation_id, from_status, to_status, actor, meta(jsonb), created_at
```

**Δ em relação ao rascunho**

- `quotations.installments` e `quotations.first_due_days` existem porque a aprovação cria o
  pedido; sem as condições na proposta, o parcelamento não teria de onde nascer.
- `payments.fingerprint` guarda o hash de `valor|método|data` para distinguir replay de
  conflito na mesma chave.
- `orders.quotation_id` é **único**: um orçamento aprovado gera no máximo um pedido.
- Não existe `order_items`: o pedido referencia o orçamento aprovado, que é imutável. O
  snapshot que importa é `quotation_items.unit_price_cents` e `orders.total_cents`.
- `quotation_events.meta` é `jsonb`.

## 4. Invariantes

No banco:

1. `CHECK (payments.amount_cents > 0)` e `CHECK (payments.method IN (...))`.
2. `UNIQUE (receivable_id, idempotency_key)`.
3. `CHECK (quotation_items.quantity > 0)` e
   `CHECK (total_cents = quantity * unit_price_cents - discount_cents)`.
4. `CHECK (quotations.total_cents = subtotal_cents - discount_cents)`.
5. `CHECK (receivables.amount_cents > 0)` e `SUM(receivables.amount_cents) = orders.total_cents`
   garantido pela transação de criação e por teste.
6. Pedido só nasce de orçamento `approved`: validado no `OrderService` e em teste.

Na aplicação:

- dinheiro só em `int` de centavos; `Money::split` joga o resto na primeira parcela;
- desconto de item não pode ser negativo nem maior que o bruto;
- status de parcela é derivado, com precedência **paid > overdue > partial > open** (Δ: o
  rascunho não definia a precedência de parcial vencida);
- expiração é derivada de `valid_until`; `expired` nunca é gravado;
- decisão do cliente é terminal (409 na segunda);
- mesma chave de pagamento com payload diferente → 409; sobrepagamento → 422;
- operador não arquiva cliente nem altera preço de catálogo (policy).

## 5. Casos de uso

**UC-01 Cliente e catálogo.** CRUD de cliente PF/PJ e serviço (nome, unidade, preço em
centavos, ativo). Inativar não apaga; cliente é soft delete.

**UC-02 Orçamento versionado.** Orçamento com N itens, desconto por item, validade,
parcelamento e primeiro vencimento. Editar cria `version + 1` com `parent_id` apontando para
a raiz; versões anteriores ficam intactas. `draft → sent → approved | rejected`; expirado é
derivado. Orçamento decidido não recebe nova versão.

**UC-03 Aprovação por link.** Enviar gera URL assinada de 30 dias, sem login. A página mostra
documento, parcelamento e botões aprovar/recusar; grava decisão, data/hora, IP e user agent.
O link é reapresentável enquanto indecidido e terminal depois da decisão (Δ: o rascunho dizia
“uso único”, o que contradizia a validade de 30 dias).

**UC-04 Pedido.** Aprovar cria o pedido copiando total e condições, na mesma transação.
Número sequencial por ano (`2026-0001`). `open → fulfilled | cancelled`; cancelar com
recebível pago é bloqueado (409).

**UC-05 Recebíveis.** N parcelas com vencimento mensal a partir da aprovação + `first_due_days`.
Divisão inteira com resto na primeira. Status derivado. Nenhuma coluna de status.

**UC-06 Pagamento.** `POST /payments` autenticado por sessão, `Idempotency-Key` obrigatória
(campo do formulário), método (`pix`, `cash`, `transfer`, `card`), valor e data. Replay
devolve 200 com o mesmo pagamento; conflito 409; sobrepagamento 422; pedido cancelado 422.

**UC-07 Painel.** A receber em 30 dias, vencido, taxa de aprovação do mês e ticket médio;
listas de vencimentos e orçamentos recentes; fluxo previsto por mês. Sem job agendado.

**UC-08 Demo resetável.** `artisan demo:reset` recria o dataset em ~1 s:
3 clientes, 8 serviços (1 inativo), **8 linhas de orçamento em 7 números** (4 aprovados,
1 recusado, 1 expirado, 1 rascunho, 1 enviado pendente, além de uma v2), 4 pedidos e
9 parcelas (2 pagas, 1 parcial, 1 vencida, 5 abertas) — Δ: 4 pedidos exigem 4 orçamentos
aprovados, o que era impossível com “um aprovado” no rascunho.

## 6. Aceite executável

- [x] `ddev start && ddev artisan demo:reset` sobe com dados e login `demo@orca.test` / `password`.
- [x] Pest verde (57 testes): divisão 1000,00 em 3×/7×/11×, snapshot pós-aprovação,
      idempotência (mesma chave 2× e chave repetida com payload diferente → 409),
      sobrepagamento → 422, link expirado → 403, decisão terminal, seed duas vezes.
- [x] Playwright: criar orçamento → link assinado → aprovar → pedido com total igual.
- [x] Playwright: mesmo pagamento duas vezes deixa **uma** linha em `payments`.
- [x] Parcela vencida aparece como `overdue` no painel sem job.
- [x] `demo:reset` em ~1,0 s, rodando duas vezes sem erro.
- [x] `grep -rn "float\|(float)\|double" app/` não retorna cálculo monetário; nenhum
      `decimal` em `db/migrations/`.

## 7. Fora de escopo

NF-e/fiscal, multi-tenant, contabilidade, conciliação bancária, boleto/PIX automático, app
mobile, portal além da página de aprovação, e-mail transacional real (mailer `log`), API
pública.

## 8. Riscos assumidos

- **Escopo virar ERP**: o argumento é o ciclo curto; estoque/comissão não entram.
- **PDF simples**: dompdf com layout de tabela; trocar por Browsershot é trabalho local.
- **Dados do demo**: o seed é a fonte dos números exibidos no case.
