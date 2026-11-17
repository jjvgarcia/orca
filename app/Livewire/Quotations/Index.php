<?php

namespace App\Livewire\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Orçamentos')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $status = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function updating(string $property): void
    {
        if (in_array($property, ['status', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        Gate::authorize('viewAny', Quotation::class);

        $counts = [
            '' => Quotation::count(),
            'draft' => Quotation::where('status', QuotationStatus::Draft)->count(),
            'sent' => Quotation::where('status', QuotationStatus::Sent)->where('valid_until', '>=', today())->count(),
            'approved' => Quotation::where('status', QuotationStatus::Approved)->count(),
            'rejected' => Quotation::where('status', QuotationStatus::Rejected)->count(),
            'expired' => Quotation::where('status', QuotationStatus::Sent)->where('valid_until', '<', today())->count(),
        ];

        $quotations = Quotation::query()
            ->with('customer')
            ->withCount('items')
            ->when($this->status === 'expired', fn ($query) => $query
                ->where('status', QuotationStatus::Sent)
                ->where('valid_until', '<', today()))
            ->when($this->status === 'sent', fn ($query) => $query
                ->where('status', QuotationStatus::Sent)
                ->where('valid_until', '>=', today()))
            ->when(in_array($this->status, ['draft', 'approved', 'rejected'], true), fn ($query) => $query
                ->where('status', $this->status))
            ->when($this->search !== '', function ($query) {
                $term = '%'.str_replace(['%', '_'], '', $this->search).'%';
                $query->where(fn ($query) => $query
                    ->where('number', 'ilike', $term)
                    ->orWhereHas('customer', fn ($query) => $query->where('name', 'ilike', $term)));
            })
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('livewire.quotations.index', [
            'quotations' => $quotations,
            'counts' => $counts,
            'filters' => [
                '' => 'Todos',
                'draft' => 'Rascunhos',
                'sent' => 'Enviados',
                'approved' => 'Aprovados',
                'rejected' => 'Recusados',
                'expired' => 'Expirados',
            ],
        ]);
    }
}
