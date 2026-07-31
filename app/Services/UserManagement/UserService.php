<?php

namespace App\Services\UserManagement;

use App\Models\User;
use App\Repositories\UserManagement\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Service untuk mengelola business logic User Management.
 *
 * Service ini bertanggung jawab atas operasi CRUD user,
 * termasuk pembuatan password hash dan delegasi query ke Repository.
 *
 * KONSEP: Service ini tipis — seluruh query memakai UserRepository agar
 * akses data (filter, pagination, delete) terpusat di satu lapisan.
 */
class UserService
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Mendapatkan daftar user dengan paginasi dan pencarian.
     *
     * Logika: semua filter/pagination didelegasikan ke repository->search().
     * Service tidak memegang query langsung — pemanggil cukup memberikan
     * kata kunci pencarian (nama/email).
     *
     * @param  string|null  $search  Kata kunci pencarian (nama atau email)
     * @return LengthAwarePaginator
     */
    public function getPaginatedSearch(?string $search): LengthAwarePaginator
    {
        return $this->repository->search($search);
    }

    /**
     * Menyimpan user baru dengan password yang sudah di-hash.
     *
     * Logika:
     * - Password di-hash di sini (bcrypt) di sisi server — nilai password
     *   mentah dari request TIDAK pernah disimpan langsung.
     * - Hanya kolom name/email/password/role yang dipetakan; kolom lain
     *   (misal email_verified_at) dibiarkan default.
     *
     * @param  array  $data  Data yang sudah divalidasi
     * @return User
     */
    public function store(array $data): User
    {
        return $this->repository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
        ]);
    }

    /**
     * Memperbarui data user yang sudah ada.
     *
     * Logika: password TIDAK termasuk dalam array update — ganti password
     * memerlukan alur tersendiri. Update hanya menyentuh name/email/role.
     *
     * @param  User   $user  Model user yang akan diperbarui
     * @param  array  $data  Data yang sudah divalidasi
     * @return bool
     */
    public function update(User $user, array $data): bool
    {
        return $this->repository->update($user, [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);
    }

    /**
     * Menghapus beberapa user sekaligus (bulk delete).
     *
     * Logika: mass delete (whereIn) didelegasikan ke repository->deleteMany().
     * User tidak punya observer/efek samping per-record, jadi delete massal
     * aman dan lebih efisien daripada loop per model.
     *
     * @param  array  $ids  Daftar ID user yang akan dihapus
     * @return int  Jumlah record yang dihapus
     */
    public function destroySelected(array $ids): int
    {
        return $this->repository->deleteMany($ids);
    }
}
