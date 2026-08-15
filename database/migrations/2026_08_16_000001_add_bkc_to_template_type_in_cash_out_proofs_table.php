<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Menambahkan opsi template BUKTI CEK/GIRO KELUAR (bkc).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE cash_out_proofs MODIFY template_type ENUM('standard', 'hollow', 'bkc') NOT NULL DEFAULT 'standard'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE cash_out_proofs MODIFY template_type ENUM('standard', 'hollow') NOT NULL DEFAULT 'standard'");
    }
};