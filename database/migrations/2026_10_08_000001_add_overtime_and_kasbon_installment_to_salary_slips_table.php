<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     *
     * Revisi klien slip gaji karyawan kantor (bulanan):
     * - overtime_total     : snapshot total lembur (modul Lembur) pada bulan
     *                        slip — ditampilkan di bawah baris Uang Makan dan
     *                        ikut dihitung sebagai penerimaan.
     * - kasbon_total       : snapshot total sisa kasbon karyawan sebelum
     *                        dipotong slip ini (mis. Rp. 500.000).
     * - kasbon_installment : nominal cicilan kasbon yang diinput admin untuk
     *                        bulan ini; null = potong seluruh sisa kasbon.
     *                        Potongan efektif tetap disimpan di kasbon_deduction.
     */
    public function up(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_slips', 'overtime_total')) {
                $table->integer('overtime_total')->default(0)->after('meal_total');
            }
            if (! Schema::hasColumn('salary_slips', 'kasbon_total')) {
                $table->integer('kasbon_total')->default(0)->after('pph21');
            }
            if (! Schema::hasColumn('salary_slips', 'kasbon_installment')) {
                $table->integer('kasbon_installment')->nullable()->after('kasbon_total');
            }
        });

        // Slip lama memotong seluruh sisa kasbon sekaligus, sehingga total
        // kasbon = potongan kasbon (sisa setelah dipotong = 0).
        DB::table('salary_slips')
            ->where('kasbon_total', 0)
            ->where('kasbon_deduction', '>', 0)
            ->update(['kasbon_total' => DB::raw('kasbon_deduction')]);
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            foreach (['overtime_total', 'kasbon_total', 'kasbon_installment'] as $column) {
                if (Schema::hasColumn('salary_slips', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
