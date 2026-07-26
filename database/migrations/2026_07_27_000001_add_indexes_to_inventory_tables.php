<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════════════
        // items — 1 index baru
        // ═══════════════════════════════════════════════════════════════
        // idx_items_name_item
        // Digunakan oleh: scopeSearch (LIKE %name_item%), resolveNewItemForUpdate (WHERE name_item =)
        // Alasan: name_item adalah kolom pencarian utama. Tanpa index, setiap
        // pencarian harus full table scan pada seluruh row items.
        Schema::table('items', function (Blueprint $table) {
            $table->index('name_item', 'idx_items_name_item');
        });

        // ═══════════════════════════════════════════════════════════════
        // item_stock_ins — 1 index baru
        // ═══════════════════════════════════════════════════════════════
        // idx_stock_ins_date
        // Digunakan oleh: scopeFilterMonth (WHERE MONTH(date)), scopeFilterYear (WHERE YEAR(date)),
        //   orderBy('date', 'desc') di baseQuery controller
        // Alasan: date adalah kolom filter utama di halaman Barang Masuk. Setiap
        // request index/export memanggil whereMonth + whereYear + orderBy date.
        // Tanpa index, query harus scan seluruh tabel item_stock_ins.
        Schema::table('item_stock_ins', function (Blueprint $table) {
            $table->index('date', 'idx_stock_ins_date');
        });

        // ═══════════════════════════════════════════════════════════════
        // item_stock_outs — 1 index baru
        // ═══════════════════════════════════════════════════════════════
        // idx_stock_outs_date
        // Digunakan oleh: scopeFilterMonth, scopeFilterYear, orderBy('date', 'desc')
        //   di baseQuery controller
        // Alasan: Sama seperti stock_ins, date adalah filter utama untuk
        // halaman Barang Keluar dan export PDF/Excel.
        Schema::table('item_stock_outs', function (Blueprint $table) {
            $table->index('date', 'idx_stock_outs_date');
        });

        // ═══════════════════════════════════════════════════════════════
        // item_returns — 2 index baru
        // ═══════════════════════════════════════════════════════════════
        // idx_returns_date
        // Digunakan oleh: scopeFilterMonth, scopeFilterYear, orderBy('date', 'desc')
        //   di baseQuery controller
        // Alasan: Date adalah filter utama di halaman Pengembalian Barang.
        //
        // idx_returns_return_type
        // Digunakan oleh: scopeFilterReturnType (WHERE return_type = 'masuk'/'keluar')
        // Alasan: return_type memiliki 2 nilai (masuk/keluar) — low cardinality,
        // TETAPI karena kombinasi filter date + return_type sangat sering dipakai
        // bersamaan, index ini membantu database filter lebih cepat setelah
        // index date mengurangi jumlah row.
        Schema::table('item_returns', function (Blueprint $table) {
            $table->index('date', 'idx_returns_date');
            $table->index('return_type', 'idx_returns_return_type');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('idx_items_name_item');
        });

        Schema::table('item_stock_ins', function (Blueprint $table) {
            $table->dropIndex('idx_stock_ins_date');
        });

        Schema::table('item_stock_outs', function (Blueprint $table) {
            $table->dropIndex('idx_stock_outs_date');
        });

        Schema::table('item_returns', function (Blueprint $table) {
            $table->dropIndex('idx_returns_date');
            $table->dropIndex('idx_returns_return_type');
        });
    }
};
