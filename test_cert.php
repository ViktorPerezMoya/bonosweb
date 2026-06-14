<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = App\Models\Company::find(1);
tenancy()->initialize($tenant);

$job = new App\Jobs\GenerateEmployeeCertificate(2);
$job->handle(new App\Services\EmployeeCertificateGenerator());
echo "Generado\n";
