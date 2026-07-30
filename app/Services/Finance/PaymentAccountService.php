<?php

namespace App\Services\Finance;

use App\Models\Finance\PaymentAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service layer untuk operasi bisnis Rekening Pembayaran.
 *
 * Menangani semua logika bisnis terkait Rekening Pembayaran termasuk:
 * - Pencarian dan filter
 * - Pengecekan penggunaan rekening di tabel lain
 * - Operasi bulk delete dengan validasi
 */
class PaymentAccountService
{
    /**
     * Tabel-tabel yang mereferensikan payment_accounts via JSON column.
     *
     * @var array<int, array{table: string, column: string, method: string}>
     */
    private const JSON_REFERENCE_TABLES = [
        ['table' => 'project_quotations',    'column' => 'selected_payment_accounts', 'method' => 'json'],
        ['table' => 'aluminium_quotations',  'column' => 'selected_payment_accounts', 'method' => 'json'],
        ['table' => 'proyek_invoices',       'column' => 'selected_payment_accounts', 'method' => 'json'],
        ['table' => 'alumunium_invoices',    'column' => 'selected_payment_accounts', 'method' => 'json'],
        ['table' => 'barang_invoices',       'column' => 'selected_payment_accounts', 'method' => 'json'],
        ['table' => 'notas_administrasi',    'column' => 'selected_payment_accounts', 'method' => 'json'],
        ['table' => 'rabs',                  'column' => 'selected_payment_accounts', 'method' => 'like'],
    ];

    public function __construct()
    {
        // Invalidate cache on construction if needed
    }

    /**
     * Mendapatkan semua rekening pembayaran yang aktif milik user login.
     *
     * Menggunakan cache per user untuk dropdown di halaman Invoice Aluminium,
     * Invoice Proyek, Bukti Pembayaran, dll.
     * Cache di-invalidate saat ada create/update/toggle/delete.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveAccounts()
    {
        $userId = auth()->id();
        $cacheKey = "finance:payment-accounts:active:{$userId}";

        try {
            return Cache::remember(
                $cacheKey,
                now()->addDay(),
                fn () => PaymentAccount::active()->where('created_by', $userId)->get()
            );
        } catch (\Exception $e) {
            Log::warning('Cache READ error [finance:payment-accounts:active]: ' . $e->getMessage());
            return PaymentAccount::active()->where('created_by', $userId)->get();
        }
    }

    /**
     * Invalidate cache rekening pembayaran aktif milik user login.
     *
     * @return void
     */
    public function flushCache(): void
    {
        $userId = auth()->id();

        try {
            Cache::forget("finance:payment-accounts:active:{$userId}");
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [finance:payment-accounts:active]: ' . $e->getMessage());
        }
    }

    /**
     * Membangun query dasar untuk listing rekening pembayaran.
     *
     * Menerapkan filter search pada bank_name, account_number, dan account_holder.
     *
     * @param  \Illuminate\Http\Request|null $request  Request yang berisi parameter filter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildFilteredQuery(?Request $request = null): Builder
    {
        $query = PaymentAccount::query()->where('created_by', auth()->id());

        if (!$request) {
            return $query;
        }

        $search = $request->input('search');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('account_holder', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Membuat rekening pembayaran baru.
     *
     * @param  array<string, mixed> $validated  Data yang sudah validasi
     * @return \App\Models\Finance\PaymentAccount
     */
    public function createAccount(array $validated): PaymentAccount
    {
        $result = PaymentAccount::create([
            'bank_name'      => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_holder' => $validated['account_holder'],
            'is_active'      => true,
            'created_by'     => auth()->id(),
        ]);

        $this->flushCache();

        return $result;
    }

    /**
     * Mengupdate rekening pembayaran.
     *
     * @param  \App\Models\Finance\PaymentAccount $account  Model yang akan diupdate
     * @param  array<string, mixed>               $validated Data yang sudah validasi
     * @return bool
     */
    public function updateAccount(PaymentAccount $account, array $validated): bool
    {
        $result = $account->update([
            'bank_name'      => $validated['bank_name'],
            'account_number' => $validated['account_number'],
            'account_holder' => $validated['account_holder'],
        ]);

        $this->flushCache();

        return $result;
    }

    /**
     * Toggle status aktif/nonaktif rekening pembayaran.
     *
     * @param  \App\Models\Finance\PaymentAccount $account  Model yang akan di-toggle
     * @return bool
     */
    public function toggleActive(PaymentAccount $account): bool
    {
        $result = $account->update([
            'is_active' => !$account->is_active,
        ]);

        $this->flushCache();

        return $result;
    }

    /**
     * Mengecek apakah rekening masih digunakan di tabel lain.
     *
     * Menggunakan batch query (1 per tabel, bukan 1 per ID) untuk performa optimal.
     *
     * @param  array<int, int> $ids  Daftar ID rekening yang akan dicek
     * @return array<int, int>       ID rekening yang masih digunakan
     */
    public function findUsedAccountIds(array $ids): array
    {
        $usedIds = [];

        // Cek relasi langsung via FK: kwintansi.payment_account_id
        $kwintansiIds = DB::table('kwintansi')
            ->whereIn('payment_account_id', $ids)
            ->pluck('payment_account_id')
            ->all();
        $usedIds = array_merge($usedIds, $kwintansiIds);

        // Cek referensi JSON di tabel-tabel lain
        foreach (self::JSON_REFERENCE_TABLES as $ref) {
            $foundIds = [];

            if ($ref['method'] === 'like') {
                // Kolom longText — gunakan LIKE per ID (rabs)
                foreach ($ids as $id) {
                    if (DB::table($ref['table'])
                        ->where($ref['column'], 'like', '%"'.$id.'"%')
                        ->exists()
                    ) {
                        $foundIds[] = $id;
                    }
                }
            } else {
                // Kolom JSON — gunakan whereJsonContains (1 query per tabel)
                foreach ($ids as $id) {
                    if (DB::table($ref['table'])
                        ->whereJsonContains($ref['column'], $id)
                        ->exists()
                    ) {
                        $foundIds[] = $id;
                    }
                }
            }

            $usedIds = array_merge($usedIds, $foundIds);
        }

        return array_unique($usedIds);
    }

    /**
     * Mendapatkan label rekening untuk pesan error.
     *
     * @param  array<int, int> $ids  Daftar ID rekening
     * @return string                String label "Bank - No.Rek, Bank - No.Rek"
     */
    public function getAccountLabels(array $ids): string
    {
        return PaymentAccount::whereIn('id', $ids)
            ->get()
            ->map(fn($a) => $a->bank_name . ' - ' . $a->account_number)
            ->implode(', ');
    }

    /**
     * Hapus beberapa rekening sekaligus.
     *
     * @param  array<int, int> $ids  Daftar ID rekening
     * @return int                   Jumlah rekening yang dihapus
     */
    public function bulkDelete(array $ids): int
    {
        $result = PaymentAccount::whereIn('id', $ids)->delete();

        $this->flushCache();

        return $result;
    }

    /**
     * Hapus satu rekening pembayaran.
     *
     * @param  \App\Models\Finance\PaymentAccount $account  Model yang akan dihapus
     * @return bool
     */
    public function deleteAccount(PaymentAccount $account): bool
    {
        $result = $account->delete();

        $this->flushCache();

        return $result;
    }

    /**
     * Mengecek apakah satu rekening masih digunakan di tabel lain.
     *
     * @param  \App\Models\Finance\PaymentAccount $account  Rekening yang akan dicek
     * @return bool  true jika masih ada referensi di tabel lain
     */
    public function isAccountUsed(PaymentAccount $account): bool
    {
        return !empty($this->findUsedAccountIds([$account->id]));
    }

    /**
     * Mendapatkan ringkasan penggunaan rekening untuk pesan informasi.
     *
     * Mengembalikan array berisi nama tabel dan jumlah record yang mereferensikan rekening.
     *
     * @param  \App\Models\Finance\PaymentAccount $account  Rekening yang akan dicek
     * @return array<int, array{table: string, count: int}>
     */
    public function getUsageSummary(PaymentAccount $account): array
    {
        $id = $account->id;
        $summary = [];

        // Cek kwintansi (FK langsung)
        $count = DB::table('kwintansi')
            ->where('payment_account_id', $id)
            ->count();
        if ($count > 0) {
            $summary[] = ['table' => 'Kwintansi', 'count' => $count];
        }

        // Cek tabel-tabel JSON
        foreach (self::JSON_REFERENCE_TABLES as $ref) {
            if ($ref['method'] === 'like') {
                $count = DB::table($ref['table'])
                    ->where($ref['column'], 'like', '%"'.$id.'"%')
                    ->count();
            } else {
                $count = DB::table($ref['table'])
                    ->whereJsonContains($ref['column'], $id)
                    ->count();
            }

            if ($count > 0) {
                $label = ucwords(str_replace('_', ' ', $ref['table']));
                $summary[] = ['table' => $label, 'count' => $count];
            }
        }

        return $summary;
    }

    /**
     * Mengecek apakah rekening bisa dihapus (minimal 1 harus tersisa).
     *
     * @return bool  true jika masih ada sisa setelah penghapusan
     */
    public function canDelete(): bool
    {
        return PaymentAccount::where('created_by', auth()->id())->count() > 1;
    }
}
