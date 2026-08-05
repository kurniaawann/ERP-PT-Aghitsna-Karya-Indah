<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengganti kolom string signed_by/division pada proyek_invoices dengan
 * foreign key proper:
 * - signed_by_id -> executives.id  (Nama Penandatangan dari Data Petinggi)
 * - division_id  -> divisions.id   (Divisi dari submodul Divisi)
 *
 * Data lama (string) dipetakan ke ID yang cocok berdasarkan nama, lalu
 * kolom string dihapus agar data tetap konsisten (nama yang berubah pada
 * master data otomatis ikut ter-refleksi di invoice melalui relasi).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->foreignId('signed_by_id')
                ->nullable()
                ->after('division')
                ->constrained('executives')
                ->nullOnDelete();
            $table->foreignId('division_id')
                ->nullable()
                ->after('signed_by_id')
                ->constrained('divisions')
                ->nullOnDelete();
        });

        $invoices = DB::table('proyek_invoices')->get();

        foreach ($invoices as $invoice) {
            $signedById = $invoice->signed_by
                ? DB::table('executives')->where('name', $invoice->signed_by)->value('id')
                : null;
            $divisionId = $invoice->division
                ? DB::table('divisions')->where('name', $invoice->division)->value('id')
                : null;

            if ($signedById !== null || $divisionId !== null) {
                DB::table('proyek_invoices')
                    ->where('invoice_number', $invoice->invoice_number)
                    ->update([
                        'signed_by_id' => $signedById,
                        'division_id' => $divisionId,
                    ]);
            }
        }

        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropColumn(['signed_by', 'division']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->string('signed_by')->nullable()->after('selected_payment_accounts');
            $table->string('division')->nullable()->after('signed_by');
        });

        $invoices = DB::table('proyek_invoices')->get();

        foreach ($invoices as $invoice) {
            $signedByName = $invoice->signed_by_id
                ? DB::table('executives')->where('id', $invoice->signed_by_id)->value('name')
                : null;
            $divisionName = $invoice->division_id
                ? DB::table('divisions')->where('id', $invoice->division_id)->value('name')
                : null;

            DB::table('proyek_invoices')
                ->where('invoice_number', $invoice->invoice_number)
                ->update([
                    'signed_by' => $signedByName,
                    'division' => $divisionName,
                ]);
        }

        Schema::table('proyek_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signed_by_id');
            $table->dropConstrainedForeignId('division_id');
        });
    }
};
