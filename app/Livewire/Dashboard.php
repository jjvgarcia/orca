<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Painel')]
class Dashboard extends Component
{
    public function render()
    {
        $receivables = Receivable::query()
            ->with('order.customer')
            ->withSum('payments as paid_cents', 'amount_cents')
            ->whereHas('order', fn (Builder $query) => $query->where('status', '!=', OrderStatus::Cancelled->value))
            ->get();

        $upcoming = $receivables
            ->filter(fn (Receivable $receivable) => $receivable->outstandingCents() > 0
                && $receivable->due_date->greaterThanOrEqualTo(today())
                && $receivable->due_date->lessThanOrEqualTo(today()->addDays(30)))
            ->sortBy('due_date');

        $overdue = $receivables
            ->filter(fn (Receivable $receivable) => $receivable->outstandingCents() > 0
                && $receivable->due_date->isBefore(today()))
            ->sortBy('due_date');

        $approvedThisMonth = Quotation::query()
            ->whereBetween('decided_at', [today()->startOfMonth(), today()->endOfMonth()])
            ->where('status', QuotationStatus::Approved)
            ->count();

        $decidedThisMonth = Quotation::query()
            ->whereBetween('decided_at', [today()->startOfMonth(), today()->endOfMonth()])
            ->whereIn('status', [QuotationStatus::Approved, QuotationStatus::Rejected])
            ->count();

        $orderCount = Order::query()->where('status', '!=', OrderStatus::Cancelled)->count();
        $orderTotal = (int) Order::query()->where('status', '!=', OrderStatus::Cancelled)->sum('total_cents');
        $averageTicket = $orderCount > 0 ? intdiv($orderTotal, $orderCount) : 0;

        $months = collect(range(0, 5))->map(function (int $offset) use ($receivables): array {
            $start = today()->startOfMonth()->addMonthsNoOverflow($offset);
            $end = $start->copy()->endOfMonth();

            $cents = $receivables
                ->filter(fn (Receivable $receivable) => $receivable->outstandingCents() > 0
                    && $receivable->due_date->betweenIncluded($start, $end))
                ->sum(fn (Receivable $receivable) => $receivable->outstandingCents());

            return [
                'label' => $start->translatedFormat('M'),
                'full' => $start->translatedFormat('F Y'),
                'cents' => (int) $cents,
            ];
        });

        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        return view('livewire.dashboard', [
            'greeting' => $greeting,
            'upcoming' => $upcoming->take(6),
            'upcomingCents' => (int) $upcoming->sum(fn (Receivable $receivable) => $receivable->outstandingCents()),
            'upcomingCount' => $upcoming->count(),
            'overdue' => $overdue->take(4),
            'overdueCents' => (int) $overdue->sum(fn (Receivable $receivable) => $receivable->outstandingCents()),
            'overdueCount' => $overdue->count(),
            'approvalRate' => $decidedThisMonth > 0 ? intdiv($approvedThisMonth * 100, $decidedThisMonth) : null,
            'approvedThisMonth' => $approvedThisMonth,
            'decidedThisMonth' => $decidedThisMonth,
            'averageTicket' => $averageTicket,
            'orderCount' => $orderCount,
            'months' => $months,
            'chartMax' => max(1, (int) $months->max('cents')),
            'recentQuotations' => Quotation::query()
                ->with('customer')
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(),
            'openOrders' => Order::query()
                ->withCount('receivables')
                ->where('status', OrderStatus::Open)
                ->count(),
        ]);
    }
}
