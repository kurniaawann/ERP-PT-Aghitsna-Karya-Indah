<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * 1. project_quotations: tambah kolom items (JSON, format flat seperti
     *    Invoice Proyek) dan discount agar form penawaran identik dengan
     *    Invoice Proyek. Catatan: TIDAK ada DP pada penawaran — DP adalah
     *    konsep pembayaran invoice, bukan penawaran.
     * 2. proyek_invoices: tambah kolom quotation_number (nullable) sebagai
     *    penghubung ke penawaran yang membuat invoice secara otomatis.
     * 3. Backfill: konversi data lama (project_quotation_items) menjadi
     *    items JSON berformat {keterangan, volume, satuan, harga}.
     */
    public function up(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
            $table->json('items')->nullable()->after('total_amount');
            $table->string('discount_type')->nullable()->after('items');
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
            $table->integer('total_after_discount')->nullable()->after('discount_value');
        });

        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->string('quotation_number')->nullable()->after('invoice_number');
            $table->index('quotation_number');
        });

        $this->backfillItemsFromLegacyTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropIndex(['quotation_number']);
            $table->dropColumn('quotation_number');
        });

        Schema::table('project_quotations', function (Blueprint $table) {
            $table->dropColumn([
                'items',
                'discount_type',
                'discount_value',
                'total_after_discount',
            ]);
        });
    }

    /**
     * Mengonversi data item lama (project_quotation_items) menjadi kolom
     * items JSON berformat {keterangan, volume, satuan, harga} agar
     * penawaran lama tetap dapat ditampilkan oleh tampilan baru (flat items).
     */
    private function backfillItemsFromLegacyTable(): void
    {
        $quotations = DB::table('project_quotations')->whereNull('items')->get();

        foreach ($quotations as $quotation) {
            $rows = DB::table('project_quotation_items')
                ->where('quotation_number', $quotation->quotation_number)
                ->orderBy('order_number')
                ->get();

            $items = [];

            foreach ($rows as $row) {
                $items[] = [
                    'keterangan' => $row->description,
                    'volume' => (float) ($row->volume ?? 0),
                    'satuan' => $row->unit ?? null,
                    'harga' => (int) ($row->unit_price ?? 0),
                ];
            }

            DB::table('project_quotations')
                ->where('quotation_number', $quotation->quotation_number)
                ->update(['items' => json_encode($items)]);
        }
    }
};
