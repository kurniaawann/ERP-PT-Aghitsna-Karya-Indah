<?php

namespace App\Models\Administrasi;

use App\Services\Finance\ReimburseService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query builder khusus model Nota.
 *
 * Revisi klien (Nota → Reimbursement otomatis): semua jalur penghapusan nota
 * lewat Eloquent — $nota->delete(), Nota::destroy(), maupun hapus massal
 * Nota::whereIn(...)->delete() (bulk delete Surat Menyurat, alur hapus
 * invoice/DO semen, dst.) — berakhir di delete() builder ini. Hapus massal
 * tidak memicu event model, sehingga pembersihan dilakukan di sini, bukan
 * di observer.
 *
 * Sebelum nota dihapus, pengajuan reimburse tertaut yang masih draft ikut
 * dihapus. Yang sudah disetujui/ditolak tetap disimpan; tautannya
 * dikosongkan oleh FK reimburses.id_nota ON DELETE SET NULL.
 *
 * Catatan: DB::table('notas_administrasi')->delete() melewati builder ini
 * (draft akan tertinggal tanpa tautan) — gunakan model Nota untuk menghapus.
 */
class NotaBuilder extends Builder
{
    /**
     * Hapus nota sesuai query, didahului penghapusan reimburse draft tertaut.
     *
     * @return mixed  Jumlah nota yang dihapus
     */
    public function delete()
    {
        return $this->getConnection()->transaction(function () {
            $notaIds = (clone $this)
                ->pluck($this->getModel()->getQualifiedKeyName())
                ->all();

            if (!empty($notaIds)) {
                app(ReimburseService::class)->deleteDraftsForNotas($notaIds);
            }

            return parent::delete();
        });
    }
}
