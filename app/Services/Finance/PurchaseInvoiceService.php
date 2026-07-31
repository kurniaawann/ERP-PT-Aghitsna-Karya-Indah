<?php

namespace App\Services\Finance;

use App\Models\Finance\PurchaseInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Service layer untuk Faktur Pembelian.
 *
 * Menangani business logic terkait pembuatan, update, dan
 * pengambilan data faktur pembelian, termasuk perhitungan PPN
 * dan filter query yang digunakan di banyak tempat.
 */
class PurchaseInvoiceService
{
    /**
     * Hitung PPN berdasarkan harga jual dan persentase.
     *
     * @param  int   $sellingPrice  Harga jual
     * @param  float $ppnPercentage Persentase PPN (0-100)
     * @return int   Nilai PPN
     */
    public static function calculatePpnTax(int $sellingPrice, float $ppnPercentage): int
    {
        return (int) round($sellingPrice * $ppnPercentage / 100);
    }

    /**
     * Build query dengan filter search, month, year.
     *
     * Digunakan oleh controller index(), exportPdf(), dan export Excel
     * untuk menghindari duplikasi logika filter.
     *
     * @param  Request|null $request  Request yang berisi parameter filter
     * @return Builder
     */
    public static function buildFilteredQuery(?Request $request = null): Builder
    {
        $query = PurchaseInvoice::query();

        if (!$request) {
            return $query;
        }

        $search = $request->input('search');
        $month  = $request->input('month');
        $year   = $request->input('year');

        return $query->search($search)
            ->filterByMonth($month)
            ->filterByYear($year);
    }

    /**
     * Create faktur pembelian baru dari validated data.
     *
     * @param  array<string, mixed> $validated  Data yang sudah validasi
     * @return PurchaseInvoice
     */
    public static function createInvoice(array $validated): PurchaseInvoice
    {
        $validated['ppn_tax'] = self::calculatePpnTax(
            (int) $validated['selling_price'],
            (float) $validated['ppn_percentage']
        );

        return PurchaseInvoice::create($validated);
    }

    /**
     * Update faktur pembelian dari validated data.
     *
     * @param  PurchaseInvoice     $invoice   Model yang akan diupdate
     * @param  array<string, mixed> $validated Data yang sudah validasi
     * @return PurchaseInvoice
     */
    public static function updateInvoice(PurchaseInvoice $invoice, array $validated): PurchaseInvoice
    {
        $validated['ppn_tax'] = self::calculatePpnTax(
            (int) $validated['selling_price'],
            (float) $validated['ppn_percentage']
        );

        $invoice->update($validated);

        return $invoice;
    }

    /**
     * Menghapus beberapa faktur pembelian sekaligus (bulk delete).
     *
     * @param  array  $ids  Daftar ID faktur pembelian yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public static function deleteSelected(array $ids): int
    {
        return PurchaseInvoice::whereIn('id', $ids)->delete();
    }
}
