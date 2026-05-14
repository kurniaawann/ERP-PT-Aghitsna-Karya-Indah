<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\InvoiceProyekReminder;
use Illuminate\Http\Request;

class InvoiceProyekReminderController extends Controller
{
    /**
     * Tampilkan halaman reminder jatuh tempo invoice proyek
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
            $query->where(function ($subQuery) use ($request) {
                $subQuery->where('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhere('recipient', 'like', '%' . $request->search . '%');
            });
        }

        $query->orderBy('created_at', 'desc');

        $reminders = $query->paginate(10)->appends($request->all());

        $totalReminders = $query->count();
        $totalPaid = $query->clone()->where('status', 'paid')->count();
        $totalExpired = $query->clone()->where('status', '!=', 'paid')
            ->whereDate('reminder_date', '<=', now()->toDateString())
            ->get()
            ->filter(fn($reminder) => $reminder->remaining_amount > 0)
            ->count();
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
     * Update status reminder
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
     * Bulk update status
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
