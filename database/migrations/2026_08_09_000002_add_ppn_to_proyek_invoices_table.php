<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Menambahkan kolom PPN (opsional) pada tabel proyek_invoices.
     * PPN disimpan sebagai persentase (contoh: 11.00).
     *
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->decimal('ppn', 5, 2)->nullable()->after('dp_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropColumn('ppn');
        });
    }
};
