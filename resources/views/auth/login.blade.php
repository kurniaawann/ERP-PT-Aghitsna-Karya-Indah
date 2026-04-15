@extends('layouts.guest')

@section('title', 'Login | ERP PT Aghitsna Karya Indah')

@section('content')
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <div class="flex flex-col items-center mb-6">
            <div class="bg-primary flex items-center justify-center text-white font-bold text-2xl rounded-md">
                <div class="m-2">
                    ERP PT Aghitsna Karya Indah
                </div>
            </div>
            <h1 class="text-2xl font-semibold mt-4 text-text-primary">Masuk ke Akun</h1>
            <p class="text-text-secondary text-sm mt-1">Silakan login untuk melanjutkan</p>
        </div>

        {{-- FORM LOGIN --}}
        <form action="{{ route('login.post') }}" method="POST" class="space-y-5" autocomplete="on">
            @csrf

            {{-- EMAIL --}}
            <div>
                <label class="block text-text-label text-sm mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', 'superadmin@example.com') }}" id="email"
                    required autocomplete="username" placeholder="Masukkan email anda"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- PASSWORD --}}
            <div>
                <label class="block text-text-label text-sm mb-1">Kata Sandi</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                    value="{{ old('password', 'password123') }}" placeholder="Masukkan kata sandi anda"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <div class="text-right mt-2">
                    <a href="{{ route('password.request') }}" class="text-primary text-sm hover:underline">
                        Lupa kata sandi?
                    </a>
                </div>
            </div>

            {{-- TOMBOL LOGIN --}}
            <button type="submit" id="loginBtn"
                class="w-full bg-primary text-white font-medium py-2 rounded-lg hover:bg-blue-700 transition-all">
                Masuk
            </button>
        </form>
    </div>
@endsection
