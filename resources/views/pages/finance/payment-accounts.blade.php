{{-- =====================================================================
     Halaman: Rekening Pembayaran (Payment Accounts)
     Tujuan: Mengelola daftar rekening pembayaran (bank/account) yang
             dipakai pada form invoice dan dokumen finance lain, lengkap
             dengan pencarian, tambah, edit, toggle aktif, dan hapus massal
             dengan guard "minimal 1 rekening harus tetap ada".
     Data dari PaymentAccountController@index:
     - $accounts: Paginator PaymentAccount (15/halaman) hasil
                  PaymentAccountService::buildFilteredQuery($request),
                  difilter oleh request('search'), diurutkan by id.
     - session('usage_error'): pesan error saat rekening yang masih
                  dipakai tabel lain gagal dihapus (AJAX/non-AJAX).
     Komponen yang di-include:
     - x-filters.search-input                        : input pencarian
     - components.finance.payment-accounts.table     : tabel rekening
     - components.finance.payment-accounts.add-modal / edit-modal : modal CRUD
     - x-pagination                                  : navigasi halaman
     - x-modal (deleteModal & errorModal)            : konfirmasi hapus & error
     JS: @vite('resources/js/pages/finance/payment-accounts/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekening Pembayaran')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ==================== Header & Toolbar ==================== --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekening Pembayaran</h1>

        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('payment-accounts.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari rekening..." />
            </form>

            {{-- Tombol Aksi --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col lg:flex-row gap-2 w-full lg:w-auto">
                    <button type="button" id="delete-button" onclick="openModal('deleteModal')" disabled
                        class="w-full sm:w-auto flex items-center justify-center gap-2 bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-btn-delete">
                        <i class="fa-solid fa-trash w-4 h-4"></i>
                        <span>Hapus</span>
                    </button>

                    <x-buttons.add-button modalId="addModal" text="Tambah Rekening" />
                </div>
            </div>
        </div>

        {{-- ==================== Tabel Rekening ==================== --}}
        {{-- Tabel menampilkan daftar rekening dengan tombol edit/toggle
             aktif per baris dan checkbox untuk seleksi hapus massal. --}}
        @include('components.finance.payment-accounts.table')

    </div>

    {{-- ==================== Pagination ==================== --}}
    <x-pagination :paginator="$accounts" />

    {{-- ==================== Modal Tambah ==================== --}}
    @include('components.finance.payment-accounts.add-modal')

    {{-- ==================== Modal Edit (per baris) ==================== --}}
    {{-- Satu modal edit dirender untuk setiap rekening agar form tiap
         baris terpisah dan data tidak tercampur. --}}
    @foreach ($accounts as $account)
        @include('components.finance.payment-accounts.edit-modal', ['account' => $account])
    @endforeach

    {{-- ==================== Modal Konfirmasi Bulk Delete ==================== --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus rekening yang dipilih?
    </x-modal>

    {{-- ==================== Modal Error Penggunaan ==================== --}}
    {{-- Ditampilkan oleh JS ketika bulk delete ditolak karena rekening
         masih dipakai di tabel lain; pesan disuntikkan ke #errorMessage. --}}
    <x-modal id="errorModal" title="Tidak Dapat Menghapus" :readonly="true">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-error-light">
                <i class="fa-solid fa-triangle-exclamation text-error text-lg"></i>
            </div>
            <p class="text-text-primary text-sm leading-relaxed" id="errorMessage"></p>
        </div>
    </x-modal>

    {{-- ==================== Session Data untuk JavaScript ==================== --}}
    {{-- Saat controller mengembalikan session('usage_error') (mis. dari
         destroySelected non-AJAX), pesan dibungkus ke elemen tersembunyi
         agar bisa dibaca & ditampilkan oleh JS di modal errorModal. --}}
    @if (session('usage_error'))
        <div id="usageErrorData" data-message="{{ session('usage_error') }}" class="hidden"></div>
    @endif

    {{-- ==================== JavaScript ==================== --}}
    @vite('resources/js/pages/finance/payment-accounts/index.js')
@endsection
