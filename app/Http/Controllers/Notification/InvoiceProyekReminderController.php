<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\BulkUpdateReminderStatusRequest;
use App\Http\Requests\Notification\UpdateReminderStatusRequest;
use App\Services\Notification\InvoiceProyekReminderService;
use Illuminate\Http\Request;

/**
 * Controller untuk menangani reminder jatuh tempo Invoice Proyek.
 *
 * Controller ini hanya menangani request dan response HTTP.
 * Business logic didelegasikan ke InvoiceProyekReminderService.
 */
class InvoiceProyekReminderController extends Controller
{
    public function __construct(
        private readonly InvoiceProyekReminderService $service
    ) {}

    /**
     * Menampilkan halaman daftar reminder jatuh tempo invoice proyek.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $filters = $request->only(['month', 'year', 'status', 'search']);

        $reminders = $this->service->getPaginatedReminders($filters);
        $stats = $this->service->getSummaryStats($filters);

        return view('pages.notification.invoice-proyek-reminder', [
            'reminders' => $reminders,
            'totalReminders' => $stats['total'],
            'totalPending' => $stats['pending'],
            'totalExpired' => $stats['expired'],
            'totalPaid' => $stats['paid'],
        ]);
    }

    /**
     * Memperbarui status satu reminder invoice proyek.
     *
     * @param  \App\Http\Requests\Notification\UpdateReminderStatusRequest  $request
     * @param  int  $id  ID reminder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus(UpdateReminderStatusRequest $request, $id)
    {
        $this->service->updateStatus($id, $request->validated('status'));

        return redirect()->back()->with('success', 'Status reminder berhasil diperbarui.');
    }

    /**
     * Memperbarui status beberapa reminder sekaligus (bulk update).
     *
     * @param  \App\Http\Requests\Notification\BulkUpdateReminderStatusRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkUpdateStatus(BulkUpdateReminderStatusRequest $request)
    {
        $this->service->bulkUpdateStatus(
            $request->validated('ids'),
            $request->validated('status')
        );

        return redirect()->back()->with('success', 'Status reminder berhasil diperbarui.');
    }
}
