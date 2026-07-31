<?php

namespace App\Services\Notification;

use App\Models\Notification\InvoiceProyekReminder;
use App\Repositories\Notification\InvoiceProyekReminderRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service untuk mengelola business logic Invoice Proyek Reminder.
 *
 * Service ini bertanggung jawab atas pengambilan data reminder jatuh tempo,
 * pembaruan status, dan statistik ringkasan.
 * Business logic tidak boleh berada langsung di Controller.
 */
class InvoiceProyekReminderService
{
    public function __construct(
        private readonly InvoiceProyekReminderRepository $repository
    ) {}

    /**
     * Mendapatkan daftar invoice proyek reminder dengan filter dan paginasi.
     *
     * Logika: seluruh filter (month, year, status, search) + pagination
     * didelegasikan ke repository->search(). Service tidak menulis query
     * langsung — query terpusat di repository supaya tidak terduplikasi.
     *
     * @param  array  $filters  Parameter filter dari request (month, year, status, search)
     * @return LengthAwarePaginator
     */
    public function getPaginatedReminders(array $filters): LengthAwarePaginator
    {
        return $this->repository->search($filters);
    }

    /**
     * Mendapatkan statistik ringkasan berdasarkan filter yang diterapkan.
     *
     * Logika:
     * - total   = seluruh reminder sesuai filter (semua status).
     * - paid    = reminder berstatus 'paid' sesuai filter.
     * - expired = reminder LEWAT jatuh tempo yang masih punya sisa tagihan
     *   (status != paid + scope overdue + remaining_amount > 0 — dihitung oleh
     *   repository dengan eager load paymentProofs).
     * - pending = total - paid - expired. Di-guard max(0) agar tidak negatif
     *   bila hitungan expired tumpang tindih dengan kategori lain.
     *
     * @param  array  $filters  Parameter filter dari request
     * @return array  ['total' => int, 'pending' => int, 'expired' => int, 'paid' => int]
     */
    public function getSummaryStats(array $filters): array
    {
        $total = $this->repository->countByFilters($filters);
        $paid = $this->repository->countByStatus('paid', $filters);
        $expired = $this->repository->countExpired($filters);
        $pending = max(0, $total - $paid - $expired);

        return [
            'total' => $total,
            'pending' => $pending,
            'expired' => $expired,
            'paid' => $paid,
        ];
    }

    /**
     * Memperbarui status satu reminder invoice proyek.
     *
     * Logika transisi notification_sent_at:
     * - Status 'notified'/'paid' → notification_sent_at diisi now() (notifikasi
     *   dianggap sudah terkirim / pelunasan tercatat).
     * - Status 'pending' → notification_sent_at di-reset null (reminder dianggap
     *   belum dikirim lagi).
     * - Status lain tidak menyentuh notification_sent_at.
     *
     * @param  int     $id     ID reminder
     * @param  string  $status Status baru ('pending', 'notified', 'paid')
     * @return \App\Models\Notification\InvoiceProyekReminder
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateStatus(int $id, string $status): InvoiceProyekReminder
    {
        $reminder = InvoiceProyekReminder::findOrFail($id);
        $reminder->status = $status;

        if (in_array($status, ['notified', 'paid'], true)) {
            $reminder->notification_sent_at = now();
        }

        if ($status === 'pending') {
            $reminder->notification_sent_at = null;
        }

        $reminder->save();

        return $reminder;
    }

    /**
     * Memperbarui status beberapa reminder sekaligus (bulk update).
     *
     * Logika: satu query UPDATE massal (whereIn); notification_sent_at di-set
     * now() jika status baru notified/paid, selain itu null. Lebih efisien
     * daripada loop per record karena tidak butuh trigger per-model.
     *
     * @param  array   $ids    Daftar ID reminder
     * @param  string  $status Status baru ('pending', 'notified', 'paid')
     * @return int  Jumlah record yang diperbarui
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return InvoiceProyekReminder::whereIn('id', $ids)->update([
            'status' => $status,
            'notification_sent_at' => in_array($status, ['notified', 'paid'], true) ? now() : null,
        ]);
    }
}
