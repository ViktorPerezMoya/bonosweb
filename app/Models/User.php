<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Todos los perfiles del empleado (uno por empresa).
     */
    public function employeeProfiles()
    {
        return $this->hasMany(EmployeeProfile::class);
    }

    /**
     * Perfil principal (empresa principal del tenant).
     */
    public function employeeProfile()
    {
        return $this->hasOne(EmployeeProfile::class)
            ->whereHas('company', fn ($q) => $q->where('is_main', true));
    }

    /**
     * Perfil del empleado en la empresa activa del contexto.
     * No restringe por is_main; el CurrentCompanyScope de EmployeeProfile
     * se encarga de filtrar por la empresa en sesión.
     */
    public function currentCompanyProfile()
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    /**
     * Empresas a las que el usuario tiene acceso directo como empleado.
     * Usa employee_profiles como tabla pivote (user_id → company_id).
     * Solo es relevante para el rol 'employee'; admin y rrhh usan Company::all().
     */
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'employee_profiles', 'user_id', 'company_id');
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'employee_id');
    }
}
