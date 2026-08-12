<?php

namespace App\Services\Report;

use App\Models\Inventory\CementDeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service untuk business logic Laporan Semen (Cement Report).
 *
 * Laporan Semen menggabungkan data DO Semen (header) beserta seluruh
 * baris Data Semen (detail) dalam satu laporan utuh. Menyediakan data
 * untuk tampilan tab "Laporan Semen" di halaman Laporan Akhir serta
 * data untuk export PDF/Excel.
 *
 * Filter yang didukung (sama seperti modul DO Semen):
 * - month : filter berdasarkan bulan tanggal DO
 * - year  : filter berdasarkan tahun tanggal DO (default: tahun berjalan)
 * - search: pencarian berdasarkan nomor DO atau tanggal
 */
class CementReportService
{
    /**
     * Ambil DO Semen beserta detail baris Data Semen dengan filter & paginasi.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return LengthAwarePaginator<CementDeliveryOrder>
     */
    public function getPaginatedDeliveryOrders(Request $request): LengthAwarePaginator
    {
        return $this->buildQuery($request)
            ->with('cements')
            ->orderBy('tanggal', 'desc')
            ->orderBy('no', 'desc')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Ambil seluruh DO Semen beserta detail Data Semen tanpa paginasi (export).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection<int, CementDeliveryOrder>
     */
    public function getAllDeliveryOrders(Request $request)
    {
        return $this->buildQuery($request)
            ->with('cements')
            ->orderBy('tanggal', 'asc')
            ->orderBy('no', 'asc')
            ->get();
    }

    /**
     * Menghitung ringkasan statistik (summary cards).
     *
     * Mengembalikan: jumlah DO, jumlah baris data semen, total volume (zak),
     * total nilai penjualan (subtotal), total harga modal, dan total profit.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function calculateSummary(Request $request): array
    {
        $deliveryOrders = $this->getAllDeliveryOrders($request);

        $summary = [
            'total_do' => $deliveryOrders->count(),
            'total_rows' => 0,
            'total_volume' => 0,
            'total_subtotal' => 0,
            'total_modal' => 0,
            'total_profit' => 0,
        ];

        foreach ($deliveryOrders as $do) {
            $summary['total_rows'] += $do->cements->count();
            $summary['total_volume'] += $do->total_volume;
            $summary['total_subtotal'] += $do->subtotal;
            $summary['total_modal'] += $do->harga_modal;
            $summary['total_profit'] += $do->profit;
        }

        return $summary;
    }

    /**
     * Bangun query dasar dengan filter dari request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function buildQuery(Request $request)
    {
        $query = CementDeliveryOrder::query();

        if ($request->filled('month')) {
            $query->whereMonth('tanggal', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('tanggal', $request->year);
        } else {
            $query->whereYear('tanggal', date('Y'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no', 'like', '%' . $search . '%')
                    ->orWhere('tanggal', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }
}
