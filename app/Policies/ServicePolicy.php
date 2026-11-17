<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Service $service): bool
    {
        return true;
    }

    /**
     * Preço de catálogo só muda com papel de administrador: operador não
     * reescreve a tabela de preços que sustenta os orçamentos.
     */
    public function create(User $user): bool
    {
        return $user->role === Role::Admin;
    }

    public function update(User $user, Service $service): bool
    {
        return $user->role === Role::Admin;
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->role === Role::Admin;
    }
}
