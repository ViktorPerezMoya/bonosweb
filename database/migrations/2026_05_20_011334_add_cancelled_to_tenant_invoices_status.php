<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tenant_invoices MODIFY COLUMN status ENUM('pending', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tenant_invoices MODIFY COLUMN status ENUM('pending', 'paid', 'overdue') NOT NULL DEFAULT 'pending'");
    }
};
