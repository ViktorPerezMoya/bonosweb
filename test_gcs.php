<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $result = \Illuminate\Support\Facades\Storage::disk('gcs')->put('test-folder/test-file.txt', 'Hello GCS!');
    echo "Result: " . ($result ? 'Success' : 'Failed (returned false)');
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage();
}
