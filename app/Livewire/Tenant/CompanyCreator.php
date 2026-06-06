<?php

namespace App\Livewire\Tenant;

use App\Models\Company;
use App\Models\Scopes\CurrentCompanyScope;
use App\Services\CompanyCertificateGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Componente para crear nuevas empresas/sub-empresas dentro de un Tenant.
 *
 * Acceso: exclusivo para usuarios con rol 'admin'.
 * Al guardar: crea el registro en companies, genera el certificado .pfx con
 * CompanyCertificateGenerator y actualiza el registro en una sola transacción.
 */
class CompanyCreator extends Component
{
    public string $name = '';
    public string $cuit = '';

    // ── Ciclo de vida ─────────────────────────────────────────────────────────

    public function mount(): void
    {
        // Solo administradores pueden gestionar empresas.
        // Se usa abort_if siguiendo el patrón del resto de componentes del proyecto.
        abort_if(
            auth()->user()->role !== 'admin',
            403,
            'Solo los administradores pueden gestionar empresas.'
        );
    }

    // ── Validación ────────────────────────────────────────────────────────────

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cuit' => [
                'required',
                'string',
                // CUIT argentino: prefijo válido (20/23/24/27/30/33/34) + 9 dígitos
                'regex:/^(20|23|24|27|30|33|34)\d{9}$/',
                // Unicidad dentro de la BD del tenant (conexión ya apunta al tenant)
                'unique:companies,cuit',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'La razón social es obligatoria.',
            'name.max'      => 'La razón social no puede superar los 255 caracteres.',
            'cuit.required' => 'El CUIT es obligatorio.',
            'cuit.regex'    => 'El CUIT debe tener 11 dígitos válidos (prefijo 20/23/24/27/30/33/34 seguido de 9 dígitos, sin guiones).',
            'cuit.unique'   => 'Este CUIT ya está registrado en una de las empresas de tu tenant.',
        ];
    }

    // ── Acciones ──────────────────────────────────────────────────────────────

    /**
     * Guarda la empresa y genera su certificado digital en una transacción atómica.
     *
     * Pasos:
     *  1. Valida el formulario.
     *  2. Abre una transacción de BD.
     *  3. Crea el registro en `companies` sin certificado aún.
     *  4. Invoca CompanyCertificateGenerator con el ID real de la empresa.
     *  5. Actualiza el registro con pfx_path, pfx_password y expires_at.
     *  6. Confirma la transacción.
     */
    public function save(): void
    {
        $this->validate();

        // Normalizar CUIT: eliminar cualquier guión o espacio que el usuario haya ingresado
        $normalizedCuit = preg_replace('/\D/', '', $this->cuit);

        DB::beginTransaction();

        try {
            // ── 3. Crear empresa (sin certificado aún) ────────────────────────
            $company = Company::withoutGlobalScope(CurrentCompanyScope::class)->create([
                'name'    => trim($this->name),
                'cuit'    => $normalizedCuit,
                'is_main' => false,
            ]);

            // ── 4. Generar certificado .pfx (operación OpenSSL ~0.5-1s) ───────
            /** @var CompanyCertificateGenerator $certGenerator */
            $certGenerator = app(CompanyCertificateGenerator::class);
            $certData      = $certGenerator->generate($company->name, $company->cuit, $company->id);

            // ── 5. Persistir rutas y contraseña cifrada ───────────────────────
            $company->update([
                'signature_pfx_path'       => $certData['pfx_path'],
                'signature_pfx_password'   => $certData['pfx_password'],
                'signature_pfx_expires_at' => $certData['expires_at'],
            ]);

            DB::commit();

            session()->flash(
                'message',
                "Empresa «{$company->name}» creada. Certificado digital activo hasta {$certData['expires_at']}."
            );

            // Notificar a otros componentes en la página (ej: CompanySwitcher)
            $this->dispatch('company-created');

            // Resetear formulario
            $this->reset('name', 'cuit');
            $this->resetErrorBag();

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('[CompanyCreator] Error al crear empresa.', [
                'name'  => $this->name,
                'cuit'  => $normalizedCuit,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'No se pudo crear la empresa: ' . $e->getMessage());
        }
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): \Illuminate\Contracts\View\View
    {
        // Lista de empresas existentes en el tenant (sin scope de empresa activa)
        $companies = Company::withoutGlobalScope(CurrentCompanyScope::class)
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        return view('livewire.tenant.company-creator', compact('companies'))
            ->layout('components.layouts.app');
    }
}
