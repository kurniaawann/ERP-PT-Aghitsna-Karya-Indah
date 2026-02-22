@extends('layouts.app')

@section('title', 'Terjadi Kesalahan - PT Aghitsna Karya Indah')

@section('content')
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-lg w-full bg-white rounded-xl shadow-lg p-8 text-center">
            {{-- Icon Error --}}
            <div class="mb-6">
                <div class="w-24 h-24 bg-red-100 rounded-full flex items-center justify-center mx-auto">
                    <i class="fa-solid fa-exclamation-triangle text-red-600 text-5xl"></i>
                </div>
            </div>

            {{-- Title --}}
            <h1 class="text-3xl font-bold text-text-primary mb-3">Terjadi Kesalahan</h1>

            {{-- Message --}}
            <p class="text-text-secondary mb-6">
                Maaf, terjadi kesalahan pada sistem. Tim kami telah diberitahu dan akan segera memperbaikinya.
            </p>

            {{-- Error Code --}}
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-800">
                    <span class="font-semibold">Kode Error:</span> 500 - Internal Server Error
                </p>
                @if (config('app.debug'))
                    <p class="text-xs text-red-600 mt-2">
                        {{ $exception->getMessage() ?? 'Unknown error' }}
                    </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="window.location.reload()"
                    class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-rotate-right"></i>
                    Coba Lagi
                </button>

                <a href="{{ route('dashboard') }}"
                    class="flex items-center justify-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-home"></i>
                    Kembali ke Dashboard
                </a>
            </div>

            {{-- Support Info --}}
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs text-text-label">
                    Jika masalah terus berlanjut, hubungi administrator sistem
                </p>
            </div>
        </div>
    </div>
@endsection
