<?php

namespace App\Services\Report;

use App\Models\Sdm\Executive;
use Illuminate\Http\Request;

/**
 * Penandatangan laporan yang dipilih saat cetak (PDF/Excel).
 *
 * Revisi klien: Laporan Pengeluaran (super admin) / Kas Kantor (admin) WAJIB
 * memilih satu penandatangan dari Data Penandatangan (seperti invoice)
 * sebelum dicetak. Pilihan dikirim sebagai query `signer_id` oleh modal
 * "Penandatangan Laporan" (lihat components/print-signer-modal & JS
 * shared/print-signer.js), lalu dicetak sebagai satu blok tanda tangan di
 * kanan: jabatan, gambar tanda tangan (bila ada), dan nama.
 */
class ReportSignerService
{
    /**
     * Apakah role user aktif wajib memilih penandatangan saat cetak.
     * Role lain (mis. general manager) tetap bisa cetak tanpa penandatangan.
     */
    public function isRequired(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isSuperAdmin() || $user?->isAdmin());
    }

    /**
     * Snapshot penandatangan dari `signer_id` (hanya Data Penandatangan milik
     * user login).
     *
     * Wajib untuk super admin & admin: tanpa penandatangan valid, permintaan
     * cetak ditolak (422) — tombol cetak di UI selalu meminta pilihan dulu.
     *
     * @return array{name: string, position: string|null, signature_image: string|null}|null
     */
    public function resolve(Request $request): ?array
    {
        $signerId = (int) $request->query('signer_id');

        $executive = $signerId > 0
            ? Executive::where('created_by', auth()->id())->find($signerId)
            : null;

        if (! $executive) {
            abort_if($this->isRequired(), 422, 'Penandatangan laporan wajib dipilih sebelum mencetak.');

            return null;
        }

        return [
            'name' => $executive->name,
            'position' => $executive->position,
            'signature_image' => $executive->signature_image,
        ];
    }
}
