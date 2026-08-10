{{-- =====================================================================
     Halaman: Rekap Proyek (Standalone)
     Tujuan: Menampilkan daftar rekap proyek yang diinput manual dengan
             kolom No (auto-generate), Nama Proyek, Total RAB, Uang Masuk
             (DP, superadmin), Terbayar, Sisa Pembayaran, progress bar,
             dan file design. Mendukung tambah, lihat detail, edit, dan
             hapus massal. Bukti pembayaran diupload lewat menu Bukti
             Pembayaran (kategori Rekap Proyek).
     Data dari RecapProyekController@index:
     - $recaps : Paginator ProjectRecap dari
                 RecapProyekService::buildIndexQuery($request),
                 difilter oleh search.
     Komponen yang di-include:
     - x-filters.search-input                         : toolbar pencarian
     - x-buttons.delete-button / add-button           : tombol aksi
     - components.finance.project-recaps.table        : tabel rekap
     - components.finance.project-recaps.add-modal    : modal tambah
     - components.finance.project-recaps.detail-modal : modal detail
     - components.finance.project-recaps.edit-modal   : modal edit
     - x-modal                                        : konfirmasi hapus
     - x-pagination                                   : navigasi halaman
     JS: @vite('resources/js/pages/finance/project-recaps/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Rekap Proyek')

@section('content')
    {{-- ==================== Container Utama ==================== --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Rekap Proyek</h1>

        {{-- ==================== Toolbar Pencarian & Aksi ==================== --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('recap-proyek.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nama proyek..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Proyek" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ==================== Tabel Rekap Proyek ==================== --}}
        <x-finance.project-recaps.table :recaps="$recaps" />
    </div>

    {{-- ==================== Pagination ==================== --}}
    <x-pagination :paginator="$recaps" />

    {{-- ==================== Modals ==================== --}}
    @include('components.finance.project-recaps.add-modal')

    @foreach ($recaps as $recap)
        @include('components.finance.project-recaps.detail-modal', ['recap' => $recap])
        @include('components.finance.project-recaps.edit-modal', ['recap' => $recap])
    @endforeach

    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        <p class="text-text-primary mb-4">Apakah Anda yakin ingin menghapus data yang dipilih?</p>
        <p class="text-sm text-text-secondary">
            <i class="fa-solid fa-info-circle"></i> File design dari data yang dihapus juga akan ikut terhapus.
        </p>
    </x-modal>

    {{-- ==================== JavaScript ==================== --}}
    @vite(['resources/js/pages/finance/project-recaps/index.js'])
@endsection
