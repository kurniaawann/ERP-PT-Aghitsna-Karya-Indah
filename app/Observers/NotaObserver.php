<?php

namespace App\Observers;

use App\Models\Administrasi\Nota;
use App\Services\Finance\ReimburseService;

/**
 * Observer untuk model Nota (revisi klien: Nota → Reimbursement otomatis).
 *
 * - created: nota buatan Super Admin (input manual Surat Menyurat maupun
 *            nota proyek otomatis dari Invoice Semen) membuat satu pengajuan
 *            reimburse draft yang tertaut ke nota.
 * - updated: reimburse tertaut yang masih draft disinkronkan (tanggal,
 *            proyek, keterangan, total). Disetujui/ditolak tidak diubah.
 *
 * Penghapusan nota ditangani NotaBuilder::delete() agar hapus massal
 * (yang tidak memicu event model) juga tercakup.
 */
class NotaObserver
{
    public function __construct(
        private readonly ReimburseService $reimburseService
    ) {}

    /**
     * Handle the Nota "created" event.
     *
     * @param  \App\Models\Administrasi\Nota  $nota
     * @return void
     */
    public function created(Nota $nota): void
    {
        $this->reimburseService->createFromNota($nota);
    }

    /**
     * Handle the Nota "updated" event.
     *
     * @param  \App\Models\Administrasi\Nota  $nota
     * @return void
     */
    public function updated(Nota $nota): void
    {
        $this->reimburseService->syncFromNota($nota);
    }
}
