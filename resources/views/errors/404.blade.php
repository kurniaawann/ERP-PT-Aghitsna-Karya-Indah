@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan - PT Aghitsna Karya Indah')

@section('content')
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-lg w-full bg-white rounded-xl shadow-lg p-8 text-center">
            {{-- Icon 404 --}}
            <div class="mb-6">
                <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto">
                    <i class="fa-solid fa-search text-yellow-600 text-5xl"></i>
                </div>
            </div>

            {{-- Title --}}
            <h1 class="text-6xl font-bold text-yellow-600 mb-3">404</h1>
            <h2 class="text-2xl font-semibold text-text-primary mb-3">Halaman Tidak Ditemukan</h2>

            {{-- Message --}}
            <p class="text-text-secondary mb-6">
                Maaf, halaman yang Anda cari tidak ditemukan atau telah dipindahkan.
            </p>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="window.history.back()"
                    class="flex items-center justify-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-arrow-left"></i>
                    Kembali
                </button>

                <a href="{{ route('dashboard') }}"
                    class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-home"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection
