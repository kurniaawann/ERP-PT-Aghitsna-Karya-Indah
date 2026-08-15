<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Cement;
use App\Models\Inventory\CementDeliveryOrder;
use App\Services\InputNormalizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service untuk logika bisnis DO Semen (Delivery Order Semen) - HEADER.
 *
 * Menangani pencarian dengan paginasi, penyimpanan, pembaruan, dan
 * penghapusan massal DO semen beserta baris detailnya (master-detail).
 * Satu DO dapat memiliki banyak baris Data Semen (cements).
 */
class CementDeliveryOrderService
{
    /**
     * Ambil data DO Semen dengan pencarian dan filter bulan/tahun.
     *
     * @param  string|null  $search
     * @param  string|null  $month
     * @param  string|null  $year
     * @return LengthAwarePaginator<CementDeliveryOrder>
     */
    public function getPaginatedSearch(?string $search = null, ?string $month = null, ?string $year = null): LengthAwarePaginator
    {
        return CementDeliveryOrder::with('cements')
            ->search($search)
            ->filterMonth($month)
            ->filterYear($year)
            ->orderBy('tanggal', 'desc')
            ->orderBy('no', 'desc')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Ambil seluruh data DO Semen untuk keperluan export (tanpa paginasi).
     *
     * @param  string|null  $search
     * @return Collection<int, CementDeliveryOrder>
     */
    public function getAll(?string $search = null): Collection
    {
        return CementDeliveryOrder::with('cements')
            ->search($search)
            ->orderBy('no', 'asc')
            ->get();
    }

    /**
     * Ambil seluruh data DO Semen dan kelompokkan per bulan (untuk laporan).
     *
     * Key grup berupa nama bulan Indonesia + tahun, mis. "Januari 2026".
     * Dipakai oleh view export PDF DO Semen.
     *
     * @return Collection<string, Collection<int, CementDeliveryOrder>>
     */
    public function getGroupedByMonth(): Collection
    {
        $bulanIndo = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];

        return $this->getAll()->groupBy(function (CementDeliveryOrder $do) use ($bulanIndo) {
            if (!$do->tanggal) {
                return 'Tanpa Tanggal';
            }

            return $bulanIndo[$do->tanggal->format('m')] . ' ' . $do->tanggal->format('Y');
        });
    }

    /**
     * Ambil satu data DO Semen berdasarkan nomor.
     *
     * @param  string  $no
     * @return CementDeliveryOrder|null
     */
    public function findById(string $no): ?CementDeliveryOrder
    {
        return CementDeliveryOrder::with('cements')->whereKey($no)->first();
    }

    /**
     * Simpan DO Semen baru beserta baris Data Semen (detail) di dalamnya.
     *
     * @param  array<string, mixed>  $data
     * @return CementDeliveryOrder
     */
    public function store(array $data): CementDeliveryOrder
    {
        return DB::transaction(function () use ($data) {
            $cementDeliveryOrder = CementDeliveryOrder::create([
                'no' => CementDeliveryOrder::generateNextNo($data['tanggal'] ?? null),
                'tanggal' => $data['tanggal'] ?? null,
                'tanggal_datang' => $data['tanggal_datang'] ?? null,
                'tanggal_bayar' => $data['tanggal_bayar'] ?? null,
                'harga_modal' => InputNormalizer::normalizeCurrency($data['harga_modal']),
            ]);

            $this->syncCements($cementDeliveryOrder, $data['cements'] ?? []);

            return $cementDeliveryOrder;
        });
    }

    /**
     * Perbarui DO Semen beserta baris Data Semen (detail) di dalamnya.
     *
     * Baris detail lama dihapus lalu dibuat ulang dari input form sehingga
     * kontennya selalu sinkron dengan apa yang dikirim.
     *
     * @param  CementDeliveryOrder   $cementDeliveryOrder
     * @param  array<string, mixed>  $data
     * @return bool
     */
    public function update(CementDeliveryOrder $cementDeliveryOrder, array $data): bool
    {
        return DB::transaction(function () use ($cementDeliveryOrder, $data) {
            $cementDeliveryOrder->update([
                'tanggal' => $data['tanggal'] ?? null,
                'tanggal_datang' => $data['tanggal_datang'] ?? null,
                'tanggal_bayar' => $data['tanggal_bayar'] ?? null,
                'harga_modal' => InputNormalizer::normalizeCurrency($data['harga_modal']),
            ]);

            $cementDeliveryOrder->cements()->delete();
            $this->syncCements($cementDeliveryOrder, $data['cements'] ?? []);

            return true;
        });
    }

    /**
     * Buat ulang baris Data Semen (detail) untuk sebuah DO.
     *
     * @param  CementDeliveryOrder     $cementDeliveryOrder
     * @param  array<int, array<string, mixed>>  $rows
     * @return void
     */
    private function syncCements(CementDeliveryOrder $cementDeliveryOrder, array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['nama_proyek']) && empty($row['jumlah'])) {
                continue;
            }

            Cement::create([
                'no' => Cement::generateNextNo(),
                'do_no' => $cementDeliveryOrder->no,
                'tanggal' => $row['tanggal'] ?? null,
                'nama_proyek' => $row['nama_proyek'],
                'name' => $row['name'] ?? null,
                'jumlah' => (int) ($row['jumlah'] ?? 0),
                'satuan' => $row['satuan'] ?? 'zak',
                'harga' => InputNormalizer::normalizeCurrency($row['harga'] ?? 0),
                'tanggal_lunas' => $row['tanggal_lunas'] ?? null,
            ]);
        }
    }

    /**
     * Hapus data DO Semen secara massal berdasarkan nomor terpilih.
     *
     * @param  array<int, string>  $nos
     * @return int  Jumlah data yang berhasil dihapus.
     */
    public function destroySelected(array $nos): int
    {
        if (empty($nos)) {
            return 0;
        }

        return CementDeliveryOrder::whereIn('no', $nos)->delete();
    }
}
