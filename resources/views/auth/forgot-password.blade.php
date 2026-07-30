@extends('layouts.guest')

@section('title', 'Lupa Kata Sandi | ERP PT Aghitsna Karya Indah')

@section('content')
    <div class="w-full max-w-md bg-surface-base rounded-2xl shadow-lg border border-border-light p-8">
        <div class="flex flex-col items-center mb-6">
            <img src="{{ asset('images/logo.jpeg') }}" alt="Logo PT Aghitsna Karya Indah"
                class="h-20 w-auto object-contain">
            <h1 class="text-xl font-semibold mt-4 text-text-primary">Lupa Kata Sandi</h1>
            <p class="text-text-secondary text-sm mt-1">Masukkan email untuk menerima link reset password</p>
        </div>

        {{-- Status Message --}}
        @if (session('status'))
            <div class="mb-4 p-4 bg-success-light border border-success text-success rounded-lg">
                {{ session('status') }}
            </div>
        @endif

        {{-- FORM FORGOT PASSWORD --}}
        <form action="{{ route('password.email') }}" method="POST" class="space-y-5" autocomplete="on">
            @csrf

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

            {{-- TOMBOL KIRIM LINK --}}
            <button type="submit"
                class="w-full bg-primary text-white font-medium py-2 rounded-lg hover:bg-primary-hover transition-all">
                Kirim Link Reset
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
