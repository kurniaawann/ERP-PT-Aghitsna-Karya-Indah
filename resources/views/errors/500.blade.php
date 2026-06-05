@extends('layouts.app')

@section('title', 'Terjadi Kesalahan - PT Aghitsna Karya Indah')

@section('content')
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-lg w-full bg-surface-base rounded-xl shadow-lg p-8 text-center">
            {{-- Icon Error --}}
            <div class="mb-6">
                <div class="w-24 h-24 bg-error-light rounded-full flex items-center justify-center mx-auto">
                    <i class="fa-solid fa-exclamation-triangle text-error text-5xl"></i>
                </div>
            </div>

            {{-- Title --}}
            <h1 class="text-3xl font-bold text-text-primary mb-3">Terjadi Kesalahan</h1>

            {{-- Message --}}
            <p class="text-text-secondary mb-6">
                Maaf, terjadi kesalahan pada sistem. Tim kami telah diberitahu dan akan segera memperbaikinya.
            </p>

            {{-- Error Code --}}
            <div class="bg-error-light border border-error rounded-lg p-4 mb-6">
                <p class="text-sm text-error">
                    <span class="font-semibold">Kode Error:</span> 500 - Internal Server Error
                </p>
                @if (config('app.debug'))
                    <p class="text-xs text-error mt-2">
                        {{ $exception->getMessage() ?? 'Unknown error' }}
                    </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="window.location.reload()"
                    class="flex items-center justify-center gap-2 bg-btn-add hover:bg-btn-add-hover text-white px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-rotate-right"></i>
                    Coba Lagi
                </button>

                <a href="{{ route('dashboard') }}"
                    class="flex items-center justify-center gap-2 bg-surface-secondary hover:bg-surface-hover text-text-primary px-6 py-3 rounded-lg transition-colors duration-200 font-medium">
                    <i class="fa-solid fa-home"></i>
                    Kembali ke Dashboard
                </a>
            </div>

            {{-- Support Info --}}
            <div class="mt-8 pt-6 border-t border-border-light">
                <p class="text-xs text-text-label">
                    Jika masalah terus berlanjut, hubungi administrator sistem
                </p>
            </div>
        </div>
    </div>
@endsection
