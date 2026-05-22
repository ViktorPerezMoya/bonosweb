<?php

namespace App\Jobs\Tenancy;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateTenantAdminUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Tenant $tenant;

    /**
     * El JobPipeline de stancl/tenancy pasa el modelo Tenant directamente
     * (no el evento TenantCreated), porque el .send() del pipeline
     * devuelve $event->tenant.
     */
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Crea el usuario administrador inicial dentro de la base de datos del tenant.
     */
    public function handle(): void
    {
        if ($this->tenant->admin_email) {
            $this->tenant->run(function ($tenant) {
                \Illuminate\Support\Facades\DB::table('users')->insert([
                    'name'       => $tenant->admin_name ?? 'Administrador',
                    'email'      => $tenant->admin_email,
                    'password'   => $tenant->admin_password,
                    'role'       => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }
    }
}
