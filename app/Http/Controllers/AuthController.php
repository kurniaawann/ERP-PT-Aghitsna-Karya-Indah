<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use App\Models\User;

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
            $request->session()->regenerate();

            return redirect(Auth::user()->getHomeRoute());
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

    // ===== FORGOT PASSWORD METHODS =====

    /**
     * Tampilkan form forgot password
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Kirim link reset password ke email
     */
    public function sendResetLink(Request $request)
    {
        // Validasi email
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Tambahkan validasi: email harus ada di tabel users
        $email = $request->input('email');
        $userExists = \App\Models\User::where('email', $email)->exists();

        if (!$userExists) {
            return back()->withErrors([
                'email' => 'Email tidak terdaftar dalam sistem kami.',
            ])->withInput();
        }

        // Kirim password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Jika berhasil
        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Link reset password telah dikirim ke email Anda!');
        }

        return back()->withErrors([
            'email' => 'Gagal mengirim link reset password. Silakan coba lagi.',
        ])->withInput();
    }

    /**
     * Tampilkan form reset password
     */
    public function showResetForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Handle reset password
     */
    public function resetPassword(Request $request)
    {
        // Validasi input
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        // Reset password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Update password
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                // Trigger event untuk logging atau activity lainnya
                event(new PasswordReset($user));
            }
        );

        // Jika berhasil
        if ($status === Password::PASSWORD_RESET) {
            return redirect('/login')->with('status', 'Password berhasil direset! Silakan login dengan password baru Anda.');
        }

        // Jika gagal
        return back()->withErrors([
            'email' => 'Gagal mereset password. Token mungkin sudah kadaluarsa.',
        ]);
    }
}
