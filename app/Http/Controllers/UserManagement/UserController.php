<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($q, $search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })
            ->latest()
            ->paginate(10);

        return view('pages.user-management.index', compact('users', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:' . implode(',', array_keys(User::ROLES)),
        ]);

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => $request->input('is_active', 0) == 1,
        ]);

        return redirect()->route('user-management.index')->with('success', 'User berhasil ditambahkan!');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required|in:' . implode(',', array_keys(User::ROLES)),
        ]);

        $data = [
            'name'  => $request->name,
            'email' => $request->email,
            'role'  => $request->role,
        ];

        // Prevent deactivating own account via form
        if ($user->id !== auth()->id()) {
            $data['is_active'] = $request->input('is_active', 0) == 1;
        }

        $user->update($data);

        return redirect()->route('user-management.index')->with('success', 'User berhasil diperbarui!');
    }

    public function destroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('user-management.index')->with('error', 'Tidak ada user yang dipilih!');
        }

        $ids = array_values(array_filter($ids, fn($id) => $id !== auth()->id()));

        if (!empty($ids)) {
            User::whereIn('id', $ids)->delete();
        }

        return redirect()->route('user-management.index')->with('success', 'User berhasil dihapus!');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('user-management.index')->with('error', 'Tidak dapat mengubah status akun sendiri!');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('user-management.index')->with('success', "User berhasil {$status}!");
    }
}
