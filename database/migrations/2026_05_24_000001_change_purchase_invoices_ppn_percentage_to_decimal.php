<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE purchase_invoices MODIFY ppn_percentage DECIMAL(5,2) NOT NULL DEFAULT 10.00');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE purchase_invoices MODIFY ppn_percentage INT NOT NULL DEFAULT 10');
    }
};