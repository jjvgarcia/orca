<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Orçamento {{ $quotation->code() }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #16130d; margin: 0; }
        .header { border-bottom: 2px solid #16130d; padding-bottom: 12px; }
        .brand { font-family: DejaVu Serif, serif; font-size: 22px; margin: 0; }
        .tagline { font-size: 8px; letter-spacing: 2px; text-transform: uppercase; color: #7b7264; margin: 2px 0 0; }
        .meta { width: 100%; margin-top: 14px; }
        .meta td { vertical-align: top; padding: 0; }
        .eyebrow { font-size: 8px; letter-spacing: 1.6px; text-transform: uppercase; color: #7b7264; margin: 0 0 3px; }
        .title { font-family: DejaVu Serif, serif; font-size: 24px; margin: 18px 0 2px; }
        .muted { color: #7b7264; }
        .box { width: 100%; margin-top: 16px; border-collapse: collapse; }
        .box th { text-align: left; font-size: 8px; letter-spacing: 1.4px; text-transform: uppercase; color: #7b7264; border-bottom: 1px solid #ddd4c2; padding: 6px 4px; }
        .box td { border-bottom: 1px solid #ebe5d8; padding: 8px 4px; vertical-align: top; }
        .right { text-align: right; }
        .total { font-family: DejaVu Serif, serif; font-size: 22px; }
        .totals { width: 45%; margin-left: 55%; margin-top: 12px; border-collapse: collapse; }
        .totals td { padding: 4px 0; }
        .totals .rule td { border-top: 1px solid #16130d; padding-top: 8px; }
        .pill { display: inline-block; border: 1px solid #ddd4c2; border-radius: 10px; padding: 2px 8px; font-size: 9px; color: #443f35; }
        .approved { color: #1d4f3c; border-color: #1d4f3c; }
        .rejected { color: #9e3129; border-color: #9e3129; }
        .footer { margin-top: 22px; border-top: 1px solid #ddd4c2; padding-top: 8px; font-size: 8px; color: #7b7264; }
        .section { margin-top: 18px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.2px; margin: 0 0 6px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="meta">
            <tr>
                <td>
                    <p class="brand">Orça</p>
                    <p class="tagline">orçamento → recebível</p>
                </td>
                <td class="right">
                    <p class="eyebrow">Proposta comercial</p>
                    <p class="pill {{ $quotation->effectiveStatus() === \App\Enums\QuotationStatus::Approved ? 'approved' : ($quotation->effectiveStatus() === \App\Enums\QuotationStatus::Rejected ? 'rejected' : '') }}">
                        {{ $quotation->effectiveStatus()->label() }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <h1 class="title">Orçamento {{ $quotation->code() }}</h1>
    <p class="muted">
        Emitido em {{ $quotation->created_at->translatedFormat('d/m/Y') }} ·
        válido até {{ $quotation->valid_until->translatedFormat('d/m/Y') }}
    </p>

    <table class="meta" style="margin-top: 14px;">
        <tr>
            <td>
                <p class="eyebrow">Cliente</p>
                <p style="margin: 0 0 2px; font-size: 12px;">{{ $quotation->customer->name }}</p>
                <p class="muted" style="margin: 0;">
                    {{ $quotation->customer->kindLabel() }}
                    @if ($quotation->customer->document) · {{ $quotation->customer->document }} @endif
                </p>
                <p class="muted" style="margin: 2px 0 0;">
                    {{ $quotation->customer->email }} {{ $quotation->customer->phone ? ' · '.$quotation->customer->phone : '' }}
                </p>
            </td>
            <td class="right">
                <p class="eyebrow">Condições</p>
                <p style="margin: 0;">{{ $quotation->installments }} parcelas</p>
                <p class="muted" style="margin: 2px 0 0;">1º vencimento em {{ $quotation->first_due_days }} dias após aprovação</p>
            </td>
        </tr>
    </table>

    <table class="box">
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Qtd.</th>
                <th class="right">Unitário</th>
                <th class="right">Desconto</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ $item->unitPrice()->format() }}</td>
                    <td class="right">{{ $item->discount_cents > 0 ? $item->discount()->format() : '—' }}</td>
                    <td class="right">{{ $item->total()->format() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="right">{{ $quotation->subtotal()->format() }}</td></tr>
        <tr><td class="muted">Descontos</td><td class="right">{{ $quotation->discount()->format() }}</td></tr>
        <tr class="rule"><td><strong>Total</strong></td><td class="right total">{{ $quotation->total()->format() }}</td></tr>
    </table>

    @php
        $order = $quotation->order;
        $preview = $order === null
            ? \App\Domain\Money::fromCents($quotation->total_cents)->split($quotation->installments)
            : collect();
        $firstDue = today()->addDays($quotation->first_due_days);
    @endphp

    <div class="section">
        <p class="section-title">{{ $order === null ? 'Parcelamento previsto' : 'Cronograma do pedido '.$order->number }}</p>
        <table class="box">
            <thead><tr><th>Parcela</th><th>Vencimento</th><th class="right">Valor</th></tr></thead>
            <tbody>
                @if ($order !== null)
                    @foreach ($order->receivables as $receivable)
                        <tr>
                            <td>{{ $receivable->sequence }}ª</td>
                            <td>{{ $receivable->due_date->translatedFormat('d/m/Y') }}</td>
                            <td class="right">{{ $receivable->amount()->format() }}</td>
                        </tr>
                    @endforeach
                @else
                    @foreach ($preview as $index => $part)
                        <tr>
                            <td>{{ $index + 1 }}ª</td>
                            <td>{{ $firstDue->copy()->addMonthsNoOverflow($index)->translatedFormat('d/m/Y') }}</td>
                            <td class="right">{{ $part->format() }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>

    @if ($quotation->decided_at)
        <div class="section">
            <p class="section-title">Decisão do cliente</p>
            <p style="margin: 0;">
                {{ $quotation->status->label() }} em {{ $quotation->decided_at->translatedFormat('d/m/Y \à\s H:i') }}
                @if ($quotation->decided_ip) · IP {{ $quotation->decided_ip }} @endif
            </p>
        </div>
    @endif

    <div class="footer">
        Documento gerado por Orça. A proposta é válida até {{ $quotation->valid_until->translatedFormat('d/m/Y') }}; o link de aprovação expira em 30 dias.
    </div>
</body>
</html>
