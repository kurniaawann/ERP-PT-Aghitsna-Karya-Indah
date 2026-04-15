@extends('layouts.guest')

@section('title', 'Reset Kata Sandi | ERP PT Aghitsna Karya Indah')

@section('content')
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <div class="flex flex-col items-center mb-6">
            <div class="bg-primary flex items-center justify-center text-white font-bold text-2xl rounded-md">
                <div class="m-2">
                    ERP PT Aghitsna Karya Indah
                </div>
            </div>
            <h1 class="text-2xl font-semibold mt-4 text-text-primary">Reset Kata Sandi</h1>
            <p class="text-text-secondary text-sm mt-1">Masukkan kata sandi baru Anda</p>
        </div>

        {{-- FORM RESET PASSWORD --}}
        <form action="{{ route('password.update') }}" method="POST" class="space-y-5">
            @csrf

            {{-- TOKEN --}}
            <input type="hidden" name="token" value="{{ $token }}">

            {{-- EMAIL --}}
            <div>
                <label class="block text-text-label text-sm mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" id="email" required
                    autocomplete="email" placeholder="Masukkan email anda"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- KATA SANDI BARU --}}
            <div>
                <label class="block text-text-label text-sm mb-1">Kata Sandi Baru</label>
                <input type="password" name="password" id="password" required autocomplete="new-password"
                    placeholder="Masukkan kata sandi baru (minimal 8 karakter)"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- KONFIRMASI KATA SANDI --}}
            <div>
                <label class="block text-text-label text-sm mb-1">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    autocomplete="new-password" placeholder="Konfirmasi kata sandi baru"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password_confirmation') border-red-500 @enderror">
                @error('password_confirmation')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- TOMBOL RESET --}}
            <button type="submit"
                class="w-full bg-primary text-white font-medium py-2 rounded-lg hover:bg-blue-700 transition-all">
                Reset Kata Sandi
            </button>

            {{-- BACK TO LOGIN --}}
            <div class="text-center">
                <p class="text-text-secondary text-sm">
                    Kembali ke
                    <a href="{{ route('login') }}" class="text-primary font-medium hover:underline">
                        Halaman Login
                    </a>
                </p>
            </div>
        </form>
    </div>
@endsection
