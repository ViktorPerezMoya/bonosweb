<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Login extends Component
{
    public $email;
    public $password;
    public $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            // Solo permitir acceso si es HR o Admin
            if(in_array(Auth::user()->role, ['hr', 'admin'])) {
                return redirect()->intended('/dashboard');
            } else {
                Auth::logout();
                session()->invalidate();
                session()->regenerateToken();
                $this->addError('email', 'Acceso denegado. Solo personal de RRHH.');
            }
        } else {
            $this->addError('email', 'Las credenciales proporcionadas no coinciden con nuestros registros.');
        }
    }

    public function render()
    {
        $bgUrl = null;
        if (function_exists('tenant') && $t = tenant()) {
            $bgUrl = $t->loginBackgroundUrl();
        }
        return view('livewire.auth.login', ['bgUrl' => $bgUrl])->layout('components.layouts.app');
    }
}
