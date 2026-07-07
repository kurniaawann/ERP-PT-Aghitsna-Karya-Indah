<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\PaymentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentAccount::query();

        // Search functionality
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('account_holder', 'like', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('id')->paginate(15);
        return view('pages.finance.payment-accounts', compact('accounts'));
    }

    public function store(Request $request)
    {
        PaymentAccount::create([
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder' => $request->account_holder,
            'is_active' => true,
        ]);

        return redirect()->route('payment-accounts.index')
            ->with('success', 'Rekening pembayaran berhasil ditambahkan!');
    }

    public function update(Request $request, PaymentAccount $paymentAccount)
    {
        $paymentAccount->update([
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder' => $request->account_holder,
        ]);

        return redirect()->route('payment-accounts.index')
            ->with('success', 'Rekening pembayaran berhasil diupdate!');
    }

    public function toggleActive(PaymentAccount $paymentAccount)
    {
        $paymentAccount->update([
            'is_active' => !$paymentAccount->is_active,
        ]);

        $status = $paymentAccount->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('payment-accounts.index')
            ->with('success', "Rekening pembayaran berhasil {$status}!");
    }

    public function destroySelected(Request $request)
    {
        $selectedIds = $request->selected_accounts;
        $totalAccounts = PaymentAccount::count();
        $selectedCount = count($selectedIds);
        $isAjax = $request->ajax();

        if ($selectedCount >= $totalAccounts) {
            $msg = 'Tidak dapat menghapus semua rekening pembayaran. Minimal 1 rekening harus tetap ada.';
            return $isAjax
                ? response()->json(['success' => false, 'message' => $msg])
                : redirect()->route('payment-accounts.index')->with('error', $msg);
        }

        // Cek apakah rekening sedang digunakan di data lain
        $usedIds = [];

        foreach ($selectedIds as $id) {
            if (
                DB::table('kwintansi')->where('payment_account_id', $id)->exists()
                || DB::table('project_quotations')->whereJsonContains('selected_payment_accounts', $id)->exists()
                || DB::table('aluminium_quotations')->whereJsonContains('selected_payment_accounts', $id)->exists()
                || DB::table('proyek_invoices')->whereJsonContains('selected_payment_accounts', $id)->exists()
                || DB::table('alumunium_invoices')->whereJsonContains('selected_payment_accounts', $id)->exists()
                || DB::table('notas_administrasi')->whereJsonContains('selected_payment_accounts', $id)->exists()
                || DB::table('rabs')->where('selected_payment_accounts', 'like', '%"'.$id.'"%')->exists()
            ) {
                $usedIds[] = $id;
            }
        }

        if (!empty($usedIds)) {
            $usedNames = PaymentAccount::whereIn('id', $usedIds)
                ->get()
                ->map(fn($a) => $a->bank_name . ' - ' . $a->account_number)
                ->implode(', ');

            $msg = "Rekening berikut tidak dapat dihapus karena masih digunakan pada data lain: {$usedNames}. Silahkan hapus atau ubah data yang menggunakan rekening ini terlebih dahulu.";

            return $isAjax
                ? response()->json(['success' => false, 'message' => $msg, 'type' => 'usage_error'])
                : redirect()->route('payment-accounts.index')->with('usage_error', $msg);
        }

        PaymentAccount::whereIn('id', $selectedIds)->delete();

        $msg = "{$selectedCount} rekening pembayaran berhasil dihapus!";
        return $isAjax
            ? response()->json(['success' => true, 'message' => $msg])
            : redirect()->route('payment-accounts.index')->with('success', $msg);
    }

    public function destroy(PaymentAccount $paymentAccount)
    {
        // Cek apakah ini satu-satunya rekening
        $totalAccounts = PaymentAccount::count();
        if ($totalAccounts <= 1) {
            return redirect()->route('payment-accounts.index')
                ->with('error', 'Tidak dapat menghapus rekening terakhir. Minimal 1 rekening harus tetap ada.');
        }

        $paymentAccount->delete();

        return redirect()->route('payment-accounts.index')
            ->with('success', 'Rekening pembayaran berhasil dihapus!');
    }
}
