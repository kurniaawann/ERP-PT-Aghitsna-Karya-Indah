<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Tabel ini menyimpan data reimbursement yang diajukan oleh admin
     * dan disetujui oleh super admin
     */
    public function up(): void
    {
        Schema::create('reimburses', function (Blueprint $table) {
            // Primary key dengan format custom (RMB001, RMB002, dst)
            $table->string('reimburse_code', 50)->primary();

            // Tanggal pengajuan reimburse
            $table->date('date');

            // Nama proyek terkait reimburse
            $table->string('project_name');

            // Deskripsi detail pengeluaran yang di-reimburse
            $table->text('expense_description');

            // Total amount reimburse
            $table->integer('total_amount');

            // Tanggal jatuh tempo pencairan/pembayaran
            $table->date('due_date');

            // Status reimburse: draft, approved, rejected
            // draft = masih dalam pengajuan
            // approved = disetujui super admin
            // rejected = ditolak super admin
            $table->enum('status', ['draft', 'approved', 'rejected'])->default('draft');

            // Keterangan/notes tambahan (misal: "Sudah ditransfer", "Menunggu verifikasi", dll)
            $table->text('notes')->nullable();

            // User ID yang mengajukan reimburse (admin)
            $table->foreignUuid('submitted_by')->nullable()->constrained('users')->onDelete('set null');

            // User ID yang menyetujui/menolak (super admin)
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');

            // Tanggal approval/rejection
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimburses');
    }
};
