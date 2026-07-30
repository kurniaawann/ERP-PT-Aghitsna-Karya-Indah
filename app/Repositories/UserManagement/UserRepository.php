<?php

namespace App\Repositories\UserManagement;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repository untuk akses data User.
 *
 * Menangani query database terkait data user menggunakan Eloquent.
 * Scope pencarian delegasi ke Model scope untuk menghindari duplikasi.
 */
class UserRepository
{
    /**
     * Mencari user dengan paginasi berdasarkan kata kunci.
     * Menggunakan scope search dari Model User.
     *
     * @param  string|null  $search  Kata kunci pencarian (nama atau email)
     * @return LengthAwarePaginator
     */
    public function search(?string $search): LengthAwarePaginator
    {
        return User::query()
            ->search($search)
            ->latest()
            ->paginate(15);
    }

    /**
     * Membuat data user baru.
     *
     * @param  array  $data  Data user yang sudah divalidasi
     * @return User
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Memperbarui data user yang sudah ada.
     *
     * @param  User  $user  Model user yang akan diperbarui
     * @param  array $data  Data yang sudah divalidasi
     * @return bool
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }
}
