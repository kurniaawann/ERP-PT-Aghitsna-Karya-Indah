<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePaymentAccountRequest;
use App\Http\Requests\Finance\UpdatePaymentAccountRequest;
use App\Models\Finance\PaymentAccount;
use App\Services\Finance\PaymentAccountService;
use Illuminate\Http\Request;

/**
 * Controller untuk sub modul Rekening Pembayaran (Finance).
 *
 * Menangani operasi CRUD rekening pembayaran, toggle status aktif,
 * dan bulk delete dengan pengecekan penggunaan di tabel lain.
 */
class PaymentAccountController extends Controller
{
    public function __construct(
        private PaymentAccountService $service
    ) {}

    /**
     * Halaman index: menampilkan daftar rekening pembayaran dengan filter pencarian.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $accounts = $this->service->buildFilteredQuery($request)
            ->orderBy('id')
            ->paginate(15);

        return view('pages.finance.payment-accounts', compact('accounts'));
    }

    /**
     * Simpan rekening pembayaran baru.
     *
     * @param  StorePaymentAccountRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePaymentAccountRequest $request)
    {
        $this->service->createAccount($request->validated());

        return redirect()->route('payment-accounts.index')
            ->with('success', 'Rekening pembayaran berhasil ditambahkan!');
    }

    /**
     * Update rekening pembayaran.
     *
     * @param  UpdatePaymentAccountRequest $request
     * @param  PaymentAccount              $paymentAccount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePaymentAccountRequest $request, PaymentAccount $paymentAccount)
    {
        $this->service->updateAccount($paymentAccount, $request->validated());

        return redirect()->route('payment-accounts.index')
            ->with('success', 'Rekening pembayaran berhasil diupdate!');
    }

    /**
     * Toggle status aktif/nonaktif rekening pembayaran.
     *
     * Selalu diizinkan (soft approach). Saat nonaktif:
     * - Data lama (invoice, quotation, dll) tidak terpengaruh.
     * - Dropdown dokumen baru hanya menampilkan rekening aktif.
     *
     * @param  PaymentAccount $paymentAccount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive(PaymentAccount $paymentAccount)
    {
        $this->service->toggleActive($paymentAccount);

        $paymentAccount->refresh();
        $status = $paymentAccount->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $msg = "Rekening pembayaran berhasil {$status}!";

        // Beri info jika menonaktifkan rekening yang masih dipakai
        if (!$paymentAccount->is_active) {
            $summary = $this->service->getUsageSummary($paymentAccount);

            if (!empty($summary)) {
                $details = collect($summary)->map(fn($s) => "{$s['table']} ({$s['count']})")->implode(', ');
                $msg .= " Rekening ini masih digunakan pada: {$details}. Data yang sudah ada tidak akan terpengaruh.";
            }
        }

        return redirect()->route('payment-accounts.index')->with('success', $msg);
    }

    /**
     * Hapus beberapa rekening pembayaran sekaligus (bulk delete).
     *
     * Mencek penggunaan rekening di tabel lain sebelum menghapus.
     * Mendukung response AJAX dan redirect biasa.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroySelected(Request $request)
    {
        $selectedIds = $request->input('selected_accounts', []);
        $totalAccounts = PaymentAccount::count();
        $selectedCount = count($selectedIds);
        $isAjax = $request->ajax();

        // Guard: tidak boleh menghapus semua rekening
        if ($selectedCount >= $totalAccounts) {
            $msg = 'Tidak dapat menghapus semua rekening pembayaran. Minimal 1 rekening harus tetap ada.';
            return $isAjax
                ? response()->json(['success' => false, 'message' => $msg])
                : redirect()->route('payment-accounts.index')->with('error', $msg);
        }

        // Cek penggunaan rekening di tabel lain (batch query)
        $usedIds = $this->service->findUsedAccountIds($selectedIds);

        if (!empty($usedIds)) {
            $usedNames = $this->service->getAccountLabels($usedIds);
            $msg = "Rekening berikut tidak dapat dihapus karena masih digunakan pada data lain: {$usedNames}. Silahkan hapus atau ubah data yang menggunakan rekening ini terlebih dahulu.";

            return $isAjax
                ? response()->json(['success' => false, 'message' => $msg, 'type' => 'usage_error'])
                : redirect()->route('payment-accounts.index')->with('usage_error', $msg);
        }

        $this->service->bulkDelete($selectedIds);

        $msg = "{$selectedCount} rekening pembayaran berhasil dihapus!";
        return $isAjax
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->route('payment-accounts.index')->with('success', $msg);
    }

    /**
     * Hapus satu rekening pembayaran.
     *
     * Guard:
     * 1. Minimal 1 rekening harus tetap ada.
     * 2. Rekening tidak boleh masih digunakan di tabel lain (kwintansi, invoice, quotation, dll).
     *
     * @param  PaymentAccount $paymentAccount
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PaymentAccount $paymentAccount)
    {
        if (!$this->service->canDelete()) {
            return redirect()->route('payment-accounts.index')
                ->with('error', 'Tidak dapat menghapus rekening terakhir. Minimal 1 rekening harus tetap ada.');
        }

        if ($this->service->isAccountUsed($paymentAccount)) {
            $summary = $this->service->getUsageSummary($paymentAccount);
            $details = collect($summary)->map(fn($s) => "{$s['table']} ({$s['count']})")->implode(', ');

            return redirect()->route('payment-accounts.index')
                ->with('error', "Rekening \"{$paymentAccount->bank_name} - {$paymentAccount->account_number}\" tidak dapat dihapus karena masih digunakan pada: {$details}. Nonaktifkan rekening ini atau hapus data yang menggunakannya terlebih dahulu.");
        }

        $this->service->deleteAccount($paymentAccount);

        return redirect()->route('payment-accounts.index')
            ->with('success', 'Rekening pembayaran berhasil dihapus!');
    }
}
