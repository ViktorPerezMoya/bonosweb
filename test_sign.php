<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $p = App\Models\Payslip::find(286);
    $c = new App\Http\Controllers\PayslipController();
    $c->signCryptographically($p, 'Conforme');
    echo "EXITO\n";
} catch (\Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
