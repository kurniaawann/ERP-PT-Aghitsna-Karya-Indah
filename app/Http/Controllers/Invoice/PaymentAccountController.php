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

        $accounts = $query->orderBy('order')->paginate(10);
        return view('pages.invoice.payment-accounts', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_holder' => 'required|string|max:255',
        ]);

        $maxOrder = PaymentAccount::max('order') ?? 0;

        PaymentAccount::create([
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder' => $request->account_holder,
            'is_active' => true,
            'order' => $maxOrder + 1,
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
            'order' => 'nullable|integer|min:1',
        ]);

        $updateData = [
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder' => $request->account_holder,
        ];

        if ($request->has('order')) {
            $updateData['order'] = $request->order;
        }

        $paymentAccount->update($updateData);

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

    public function destroy(Request $request, PaymentAccount $paymentAccount)
    {
        // Check if bulk delete
        if ($request->has('selected_accounts')) {
            $selectedIds = $request->selected_accounts;
            PaymentAccount::whereIn('id', $selectedIds)->delete();

            $count = count($selectedIds);
            return redirect()->route('payment-accounts.index')
                ->with('success', "{$count} rekening pembayaran berhasil dihapus!");
        }

        // Single delete
        $paymentAccount->delete();

        return redirect()->route('payment-accounts.index')
            ->with('success', 'Rekening pembayaran berhasil dihapus!');
    }
}
