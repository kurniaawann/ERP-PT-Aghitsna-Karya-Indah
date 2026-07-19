<?php

namespace App\Repositories\Notification;

use App\Models\Notification\InvoiceProyekReminder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repository untuk akses data Invoice Proyek Reminder.
 *
 * Menangani query database terkait data pengingat jatuh tempo invoice proyek
 * menggunakan Eloquent dan query builder.
 * Scope pencarian delegasi ke Model scope untuk menghindari duplikasi.
 */
class InvoiceProyekReminderRepository
{
    /**
     * Mencari data invoice proyek reminder dengan paginasi dan filter.
     *
     * @param  array  $filters  Parameter filter (month, year, status, search)
     * @return LengthAwarePaginator
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = InvoiceProyekReminder::with('invoice');

        // Filter berdasarkan bulan
        if (!empty($filters['month'])) {
            $query->whereMonth('invoice_date', $filters['month']);
        }

        // Filter berdasarkan tahun (default: tahun saat ini)
        $year = !empty($filters['year']) ? $filters['year'] : date('Y');
        $query->whereYear('invoice_date', $year);

        // Filter berdasarkan status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Pencarian berdasarkan nomor invoice atau nama penerima
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $query->orderBy('created_at', 'desc');

        return $query->paginate(10)->appends($filters);
    }

    /**
     * Menghitung total reminder berdasarkan filter yang diterapkan.
     *
     * @param  array  $filters  Parameter filter yang sama dengan search()
     * @return int
     */
    public function countByFilters(array $filters): int
    {
        return InvoiceProyekReminder::query()
            ->when(!empty($filters['month']), fn ($q) => $q->whereMonth('invoice_date', $filters['month']))
            ->when(true, fn ($q) => $q->whereYear('invoice_date', $filters['year'] ?? date('Y')))
            ->when(!empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(!empty($filters['search']), fn ($q) => $q->search($filters['search']))
            ->count();
    }

    /**
     * Menghitung jumlah reminder berdasarkan status.
     *
     * @param  string  $status   Status yang dihitung ('pending', 'notified', 'paid')
     * @param  array   $filters  Parameter filter yang sama
     * @return int
     */
    public function countByStatus(string $status, array $filters): int
    {
        return InvoiceProyekReminder::query()
            ->when(!empty($filters['month']), fn ($q) => $q->whereMonth('invoice_date', $filters['month']))
            ->when(true, fn ($q) => $q->whereYear('invoice_date', $filters['year'] ?? date('Y')))
            ->when(!empty($filters['search']), fn ($q) => $q->search($filters['search']))
            ->where('status', $status)
            ->count();
    }

    /**
     * Menghitung jumlah expired reminder (sudah lewat jatuh tempo dengan sisa tagihan).
     *
     * Menggunakan eager loading paymentProofs untuk menghitung remaining_amount
     * secara akurat tanpa N+1 query.
     *
     * @param  array  $filters  Parameter filter yang sama
     * @return int
     */
    public function countExpired(array $filters): int
    {
        $query = InvoiceProyekReminder::with('invoice.paymentProofs')
            ->when(!empty($filters['month']), fn ($q) => $q->whereMonth('invoice_date', $filters['month']))
            ->when(true, fn ($q) => $q->whereYear('invoice_date', $filters['year'] ?? date('Y')))
            ->when(!empty($filters['search']), fn ($q) => $q->search($filters['search']))
            ->where('status', '!=', 'paid')
            ->overdue();

        return $query->get()
            ->filter(fn ($reminder) => $reminder->remaining_amount > 0)
            ->count();
    }
}
