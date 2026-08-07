<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * 1. aluminium_quotations: tambah kolom items (JSON, format flat seperti
     *    invoice), discount, dan DP agar form penawaran identik dengan
     *    Invoice Alumunium.
     * 2. alumunium_invoices: tambah kolom quotation_number (nullable) sebagai
     *    penghubung ke penawaran yang membuat invoice secara otomatis.
     * 3. Backfill: konversi data lama (kelompok/items) menjadi items JSON.
     */
    public function up(): void
    {
        Schema::table('aluminium_quotations', function (Blueprint $table) {
            $table->json('items')->nullable()->after('total_amount');
            $table->string('discount_type')->nullable()->after('items');
            $table->decimal('discount_value', 15, 2)->nullable()->after('discount_type');
            $table->integer('total_after_discount')->nullable()->after('discount_value');
            $table->string('dp_type')->nullable()->after('total_after_discount');
            $table->decimal('dp_value', 15, 2)->nullable()->after('dp_type');
            $table->integer('dp_amount')->nullable()->after('dp_value');
        });

        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->string('quotation_number')->nullable()->after('invoice_number');
            $table->index('quotation_number');
        });

        $this->backfillItemsFromGroups();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumunium_invoices', function (Blueprint $table) {
            $table->dropIndex(['quotation_number']);
            $table->dropColumn('quotation_number');
        });

        Schema::table('aluminium_quotations', function (Blueprint $table) {
            $table->dropColumn([
                'items',
                'discount_type',
                'discount_value',
                'total_after_discount',
                'dp_type',
                'dp_value',
                'dp_amount',
            ]);
        });
    }

    /**
     * Mengonversi data kelompok/item lama menjadi kolom items JSON
     * berformat {keterangan, volume, satuan, harga} agar penawaran lama
     * tetap dapat ditampilkan oleh tampilan baru (flat items).
     */
    private function backfillItemsFromGroups(): void
    {
        $quotations = DB::table('aluminium_quotations')->whereNull('items')->get();

        foreach ($quotations as $quotation) {
            $groups = DB::table('aluminium_quotation_groups')
                ->where('quotation_number', $quotation->quotation_number)
                ->orderBy('order_number')
                ->get();

            $items = [];

            foreach ($groups as $group) {
                $rows = DB::table('aluminium_quotation_items')
                    ->where('group_id', $group->id)
                    ->orderBy('order_number')
                    ->get();

                foreach ($rows as $row) {
                    $items[] = [
                        'keterangan' => $row->description,
                        'volume' => (float) ($row->volume ?? 0),
                        'satuan' => $row->unit ?? null,
                        'harga' => (int) ($row->unit_price ?? 0),
                    ];
                }
            }

            DB::table('aluminium_quotations')
                ->where('quotation_number', $quotation->quotation_number)
                ->update(['items' => json_encode($items)]);
        }
    }
};
