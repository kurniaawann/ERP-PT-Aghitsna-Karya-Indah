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
 */
class UserService
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    /**
     * Mendapatkan daftar user dengan paginasi dan pencarian.
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
}
