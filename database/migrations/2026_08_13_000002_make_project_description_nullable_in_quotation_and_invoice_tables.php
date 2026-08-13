<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE proyek_invoices MODIFY project_description TEXT NULL');
        DB::statement('ALTER TABLE barang_invoices MODIFY project_description TEXT NULL');
        DB::statement('ALTER TABLE alumunium_invoices MODIFY project_description TEXT NULL');
        DB::statement('ALTER TABLE project_quotations MODIFY project_description VARCHAR(255) NULL');
        DB::statement('ALTER TABLE aluminium_quotations MODIFY project_description VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE proyek_invoices MODIFY project_description TEXT NOT NULL');
        DB::statement('ALTER TABLE barang_invoices MODIFY project_description TEXT NOT NULL');
        DB::statement('ALTER TABLE alumunium_invoices MODIFY project_description TEXT NOT NULL');
        DB::statement("ALTER TABLE project_quotations MODIFY project_description VARCHAR(255) NOT NULL DEFAULT 'Ditempat'");
        DB::statement("ALTER TABLE aluminium_quotations MODIFY project_description VARCHAR(255) NOT NULL DEFAULT 'Ditempat'");
    }
};
