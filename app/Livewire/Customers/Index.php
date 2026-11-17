<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Clientes')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updating(string $property): void
    {
        if ($property === 'search') {
            $this->resetPage();
        }
    }

    public function render()
    {
        Gate::authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->when($this->search !== '', function ($query) {
                $term = '%'.str_replace(['%', '_'], '', $this->search).'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'ilike', $term)
                    ->orWhere('document', 'ilike', $term)
                    ->orWhere('email', 'ilike', $term));
            })
            ->withCount(['quotations', 'orders'])
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.customers.index', ['customers' => $customers]);
    }
}
