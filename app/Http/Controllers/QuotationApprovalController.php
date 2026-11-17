<?php

namespace App\Http\Controllers;

use App\Domain\Exceptions\DomainRuleViolation;
use App\Domain\Money;
use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Services\QuotationService;
use App\Support\PublicLink;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class QuotationApprovalController extends Controller
{
    public function show(Quotation $quotation): View
    {
        $quotation->load(['customer', 'items.service', 'order.receivables', 'events']);

        if ($quotation->order !== null) {
            $schedule = $quotation->order->receivables->map(fn ($receivable) => [
                'sequence' => $receivable->sequence,
                'due_date' => $receivable->due_date,
                'amount_cents' => $receivable->amount_cents,
            ]);
            $scheduleIsProjection = false;
        } else {
            $parts = Money::fromCents($quotation->total_cents)->split($quotation->installments);
            $firstDue = today()->addDays($quotation->first_due_days);

            $schedule = collect($parts)->map(fn (Money $part, int $index) => [
                'sequence' => $index + 1,
                'due_date' => $firstDue->copy()->addMonthsNoOverflow($index),
                'amount_cents' => $part->cents,
            ]);
            $scheduleIsProjection = true;
        }

        return view('public.quotation', [
            'quotation' => $quotation,
            'status' => $quotation->effectiveStatus(),
            'schedule' => $schedule,
            'scheduleIsProjection' => $scheduleIsProjection,
            'decisionUrl' => PublicLink::decision($quotation),
            'pdfUrl' => PublicLink::pdf($quotation),
            'canDecide' => $quotation->effectiveStatus() === QuotationStatus::Sent,
        ]);
    }

    public function decide(Request $request, Quotation $quotation, QuotationService $quotations): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
        ]);

        try {
            $quotations->decide(
                $quotation,
                $data['decision'] === 'approve',
                $request->ip(),
                $request->userAgent(),
            );
        } catch (DomainRuleViolation $violation) {
            $message = $quotation->fresh()?->effectiveStatus()->isTerminal()
                ? 'Este orçamento já foi decidido.'
                : $violation->getMessage();

            return redirect()->to(PublicLink::to($quotation))->with('error', $message);
        }

        return redirect()
            ->to(PublicLink::to($quotation))
            ->with('status', $data['decision'] === 'approve'
                ? 'Orçamento aprovado. O pedido e as parcelas já foram criados.'
                : 'Orçamento recusado. Nenhum pedido foi gerado.');
    }

    public function pdf(Quotation $quotation): Response
    {
        $quotation->load(['customer', 'items.service', 'order.receivables']);

        return Pdf::loadView('pdf.quotation', ['quotation' => $quotation])
            ->setPaper('a4')
            ->download('orcamento-'.$quotation->number.'-v'.$quotation->version.'.pdf');
    }
}
