@extends('layouts.guest')

@section('title', 'Login | ERP PT Aghitsna Karya Indah')

@section('content')
    <div class="w-full max-w-md bg-surface-base rounded-2xl shadow-lg border border-border-light p-8">
        <div class="flex flex-col items-center mb-6">
            <div
                class="bg-primary flex items-center justify-center text-white font-bold text-base rounded-md px-4 py-3 text-center">
                <div>
                    ERP PT Aghitsna Karya Indah
                </div>
            </div>
            <h1 class="text-xl font-semibold mt-4 text-text-primary">Masuk ke Akun</h1>
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
                    class="w-full px-4 py-2 rounded-lg border border-border-strong bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary">
                @error('email')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- PASSWORD --}}
            <div class="relative">
                <label class="block text-text-label text-sm mb-1">Kata Sandi</label>
                <input type="password" name="password" id="password" required autocomplete="current-password"
                    value="{{ old('password', 'password123') }}" placeholder="Masukkan kata sandi anda"
                    class="w-full px-4 py-2 pr-12 rounded-lg border border-border-strong bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary">
                @include('partials.password-visibility-toggle', ['targetId' => 'password'])
                @error('password')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
                <div class="text-right mt-2">
                    <a href="{{ route('password.request') }}" class="text-primary text-sm hover:underline">
                        Lupa kata sandi?
                    </a>
                </div>
            </div>


            {{-- TOMBOL LOGIN --}}
            <button type="submit" id="loginBtn"
                class="w-full bg-primary text-white font-medium py-2 rounded-lg hover:bg-primary-hover transition-all">
                Masuk
            </button>
        </form>
    </div>
@endsection
