@extends('layouts.guest')

@section('title', 'Reset Kata Sandi | ERP PT Aghitsna Karya Indah')

@section('content')
    <div class="w-full max-w-md bg-surface-base rounded-2xl shadow-lg border border-border-light p-8">
        <div class="flex flex-col items-center mb-6">
            <img src="{{ asset('images/logo.jpeg') }}" alt="Logo PT Aghitsna Karya Indah"
                class="h-20 w-auto object-contain">
            <h1 class="text-2xl font-semibold mt-4 text-text-primary">Reset Kata Sandi</h1>
            <p class="text-text-secondary text-sm mt-1">Masukkan kata sandi baru Anda</p>
        </div>

        {{-- FORM RESET PASSWORD --}}
        <form action="{{ route('password.update') }}" method="POST" class="space-y-5" id="resetPasswordForm">
            @csrf

            {{-- TOKEN --}}
            <input type="hidden" name="token" value="{{ $token }}">

            {{-- EMAIL --}}
            <div>
                <label class="block text-text-label text-sm mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" id="email" required
                    autocomplete="email" placeholder="Masukkan email anda"
                    class="w-full px-4 py-2 rounded-lg border border-border-strong bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary @error('email') border-error @enderror">
                @error('email')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- KATA SANDI BARU --}}
            <div class="relative">
                <label class="block text-text-label text-sm mb-1">Kata Sandi Baru</label>
                <input type="password" name="password" id="password" required autocomplete="new-password"
                    placeholder="Masukkan kata sandi baru (minimal 8 karakter)"
                    class="w-full px-4 py-2 pr-12 rounded-lg border border-border-strong bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary @error('password') border-error @enderror">
                @include('partials.password-visibility-toggle', ['targetId' => 'password'])
                @error('password')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- KONFIRMASI KATA SANDI --}}
            <div class="relative">
                <label class="block text-text-label text-sm mb-1">Konfirmasi Kata Sandi</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    autocomplete="new-password" placeholder="Konfirmasi kata sandi baru"
                    class="w-full px-4 py-2 pr-12 rounded-lg border border-border-strong bg-surface-base text-text-input focus:outline-none focus:ring-2 focus:ring-primary @error('password_confirmation') border-error @enderror">
                @include('partials.password-visibility-toggle', ['targetId' => 'password_confirmation'])
                @error('password_confirmation')
                    <p class="text-error text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- TOMBOL RESET (loading reusable) --}}
            @include('partials.loading-submit-button', [
                'id' => 'resetPasswordBtn',
                'textId' => 'resetPasswordBtnText',
                'spinnerId' => 'resetPasswordBtnSpinner',
                'buttonText' => 'Reset Kata Sandi',
                'buttonType' => 'submit',
                'buttonClass' =>
                    'w-full bg-primary text-white font-medium py-2 rounded-lg hover:bg-primary-hover transition-all inline-flex items-center justify-center gap-2',
            ])

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
