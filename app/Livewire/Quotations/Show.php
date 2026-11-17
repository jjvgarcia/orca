<?php

namespace App\Livewire\Quotations;

use App\Domain\Exceptions\DomainRuleViolation;
use App\Models\Quotation;
use App\Services\QuotationService;
use App\Support\Actor;
use App\Support\PublicLink;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Orçamento')]
class Show extends Component
{
    public Quotation $quotation;

    public function mount(Quotation $quotation): void
    {
        Gate::authorize('view', $quotation);
        $this->quotation = $quotation;
    }

    public function send(): void
    {
        Gate::authorize('revise', $this->quotation);

        try {
            app(QuotationService::class)->send($this->quotation, Actor::from(auth()->user()));
        } catch (DomainRuleViolation $violation) {
            $this->addError('send', $violation->getMessage());

            return;
        }

        $this->quotation->refresh();
        session()->flash('status', 'Orçamento enviado. O link público vale 30 dias e a decisão é terminal.');
    }

    public function render()
    {
        $this->quotation->load(['customer', 'items.service', 'order', 'events']);

        $versions = Quotation::query()
            ->where('number', $this->quotation->number)
            ->orderBy('version')
            ->get();

        return view('livewire.quotations.show', [
            'versions' => $versions,
            'publicLink' => PublicLink::to($this->quotation),
            'pdfLink' => PublicLink::pdf($this->quotation),
            'status' => $this->quotation->effectiveStatus(),
        ])->title($this->quotation->code());
    }
}
