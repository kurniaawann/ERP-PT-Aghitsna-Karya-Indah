{{-- =====================================================================
     Halaman: Penawaran Proyek (Project Quotation)
     Tujuan: Menampilkan daftar penawaran proyek dengan pencarian,
             paginasi, tambah/edit lewat modal, dan hapus massal.
             Judul halaman bervariasi sesuai peran user:
             - Admin     -> "Penawaran"
             - Non-admin -> "Penawaran Proyek"

     Data dari ProjectQuotationController@index:
     - $quotations      : koleksi ProjectQuotation (paginated, eager-load
                          relasi items, hanya data milik user yang login,
                          urut sequence_number desc; pencarian berdasarkan
                          quotation_number, recipient, subject)
     - $paymentAccounts : daftar rekening pembayaran aktif (opsi form modal)
     - $search          : keyword pencarian

     Komponen: table, add-modal, edit-modal (per baris), deleteModal
     JS: @vite('resources/js/pages/administrasi/project-quotation/index.js')
         + meta project-quotation-get-next-number (nomor otomatis via AJAX,
           ProjectQuotationController@getNextQuotationNumber)
     ===================================================================== --}}

@extends('layouts.app')

    {{-- Judul tab browser dinamis: admin melihat "Penawaran", selain itu
         "Penawaran Proyek" (peran user dicek lewat auth()->user()->isAdmin()). --}}
@section('title', 'PT Aghitsna Karya Indah - ' . (auth()->user()->isAdmin() ? 'Penawaran' : 'Penawaran Proyek'))

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Judul halaman dinamis sesuai peran user (sama seperti judul tab). --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">{{ auth()->user()->isAdmin() ? 'Penawaran' : 'Penawaran Proyek' }}</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('project-quotation.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari nomor / penerima..." responsive="custom" />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Penawaran" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Daftar penawaran dengan checkbox
             ═══════════════════════════════════════════════════════════ --}}
        {{-- Table --}}
        @include('components.administrasi.project-quotation.table', ['quotations' => $quotations])

    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         PAGINATION: Navigasi halaman data
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Pagination --}}
    <x-pagination :paginator="$quotations" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah penawaran baru
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Add Modal --}}
    @include('components.administrasi.project-quotation.add-modal')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Satu modal per data penawaran.
         Alur: iterasi setiap $quotation pada halaman aktif lalu render
         satu modal edit per baris data.
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Edit Modals --}}
    @foreach ($quotations as $quotation)
        @include('components.administrasi.project-quotation.edit-modal', ['quotation' => $quotation])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    {{-- Delete Confirm Modal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus penawaran yang dipilih?
    </x-modal>

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        <meta name="project-quotation-get-next-number" content="{{ route('project-quotation.getNextNumber') }}">
        @vite('resources/js/pages/administrasi/project-quotation/index.js')
    @endpush
@endsection
