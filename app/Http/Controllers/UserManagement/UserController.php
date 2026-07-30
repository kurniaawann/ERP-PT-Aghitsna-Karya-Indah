<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserManagement\StoreUserRequest;
use App\Http\Requests\UserManagement\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagement\UserService;
use App\Traits\HasBulkActions;
use Illuminate\Http\Request;

/**
 * Controller untuk mengelola Modul User Management.
 *
 * Controller ini hanya menangani request dan response HTTP.
 * Business logic didelegasikan ke UserService.
 */
class UserController extends Controller
{
    use HasBulkActions;

    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Menampilkan daftar User dengan paginasi dan pencarian.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $users = $this->userService->getPaginatedSearch($request->input('search'));

        return view('pages.user-management.index', compact('users'));
    }

    /**
     * Menyimpan User baru.
     *
     * @param  StoreUserRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreUserRequest $request)
    {
        $this->userService->store($request->validated());

        return redirect()->back()->with('success', 'User berhasil ditambahkan!');
    }

    /**
     * Memperbarui data User yang sudah ada.
     *
     * @param  UpdateUserRequest  $request
     * @param  User               $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());

        return redirect()->back()->with('success', 'User berhasil diperbarui!');
    }

    /**
     * Menghapus beberapa User sekaligus (bulk delete).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        return $this->destroySelectedBy($request, User::class, 'ids', 'id', 'user-management.index');
    }
}
