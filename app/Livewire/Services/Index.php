<?php

namespace App\Livewire\Services;

use App\Domain\Money;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Serviços')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $unit = 'unidade';

    public string $price = '';

    public bool $active = true;

    public function mount(): void
    {
        Gate::authorize('viewAny', Service::class);
    }

    public function startCreate(): void
    {
        Gate::authorize('create', Service::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);
        Gate::authorize('update', $service);

        $this->editingId = $service->id;
        $this->name = $service->name;
        $this->unit = $service->unit;
        $this->price = $service->price()->toDecimalString();
        $this->active = $service->active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'unit' => ['required', 'string', 'max:24'],
            'price' => ['required', 'string', 'max:20'],
            'active' => ['boolean'],
        ]);

        try {
            $money = Money::fromDecimalString($data['price']);
        } catch (\InvalidArgumentException) {
            $this->addError('price', 'Informe um valor válido, como 1.200,00.');

            return;
        }

        if ($money->isNegative()) {
            $this->addError('price', 'O preço não pode ser negativo.');

            return;
        }

        $payload = [
            'name' => $data['name'],
            'unit' => $data['unit'],
            'unit_price_cents' => $money->cents,
            'active' => $this->active,
        ];

        if ($this->editingId !== null) {
            $service = Service::findOrFail($this->editingId);
            Gate::authorize('update', $service);
            $service->update($payload);
        } else {
            Gate::authorize('create', Service::class);
            Service::create($payload);
        }

        session()->flash('status', $this->editingId !== null ? 'Serviço atualizado.' : 'Serviço adicionado ao catálogo.');
        $this->cancel();
    }

    public function toggle(int $serviceId): void
    {
        $service = Service::findOrFail($serviceId);
        Gate::authorize('update', $service);

        $service->update(['active' => ! $service->active]);
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'price', 'active', 'unit');
        $this->unit = 'unidade';
        $this->active = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.services.index', [
            'services' => Service::query()
                ->withCount('quotationItems')
                ->orderByDesc('active')
                ->orderBy('name')
                ->get(),
            'canManage' => auth()->user()->can('create', Service::class),
        ]);
    }
}
