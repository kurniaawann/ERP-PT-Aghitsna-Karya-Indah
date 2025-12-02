<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller untuk mengelola autentikasi pengguna.
 * 
 * Menangani proses login dan logout untuk sistem ERP.
 */
class AuthController extends Controller
{
    /**
     * Menampilkan halaman form login.
     * 
     * Return view halaman login untuk user yang belum terautentikasi.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Memproses login pengguna.
     * 
     * Proses:
     * 1. Validasi input email dan password
     * 2. Attempt login dengan credentials yang diberikan
     * 3. Jika berhasil: regenerate session dan redirect ke dashboard
     * 4. Jika gagal: kembali ke form login dengan error message
     * 
     * Security:
     * - Session regeneration untuk mencegah session fixation
     * - Redirect ke intended URL jika ada (sebelum login dipaksa)
     */
    public function login(Request $request)
    {
        // Validasi input dari form login
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Attempt login dengan credentials
        if (Auth::attempt($credentials)) {
            // Regenerate session ID untuk keamanan
            $request->session()->regenerate();
            // Redirect ke intended page atau default ke dashboard
            return redirect()->intended('/dashboard');
        }

        // Jika login gagal, kembali dengan error
        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ]);
    }

    /**
     * Memproses logout pengguna.
     * 
     * Proses:
     * 1. Logout user dari sistem (clear authentication)
     * 2. Invalidate session (hapus semua data session)
     * 3. Regenerate CSRF token untuk keamanan
     * 4. Redirect ke halaman login
     * 
     * Security:
     * - Session invalidation untuk clear semua data
     * - Token regeneration untuk mencegah CSRF attack
     */
    public function logout(Request $request)
    {
        // Logout user dari Auth
        Auth::logout();
        // Invalidate session (hapus semua data session)
        $request->session()->invalidate();
        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Redirect ke halaman login
        return redirect('/login');
    }
}
