<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public $email;
    public $password;

    public function login()
    {
        $credentials = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            session()->regenerate();
            return redirect()->route('superadmin.tenants');
        }

        $this->addError('email', 'Las credenciales no coinciden con nuestros registros.');
    }

    public function render()
    {
        return view('livewire.superadmin.login')->layout('components.layouts.superadmin', [
            'title' => 'Login Central - BonosWeb'
        ]);
    }
}
