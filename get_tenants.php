<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\Tenant::all() as $tenant) {
    echo $tenant->id . ' -> ' . $tenant->tenancy_db_name . "\n";
}
