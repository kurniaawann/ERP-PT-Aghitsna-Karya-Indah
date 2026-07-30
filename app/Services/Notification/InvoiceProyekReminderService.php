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
