<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\InvoiceProyekReminder;
use Illuminate\Http\Request;

/**
 * Controller untuk menangani reminder jatuh tempo Invoice Proyek.
 *
 * Menangani penampilan daftar reminder, pembaruan status per item,
 * dan pembaruan status secara massal (bulk update).
 */
class InvoiceProyekReminderController extends Controller
{
    /**
     * Menampilkan halaman daftar reminder jatuh tempo invoice proyek.
     *
     * Query di-optimasi dengan menggunakan aggregate query untuk menghitung
     * total expired tanpa memuat semua data ke memory.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = InvoiceProyekReminder::with('invoice');

        if ($request->filled('month')) {
            $query->whereMonth('invoice_date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('invoice_date', $request->year);
        } else {
            $query->whereYear('invoice_date', date('Y'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('recipient', 'like', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        $reminders = $query->paginate(10)->appends($request->all());

        $totalReminders = (clone $query)->count();
        $totalPaid = (clone $query)->where('status', 'paid')->count();

        // Hitung total expired menggunakan query langsung, bukan fetch ke memory
        // Ambil semua non-paid reminders yang sudah lewat jatuh tempo
        $expiredReminders = (clone $query)->where('status', '!=', 'paid')
            ->whereDate('reminder_date', '<=', now()->toDateString())
            ->get();
        $totalExpired = $expiredReminders->filter(fn($reminder) => $reminder->remaining_amount > 0)->count();

        $totalPending = max(0, $totalReminders - $totalPaid - $totalExpired);

        return view('pages.notification.invoice-proyek-reminder', compact(
            'reminders',
            'totalReminders',
            'totalPending',
            'totalExpired',
            'totalPaid'
        ));
    }

    /**
     * Memperbarui status satu reminder invoice proyek.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id  ID reminder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $reminder = InvoiceProyekReminder::findOrFail($id);
        $reminder->status = $request->status;

        if (in_array($request->status, ['notified', 'paid'], true)) {
            $reminder->notification_sent_at = now();
        }

        if ($request->status === 'pending') {
            $reminder->notification_sent_at = null;
        }

        $reminder->save();

        return redirect()->back()->with('success', 'Status reminder berhasil diperbarui.');
    }

    /**
     * Memperbarui status beberapa reminder sekaligus (bulk update).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $ids = $request->ids;
        $status = $request->status;

        InvoiceProyekReminder::whereIn('id', $ids)->update([
            'status' => $status,
            'notification_sent_at' => in_array($status, ['notified', 'paid'], true) ? now() : null,
        ]);

        return redirect()->back()->with('success', 'Status reminder berhasil diperbarui.');
    }
}
