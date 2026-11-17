<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
class Form extends Component
{
    public ?Customer $customer = null;

    public string $name = '';

    public string $kind = 'pj';

    public ?string $document = null;

    public ?string $email = null;

    public ?string $phone = null;

    public function mount(?Customer $customer = null): void
    {
        if ($customer !== null && $customer->exists) {
            Gate::authorize('view', $customer);
            $this->customer = $customer;
            $this->name = $customer->name;
            $this->kind = $customer->kind;
            $this->document = $customer->document;
            $this->email = $customer->email;
            $this->phone = $customer->phone;

            return;
        }

        Gate::authorize('create', Customer::class);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'kind' => ['required', 'in:pf,pj'],
            'document' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Informe o nome do cliente.',
            'kind.in' => 'Escolha pessoa física ou jurídica.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->customer?->exists) {
            Gate::authorize('update', $this->customer);
            $this->customer->update($data);
            $message = 'Cliente atualizado.';
        } else {
            Gate::authorize('create', Customer::class);
            $this->customer = Customer::create($data);
            $message = 'Cliente cadastrado.';
        }

        session()->flash('status', $message);
        $this->redirect(route('customers.index'));
    }

    public function delete(): void
    {
        Gate::authorize('delete', $this->customer);

        $this->customer->delete();

        session()->flash('status', 'Cliente arquivado. O histórico de orçamentos e pedidos permanece.');
        $this->redirect(route('customers.index'));
    }

    public function render()
    {
        $title = $this->customer?->exists ? 'Editar cliente' : 'Novo cliente';

        return view('livewire.customers.form', [
            'title' => $title,
            'quotations' => $this->customer?->exists
                ? $this->customer->quotations()->withCount('items')->orderByDesc('id')->limit(5)->get()
                : collect(),
        ])->title($title);
    }
}
