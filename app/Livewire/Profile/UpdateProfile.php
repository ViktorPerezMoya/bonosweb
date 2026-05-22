<?php

namespace App\Livewire\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UpdateProfile extends Component
{
    public string $email = '';
    
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount()
    {
        $this->email = Auth::user()->email;
    }

    public function updateEmail()
    {
        $user = Auth::user();

        $this->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->forceFill([
            'email' => $this->email,
        ])->save();

        session()->flash('status', 'email-updated');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('status', 'password-updated');
    }

    public function render()
    {
        return view('livewire.profile.update-profile')
            ->layout('components.layouts.app', [
                'header' => 'Mi Perfil',
                'title' => 'Mi Perfil - BonosWeb'
            ]);
    }
}
