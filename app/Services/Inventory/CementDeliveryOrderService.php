<?php

namespace App\Services\Inventory;

use App\Models\Inventory\CementDeliveryOrder;
use App\Services\InputNormalizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service untuk logika bisnis DO Semen (Delivery Order Semen).
 *
 * Menangani pencarian dengan paginasi, penyimpanan, pembaruan, dan
 * penghapusan massal data DO semen. Modul ini berdiri sendiri dan tidak
 * memiliki relasi ke modul lain.
 */
class CementDeliveryOrderService
{
    /**
     * Ambil data DO Semen dengan pencarian dan paginasi.
     *
     * @param  string|null  $search
     * @return LengthAwarePaginator<CementDeliveryOrder>
     */
    public function getPaginatedSearch(?string $search = null): LengthAwarePaginator
    {
        return CementDeliveryOrder::search($search)
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
        return CementDeliveryOrder::search($search)
            ->orderBy('tanggal', 'asc')
            ->orderBy('no', 'asc')
            ->get();
    }

    /**
     * Ambil satu data DO Semen berdasarkan nomor.
     *
     * @param  string  $no
     * @return CementDeliveryOrder|null
     */
    public function findById(string $no): ?CementDeliveryOrder
    {
        return CementDeliveryOrder::whereKey($no)->first();
    }

    /**
     * Simpan data DO Semen baru.
     *
     * @param  array<string, mixed>  $data
     * @return CementDeliveryOrder
     */
    public function store(array $data): CementDeliveryOrder
    {
        return CementDeliveryOrder::create([
            'no' => CementDeliveryOrder::generateNextNo(),
            'tanggal' => $data['tanggal'] ?? null,
            'proyek' => $data['proyek'],
            'volume' => (int) $data['volume'],
            'satuan' => $data['satuan'] ?? null,
            'harga' => InputNormalizer::normalizeCurrency($data['harga']),
            'tanggal_lunas' => $data['tanggal_lunas'] ?? null,
            'harga_modal' => InputNormalizer::normalizeCurrency($data['harga_modal']),
        ]);
    }

    /**
     * Perbarui data DO Semen yang ada.
     *
     * @param  CementDeliveryOrder  $cementDeliveryOrder
     * @param  array<string, mixed> $data
     * @return bool
     */
    public function update(CementDeliveryOrder $cementDeliveryOrder, array $data): bool
    {
        return $cementDeliveryOrder->update([
            'tanggal' => $data['tanggal'] ?? null,
            'proyek' => $data['proyek'],
            'volume' => (int) $data['volume'],
            'satuan' => $data['satuan'] ?? null,
            'harga' => InputNormalizer::normalizeCurrency($data['harga']),
            'tanggal_lunas' => $data['tanggal_lunas'] ?? null,
            'harga_modal' => InputNormalizer::normalizeCurrency($data['harga_modal']),
        ]);
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
