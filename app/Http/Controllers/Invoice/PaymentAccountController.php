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
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder' => 'required|string|max:255',
        ]);

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
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder' => 'required|string|max:255',
        ]);

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
        $request->validate([
            'selected_accounts' => 'required|array',
            'selected_accounts.*' => 'exists:payment_accounts,id',
        ]);

        $selectedIds = $request->selected_accounts;
        PaymentAccount::whereIn('id', $selectedIds)->delete();

        $count = count($selectedIds);
        return redirect()->route('payment-accounts.index')
            ->with('success', "{$count} rekening pembayaran berhasil dihapus!");
    }

    public function destroy(PaymentAccount $paymentAccount)
    {
        $paymentAccount->delete();

        return redirect()->route('payment-accounts.index')
            ->with('success', 'Rekening pembayaran berhasil dihapus!');
    }
}
