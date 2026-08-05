{{-- =====================================================================
     Halaman: Ubah Password (Profile)
     Tujuan: Form untuk mengganti password akun user yang sedang login,
             berisi field Password Saat Ini, Password Baru, dan Konfirmasi.
     Data dari ProfileController@showChangePassword:
     - Tidak ada data khusus; form menggunakan auth()->user() secara implisit
     Route tujuan: profile.change-password.update (PUT)
     Komponen yang di-include:
     - layouts.app
     - partials.password-visibility-toggle (per field password)
     - partials.loading-submit-button (tombol submit dengan loading state)
     JS yang di-load: (tidak ada file khusus, inline dari partials)
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'Ubah Password | ERP PT Aghitsna Karya Indah')

@section('content')
    <div class="max-w-lg mx-auto">
        {{-- ============================================================
             SECTION: FORM GANTI PASSWORD
             Card berisi form yang di-submit ke profile.change-password.update
             dengan method PUT. Setiap field menampilkan pesan error
             (@error) jika validasi gagal di ChangePasswordRequest.
             ============================================================ --}}
        <div class="bg-surface-base rounded-2xl shadow-sm border border-border-light p-8">
            <div class="flex items-center mb-6">
                <div class="w-10 h-10 bg-primary-light rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-lock text-primary"></i>
                </div>
                <div>
                    <h1 class="text-xl font-semibold text-text-heading">Ubah Password</h1>
                    <p class="text-text-secondary text-sm">Pastikan akun Anda tetap aman</p>
                </div>
            </div>

            <form action="{{ route('profile.change-password.update') }}" method="POST" class="space-y-5" id="changePasswordForm">
                @csrf
                @method('PUT')

                {{-- Password Saat Ini --}}
                <div>
                    <label class="block text-text-label text-sm font-medium mb-1">Password Saat Ini</label>
                    <div class="relative">
                        <input type="password" name="current_password" id="current_password" required
                            autocomplete="current-password" placeholder="Masukkan password saat ini"
                            class="w-full px-4 py-2.5 pr-12 rounded-lg border border-border-strong bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary @error('current_password') border-error @enderror">
                        @include('partials.password-visibility-toggle', ['targetId' => 'current_password'])
                    </div>
                    @error('current_password')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password Baru --}}
                <div>
                    <label class="block text-text-label text-sm font-medium mb-1">Password Baru</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                            autocomplete="new-password" placeholder="Masukkan password baru (minimal 8 karakter)"
                            class="w-full px-4 py-2.5 pr-12 rounded-lg border border-border-strong bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary @error('password') border-error @enderror">
                        @include('partials.password-visibility-toggle', ['targetId' => 'password'])
                    </div>
                    @error('password')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi Password Baru --}}
                <div>
                    <label class="block text-text-label text-sm font-medium mb-1">Konfirmasi Password Baru</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                            autocomplete="new-password" placeholder="Konfirmasi password baru"
                            class="w-full px-4 py-2.5 pr-12 rounded-lg border border-border-strong bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary @error('password_confirmation') border-error @enderror">
                        @include('partials.password-visibility-toggle', ['targetId' => 'password_confirmation'])
                    </div>
                    @error('password_confirmation')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tombol Simpan --}}
                @include('partials.loading-submit-button', [
                    'id' => 'changePasswordBtn',
                    'textId' => 'changePasswordBtnText',
                    'spinnerId' => 'changePasswordBtnSpinner',
                    'buttonText' => 'Simpan Perubahan',
                    'buttonType' => 'submit',
                    'buttonClass' =>
                        'w-full bg-primary text-white font-medium py-2.5 rounded-lg hover:bg-primary-hover transition-all inline-flex items-center justify-center gap-2',
                ])
            </form>
        </div>
    </div>
@endsection
