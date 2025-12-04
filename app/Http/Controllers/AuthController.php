<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Return view halaman login (resources/views/auth/login.blade.php)
        // Method ini menampilkan form login untuk user yang belum terautentikasi
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // Validasi input dari form login dengan Laravel validation
        // validate() akan otomatis redirect kembali dengan error jika validasi gagal
        $credentials = $request->validate([
            // Field email wajib diisi dan harus format email yang valid
            'email' => ['required', 'email'],
            // Field password wajib diisi (tidak ada validasi panjang minimal di sini)
            'password' => ['required'],
        ]);

        // Attempt login dengan credentials (email + password)
        // Auth::attempt() akan return true jika credentials cocok dengan data di database
        // Secara otomatis akan hash password dan compare dengan yang tersimpan
        if (Auth::attempt($credentials)) {
            // Jika login berhasil:
            // Regenerate session ID untuk keamanan (mencegah session fixation attack)
            // Session fixation attack = attacker mencoba memakai session ID lama untuk hijack session
            $request->session()->regenerate();

            // Redirect ke intended URL (URL yang user coba akses sebelum diredirect ke login)
            // Jika tidak ada intended URL, default redirect ke '/dashboard'
            // intended() sangat berguna untuk UX, misal user coba akses /employee, akan redirect balik ke /employee setelah login
            return redirect()->intended('/dashboard');
        }

        // Jika login gagal (credentials tidak cocok):
        // Redirect kembali ke halaman login dengan error message
        // withErrors() akan menambahkan error ke session dan bisa ditampilkan di view
        return back()->withErrors([
            // Error message ditampilkan di field 'email' (bisa diakses di view dengan $errors->get('email'))
            'email' => 'Email atau password salah.',
        ]);
    }

    public function logout(Request $request)
    {
        // Logout user dari sistem (clear authentication state)
        // Auth::logout() akan remove user dari session dan guard
        Auth::logout();

        // Invalidate session (hapus semua data session dari server)
        // Penting untuk security, agar session lama tidak bisa dipakai lagi
        $request->session()->invalidate();

        // Regenerate CSRF token untuk keamanan
        // CSRF token = token untuk mencegah Cross-Site Request Forgery attack
        // Regenerate token setelah logout agar token lama tidak bisa dipakai untuk request
        $request->session()->regenerateToken();

        // Redirect ke halaman login
        return redirect('/login');
    }
}
