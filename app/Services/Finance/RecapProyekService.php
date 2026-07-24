<?php

namespace App\Services\Finance;

use App\Models\Finance\InvoiceProyek;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Service untuk Rekap Proyek.
 *
 * Menangani seluruh business logic Rekap Proyek:
 * - Pembangunan query dasar dengan filter
 * - Perhitungan total rekap
 * - Pembangunan judul periode
 */
class RecapProyekService
{
    public function __construct(
        protected InvoiceCalculatorService $calculator
    ) {}

    /**
     * Membangun query dasar untuk data invoice proyek.
     *
     * Query ini digunakan oleh index, export excel, dan export pdf.
     * Sudah termasuk eager loading relasi paymentProofs.
     *
     * @param  \Illuminate\Http\Request  $request  Request dengan filter: search, month, year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildBaseQuery(Request $request): Builder
    {
        return InvoiceProyek::query()
            ->with('paymentProofs')
            ->where('created_by', auth()->id())
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = $request->search;
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('recipient', 'like', "%{$search}%")
                        ->orWhere('regarding', 'like', "%{$search}%")
                        ->orWhere('project_description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('month'), function (Builder $query) use ($request) {
                $query->whereMonth('invoice_date', $request->month);
            })
            ->when($request->filled('year'), function (Builder $query) use ($request) {
                $query->whereYear('invoice_date', $request->year);
            });
    }

    /**
     * Mengambil data invoice untuk tampilan paginated.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query dasar
     * @param  \Illuminate\Http\Request  $request  Request untuk pagination
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginatedInvoices(Builder $query, Request $request)
    {
        return (clone $query)
            ->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->appends($request->all());
    }

    /**
     * Mengambil seluruh data invoice (tanpa pagination) untuk perhitungan total.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query  Query dasar
     * @return \Illuminate\Support\Collection
     */
    public function getAllInvoices(Builder $query)
    {
        return (clone $query)
            ->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Membangun ringkasan total dari koleksi invoice.
     *
     * @param  \Illuminate\Support\Collection  $invoices  Koleksi invoice proyek
     * @return object  Objek berisi: invoice_count, total_invoice, total_paid, total_remaining, paid_count, unpaid_count
     */
    public function buildTotals($invoices): object
    {
        return $this->calculator->buildProyekTotals($invoices);
    }

    /**
     * Membangun judul periode berdasarkan filter request.
     *
     * @param  \Illuminate\Http\Request  $request  Request dengan filter: month, year
     * @return string  Judul periode (contoh: "JUNI 2024", "SEMUA PERIODE")
     */
    public function buildPeriodTitle(Request $request): string
    {
        $month = $request->get('month');
        $year = $request->get('year');

        if ($month && $year) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');

            return strtoupper($monthName) . ' ' . $year;
        }

        if ($month) {
            $monthName = Carbon::create(null, $month, 1)->locale('id')->translatedFormat('F');

            return 'BULAN ' . strtoupper($monthName);
        }

        if ($year) {
            return 'TAHUN ' . $year;
        }

        return 'SEMUA PERIODE';
    }
}
