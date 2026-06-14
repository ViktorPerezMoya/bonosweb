<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\EmployeeProfile;
use App\Services\EmployeeCertificateGenerator;
use Illuminate\Support\Facades\Storage;

class FixEmployeeCert extends Command
{
    protected $signature = 'bonosweb:fix-employee-cert';
    protected $description = 'Regenerate the employee certificate for employee 2 in tenant 1';

    public function handle(EmployeeCertificateGenerator $generator)
    {
        $tenant = \App\Models\Tenant::first();
        if (!$tenant) {
            $this->error('Tenant not found');
            return;
        }

        tenancy()->initialize($tenant);
        
        $profile = EmployeeProfile::withoutGlobalScope(\App\Models\Scopes\CurrentCompanyScope::class)->find(2);
        if (!$profile) {
            $this->error('Employee profile 2 not found');
            return;
        }

        if ($profile->certificate_path) {
            Storage::disk('local')->delete($profile->certificate_path);
        }

        $company = \App\Models\Company::withoutGlobalScope(\App\Models\Scopes\CurrentCompanyScope::class)->find($profile->company_id);
        $user = $profile->user;

        $certData = $generator->generate(
            $user->name,
            $profile->cuil ?: ($profile->document_number ?: 'SIN_CUIL'),
            $company->name,
            $profile->id
        );

        $profile->update([
            'certificate_path'       => $certData['pfx_path'],
            'certificate_password'   => $certData['pfx_password'],
            'certificate_expires_at' => $certData['expires_at'],
        ]);

        $this->info('Certificate regenerated successfully!');
    }
}
