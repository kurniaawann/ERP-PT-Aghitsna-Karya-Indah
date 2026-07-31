<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Division;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengelola bisnis logika divisi.
 *
 * Menangani daftar divisi, pembuatan, pembaruan, penghapusan,
 * dan semua bisnis logika yang bukan bagian dari model atau controller.
 */
class DivisionService
{
    /**
     * Mendapatkan daftar divisi dengan paginasi, jumlah karyawan, dan pencarian opsional.
     *
     * Menggunakan withCount untuk menghindari query N+1 saat menampilkan jumlah karyawan.
     * Pencarian dibatasi pada kolom name dan description dengan pengelompokan yang tepat.
     *
     * Logika:
     * - withCount('employees') menambahkan kolom agregat employees_count lewat
     *   subquery — tanpa perlu query terpisah per divisi.
     * - Pencarian dibungkus closure + grup WHERE agar OR antara name/description
     *   tidak membatalkan kondisi lain.
     *
     * @param  string|null  $search
     * @param  int          $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedDivisions(?string $search, int $perPage = 15): LengthAwarePaginator
{
    return Division::withCount('employees')
        ->where('created_by', auth()->id())
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        })
        ->latest('created_at')
        ->paginate($perPage);
}

    /**
     * Membuat divisi baru.
     *
     * Logika: created_by selalu di-set dari user yang login; pemanggil tidak
     * bisa memalsukan pembuat lewat data request. Pemanggil wajib memanggil
     * flushCache() setelahnya agar dropdown divisi tidak basi.
     *
     * @param  array  $data  Data divisi yang sudah divalidasi (name, description)
     * @return Division
     */
    public function createDivision(array $data): Division
    {
        $data['created_by'] = auth()->id();
        return Division::create($data);
    }

    /**
     * Memperbarui divisi yang sudah ada.
     *
     * @param  Division  $division
     * @param  array     $data  Data divisi yang sudah divalidasi (name, description)
     * @return bool
     */
    public function updateDivision(Division $division, array $data): bool
    {
        return $division->update($data);
    }

    /**
     * Memeriksa apakah ada divisi yang memiliki karyawan terkait.
     *
     * Mengembalikan nama divisi yang masih memiliki karyawan,
     * sehingga pemanggil dapat menampilkan pesan kesalahan yang mudah dipahami.
     *
     * Logika:
     * - whereHas('employees') = EXISTS subquery, hanya divisi dengan minimal
     *   satu karyawan yang diambil — pengecekan constraint sebelum delete.
     * - pluck('name') mengembalikan daftar nama (bukan object) agar langsung
     *   bisa ditampilkan di pesan error.
     *
     * @param  array<int>  $ids  ID divisi yang akan diperiksa
     * @return array<string>  Nama divisi yang memiliki karyawan
     */
    public function getDivisionsWithEmployees(array $ids): array
    {
        return Division::whereIn('id', $ids)
            ->whereHas('employees')
            ->pluck('name')
            ->toArray();
    }

    /**
     * Menghapus divisi berdasarkan ID-nya.
     *
     * @param  array<int>  $ids
     * @return int  Jumlah data yang dihapus
     */
    public function deleteDivisions(array $ids): int
    {
        return Division::whereIn('id', $ids)->delete();
    }

    public function flushCache(): void
    {
        try {
            Cache::forget('sdm:divisions:dropdown');
        } catch (\Exception $e) {
            Log::warning('Cache DELETE error [sdm:divisions:dropdown]: ' . $e->getMessage());
        }
    }
}
