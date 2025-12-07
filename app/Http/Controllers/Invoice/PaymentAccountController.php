<?php

namespace App\Http\Controllers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice\PaymentAccount;
use Illuminate\Http\Request;

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

        $accounts = $query->orderBy('id')->paginate(10);
        return view('pages.invoice.payment-accounts', compact('accounts'));
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

        // Cek apakah akan menghapus semua akun
        if ($selectedCount >= $totalAccounts) {
            return redirect()->route('payment-accounts.index')
                ->with('error', 'Tidak dapat menghapus semua rekening pembayaran. Minimal 1 rekening harus tetap ada.');
        }

        PaymentAccount::whereIn('id', $selectedIds)->delete();

        return redirect()->route('payment-accounts.index')
            ->with('success', "{$selectedCount} rekening pembayaran berhasil dihapus!");
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
