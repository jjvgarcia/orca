<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Entrar')]
class Login extends Component
{
    public string $email = 'demo@orca.test';

    public string $password = 'password';

    public bool $remember = false;

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $this->remember)) {
            $this->addError('email', 'E-mail ou senha inválidos.');

            return;
        }

        session()->regenerate();
        $this->redirectIntended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
