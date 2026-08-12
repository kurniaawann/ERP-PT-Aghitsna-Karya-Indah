<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Cement;
use App\Services\InputNormalizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Service untuk logika bisnis Data Semen (baris detail dari DO Semen).
 *
 * Menangani pencarian dengan paginasi, penyimpanan, pembaruan, dan
 * penghapusan massal data semen. Setiap baris data semen terikat ke
 * sebuah DO Semen melalui kolom do_no (master-detail).
 */
class CementService
{
    /**
     * Ambil data semen dengan pencarian dan paginasi.
     *
     * @param  string|null  $search
     * @return LengthAwarePaginator<Cement>
     */
    public function getPaginatedSearch(?string $search = null): LengthAwarePaginator
    {
        return Cement::with('deliveryOrder')
            ->search($search)
            ->orderBy('tanggal', 'desc')
            ->orderBy('no', 'desc')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Ambil seluruh data semen untuk keperluan export (tanpa paginasi).
     *
     * @param  string|null  $search
     * @return Collection<int, Cement>
     */
    public function getAll(?string $search = null): Collection
    {
        return Cement::with('deliveryOrder')
            ->search($search)
            ->orderBy('tanggal', 'asc')
            ->orderBy('no', 'asc')
            ->get();
    }

    /**
     * Ambil satu data semen berdasarkan nomor.
     *
     * @param  string  $no
     * @return Cement|null
     */
    public function findById(string $no): ?Cement
    {
        return Cement::whereKey($no)->first();
    }

    /**
     * Simpan data semen baru.
     *
     * @param  array<string, mixed>  $data
     * @return Cement
     */
    public function store(array $data): Cement
    {
        return Cement::create([
            'no' => Cement::generateNextNo(),
            'do_no' => $data['do_no'] ?? null,
            'tanggal' => $data['tanggal'] ?? null,
            'nama_proyek' => $data['nama_proyek'],
            'jumlah' => (int) $data['jumlah'],
            'satuan' => $data['satuan'] ?? 'zak',
            'harga' => InputNormalizer::normalizeCurrency($data['harga']),
            'tanggal_lunas' => $data['tanggal_lunas'] ?? null,
        ]);
    }

    /**
     * Perbarui data semen yang ada.
     *
     * @param  Cement              $cement
     * @param  array<string, mixed> $data
     * @return bool
     */
    public function update(Cement $cement, array $data): bool
    {
        return $cement->update([
            'do_no' => $data['do_no'] ?? $cement->do_no,
            'tanggal' => $data['tanggal'] ?? null,
            'nama_proyek' => $data['nama_proyek'],
            'jumlah' => (int) $data['jumlah'],
            'satuan' => $data['satuan'] ?? $cement->satuan,
            'harga' => InputNormalizer::normalizeCurrency($data['harga']),
            'tanggal_lunas' => $data['tanggal_lunas'] ?? null,
        ]);
    }

    /**
     * Hapus data semen secara massal berdasarkan nomor terpilih.
     *
     * @param  array<int, string>  $nos
     * @return int  Jumlah data yang berhasil dihapus.
     */
    public function destroySelected(array $nos): int
    {
        if (empty($nos)) {
            return 0;
        }

        return Cement::whereIn('no', $nos)->delete();
    }
}
