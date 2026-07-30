<?php

namespace App\Http\Requests\UserManagement;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request untuk validasi penyimpanan User baru.
 *
 * Memastikan semua field wajib terisi, email unik,
 * password minimal 8 karakter, dan role valid.
 */
class StoreUserRequest extends FormRequest
{
    /**
     * Menentukan apakah request ini diizinkan.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk penyimpanan User baru.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', array_keys(User::ROLES)),
        ];
    }

    /**
     * Custom error messages dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tidak boleh kosong.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email tidak boleh kosong.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan oleh user lain.',
            'password.required' => 'Password tidak boleh kosong.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Role tidak boleh kosong.',
            'role.in' => 'Role tidak valid.',
        ];
    }
}
