<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // alumunium_invoices — 1 index
        // ═══════════════════════════════════════════════════════════════
        // idx_alu_inv_invoice_date
        // Digunakan oleh: AlumuniumInvoiceService, RecapAlumuniumService
        //   → whereMonth('invoice_date'), whereYear('invoice_date'),
        //     orderByDesc('invoice_date')
        // Alasan: invoice_date adalah filter utama di 2 service sekaligus.
        // Setiap request index & export memanggil filter bulan + tahun.
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->index('invoice_date', 'idx_alu_inv_invoice_date');
        });

        // ═══════════════════════════════════════════════════════════════
        // sales_recaps — 2 index
        // ═══════════════════════════════════════════════════════════════
        // idx_sales_recaps_date
        // Digunakan oleh: RecapSalesService → whereMonth/Year('date'),
        //   orderBy('date'), orderBy('created_at')
        // Alasan: Date adalah filter utama di halaman Rekap Penjualan.
        //
        // idx_sales_recaps_status
        // Digunakan oleh: RecapSalesService → filter 'Belum Lunas'/'Lunas'
        //   di dropdown & summary stats.
        // Alasan: Status adalah filter kedua yang paling sering dipakai.
        Schema::table('sales_recaps', function (Blueprint $table) {
            $table->index('date', 'idx_sales_recaps_date');
            $table->index('status', 'idx_sales_recaps_status');
        });

        // ═══════════════════════════════════════════════════════════════
        // proyek_invoices — 2 index
        // ═══════════════════════════════════════════════════════════════
        // idx_proyek_inv_invoice_date
        // Digunakan oleh: ProyekInvoiceService, RecapProyekService
        //   → whereMonth/Year('invoice_date'), orderByDesc('invoice_date')
        // Alasan: invoice_date adalah filter utama di 2 service.
        //
        // idx_proyek_inv_created_by
        // Digunakan oleh: ProyekInvoiceService, RecapProyekService
        //   → WHERE created_by = auth()->id()
        // Alasan: Setiap user hanya melihat invoice miliknya sendiri.
        // Tanpa index, query harus scan seluruh tabel proyek_invoices.
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->index('invoice_date', 'idx_proyek_inv_invoice_date');
            $table->index('created_by', 'idx_proyek_inv_created_by');
        });

        // ═══════════════════════════════════════════════════════════════
        // reimburses — 2 index
        // ═══════════════════════════════════════════════════════════════
        // idx_reimburses_date
        // Digunakan oleh: ReimburseService → whereMonth/Year('date'),
        //   latest('date')
        // Alasan: Date adalah filter utama di halaman Reimburse.
        //
        // idx_reimburses_status
        // Digunakan oleh: ReimburseService → where('status', $status)
        //   untuk filter draft/approved/rejected.
        // Alasan: Status adalah filter kedua yang paling sering dipakai.
        Schema::table('reimburses', function (Blueprint $table) {
            $table->index('date', 'idx_reimburses_date');
            $table->index('status', 'idx_reimburses_status');
        });

        // ═══════════════════════════════════════════════════════════════
        // cash_out_proofs — 2 index
        // ═══════════════════════════════════════════════════════════════
        // idx_cash_out_date
        // Digunakan oleh: CashOutProofService → whereMonth/Year('date')
        // Alasan: Filter bulan/tahun di halaman BKK.
        //
        // idx_cash_out_created_by
        // Digunakan oleh: CashOutProofService → WHERE created_by = auth()->id()
        // Alasan: Scoping per user.
        Schema::table('cash_out_proofs', function (Blueprint $table) {
            $table->index('date', 'idx_cash_out_date');
            $table->index('created_by', 'idx_cash_out_created_by');
        });

        // ═══════════════════════════════════════════════════════════════
        // payment_proofs — 2 index
        // ═══════════════════════════════════════════════════════════════
        // idx_payment_proofs_module_type
        // Digunakan oleh: PaymentProofService → WHERE module_type = ...,
        //   buildProofStageMap() → GROUP BY module_type, invoice_type,
        //   invoice_number.
        // Alasan: module_type adalah filter pertama di setiap query
        //   payment proof. Value rendah (few distinct values), tapi
        //   dikombinasikan dengan composite index invoice sudah ada.
        //
        // idx_payment_proofs_created_at
        // Digunakan oleh: controller index → latest() (orderBy created_at desc)
        // Alasan: Sort default di halaman Bukti Pembayaran.
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->index('module_type', 'idx_payment_proofs_module_type');
            $table->index('created_at', 'idx_payment_proofs_created_at');
        });

        // ═══════════════════════════════════════════════════════════════
        // purchase_invoices — 1 index
        // ═══════════════════════════════════════════════════════════════
        // idx_purchase_inv_item_name
        // Digunakan oleh: PurchaseInvoiceService → scopeSearch()
        //   LIKE %item_name%
        // Alasan: item_name adalah salah satu kolom pencarian utama.
        //   Sudah ada index pada material_name, tapi item_name belum.
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->index('item_name', 'idx_purchase_inv_item_name');
        });

        // ═══════════════════════════════════════════════════════════════
        // notas_administrasi — 1 index
        // ═══════════════════════════════════════════════════════════════
        // idx_nota_date
        // Digunakan oleh: NotaService → whereMonth/Year('nota_date')
        //   saat filter bulan/tahun ditambahkan.
        // Alasan: nota_date akan jadi filter utama saat fitur filter
        //   bulan/tahun diaktifkan di halaman Nota.
        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->index('nota_date', 'idx_nota_date');
        });

        // ═══════════════════════════════════════════════════════════════
        // barang_invoices — 1 index
        // ═══════════════════════════════════════════════════════════════
        // idx_barang_inv_invoice_date
        // Digunakan oleh: ProductInvoiceService → whereMonth/Year('invoice_date'),
        //   orderByDesc('invoice_date')
        // Alasan: invoice_date adalah filter utama di halaman Invoice Barang.
        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->index('invoice_date', 'idx_barang_inv_invoice_date');
        });
    }

    public function down(): void
    {
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_alu_inv_invoice_date');
        });

        Schema::table('sales_recaps', function (Blueprint $table) {
            $table->dropIndex('idx_sales_recaps_date');
            $table->dropIndex('idx_sales_recaps_status');
        });

        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_proyek_inv_invoice_date');
            $table->dropIndex('idx_proyek_inv_created_by');
        });

        Schema::table('reimburses', function (Blueprint $table) {
            $table->dropIndex('idx_reimburses_date');
            $table->dropIndex('idx_reimburses_status');
        });

        Schema::table('cash_out_proofs', function (Blueprint $table) {
            $table->dropIndex('idx_cash_out_date');
            $table->dropIndex('idx_cash_out_created_by');
        });

        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropIndex('idx_payment_proofs_module_type');
            $table->dropIndex('idx_payment_proofs_created_at');
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_inv_item_name');
        });

        Schema::table('notas_administrasi', function (Blueprint $table) {
            $table->dropIndex('idx_nota_date');
        });

        Schema::table('barang_invoices', function (Blueprint $table) {
            $table->dropIndex('idx_barang_inv_invoice_date');
        });
    }
};
