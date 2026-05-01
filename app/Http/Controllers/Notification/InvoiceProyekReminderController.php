<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\InvoiceProyekReminder;
use App\Models\Finance\InvoiceProyek;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InvoiceProyekReminderController extends Controller
{
    /**
     * Tampilkan halaman reminder jatuh tempo invoice proyek
     */
    public function index(Request $request)
    {
        $query = InvoiceProyekReminder::with('invoice');

        // Filter berdasarkan bulan
        if ($request->filled('month')) {
            $query->whereMonth('invoice_date', $request->month);
        }

        // Filter berdasarkan tahun
        if ($request->filled('year')) {
            $query->whereYear('invoice_date', $request->year);
        } else {
            // Default tahun saat ini
            $query->whereYear('invoice_date', date('Y'));
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search berdasarkan invoice_number atau recipient
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhere('recipient', 'like', '%' . $request->search . '%');
            });
        }

        // Sorting selalu by created_at DESC (data terbaru dulu)
        $query->orderBy('created_at', 'desc');

        $reminders = $query->paginate(10)->appends($request->all());

        // Calculate summary statistics berdasarkan data yang sudah di-filter (sebelum paginate)
        $totalReminders = $query->count();
        $totalPending = $query->clone()->byStatus('pending')->count();
        $totalNotified = $query->clone()->byStatus('notified')->count();
        $totalPaid = $query->clone()->byStatus('paid')->count();

        // Hitung invoice yang jatuh tempo (overdue)
        $overdueReminders = InvoiceProyekReminder::whereDate('reminder_date', '<=', now()->toDateString())
            ->where('status', '!=', 'paid')
            ->count();

        return view('pages.notification.invoice-proyek-reminder', compact(
            'reminders',
            'totalReminders',
            'totalPending',
            'totalNotified',
            'totalPaid',
            'overdueReminders'
        ));
    }

    /**
     * Update status reminder
     */
    public function updateStatus(Request $request, $id)
    {
        $reminder = InvoiceProyekReminder::findOrFail($id);
        $reminder->status = $request->status;

        if ($request->status === 'notified') {
            $reminder->notification_sent_at = now();
        }

        if ($request->status === 'paid') {
            $reminder->notification_sent_at = now();
        }

        $reminder->save();

        return redirect()->back()->with('success', 'Status reminder berhasil diperbarui.');
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $ids = $request->ids;
        $status = $request->status;

        InvoiceProyekReminder::whereIn('id', $ids)->update([
            'status' => $status,
            'notification_sent_at' => $status === 'paid' ? now() : ($status === 'notified' ? now() : null),
        ]);

        return redirect()->back()->with('success', 'Status reminder berhasil diperbarui.');
    }
}
