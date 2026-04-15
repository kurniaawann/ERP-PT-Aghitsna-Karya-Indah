@extends('layouts.guest')

@section('title', 'Lupa Kata Sandi | ERP PT Aghitsna Karya Indah')

@section('content')
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <div class="flex flex-col items-center mb-6">
            <div class="bg-primary flex items-center justify-center text-white font-bold text-2xl rounded-md">
                <div class="m-2">
                    ERP PT Aghitsna Karya Indah
                </div>
            </div>
            <h1 class="text-2xl font-semibold mt-4 text-text-primary">Lupa Kata Sandi</h1>
            <p class="text-text-secondary text-sm mt-1">Masukkan email untuk menerima link reset password</p>
        </div>

        {{-- Status Message --}}
        @if (session('status'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
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
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- TOMBOL KIRIM LINK --}}
            <button type="submit"
                class="w-full bg-primary text-white font-medium py-2 rounded-lg hover:bg-blue-700 transition-all">
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
