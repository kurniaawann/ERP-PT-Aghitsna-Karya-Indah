<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengganti kolom string signed_by/division pada project_quotations dan
 * aluminium_quotations dengan foreign key proper:
 * - signed_by_id -> executives.id  (Nama Penandatangan dari Data Petinggi)
 * - division_id  -> divisions.id   (Divisi dari submodul Divisi)
 *
 * Data lama (string) dipetakan ke ID yang cocok berdasarkan nama, lalu
 * kolom string dihapus agar data tetap konsisten (nama yang berubah pada
 * master data otomatis ikut ter-refleksi di penawaran melalui relasi).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_quotations', function (Blueprint $table) {
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

        Schema::table('aluminium_quotations', function (Blueprint $table) {
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

        foreach (['project_quotations', 'aluminium_quotations'] as $table) {
            $quotations = DB::table($table)->get();

            foreach ($quotations as $quotation) {
                $signedById = $quotation->signed_by
                    ? DB::table('executives')->where('name', $quotation->signed_by)->value('id')
                    : null;
                $divisionId = $quotation->division
                    ? DB::table('divisions')->where('name', $quotation->division)->value('id')
                    : null;

                if ($signedById !== null || $divisionId !== null) {
                    DB::table($table)
                        ->where('quotation_number', $quotation->quotation_number)
                        ->update([
                            'signed_by_id' => $signedById,
                            'division_id' => $divisionId,
                        ]);
                }
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['signed_by', 'division']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['project_quotations', 'aluminium_quotations'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('signed_by')->nullable()->after('selected_payment_accounts');
                $table->string('division')->nullable()->after('signed_by');
            });

            $quotations = DB::table($table)->get();

            foreach ($quotations as $quotation) {
                $signedByName = $quotation->signed_by_id
                    ? DB::table('executives')->where('id', $quotation->signed_by_id)->value('name')
                    : null;
                $divisionName = $quotation->division_id
                    ? DB::table('divisions')->where('id', $quotation->division_id)->value('name')
                    : null;

                DB::table($table)
                    ->where('quotation_number', $quotation->quotation_number)
                    ->update([
                        'signed_by' => $signedByName,
                        'division' => $divisionName,
                    ]);
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('signed_by_id');
                $table->dropConstrainedForeignId('division_id');
            });
        }
    }
};
