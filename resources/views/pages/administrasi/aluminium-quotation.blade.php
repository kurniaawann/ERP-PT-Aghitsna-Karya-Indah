{{-- =====================================================================
     Halaman Utama Modul Penawaran Aluminium (Aluminium Quotation)
     PT Aghitsna Karya Indah

     Tujuan: Menampilkan daftar penawaran aluminium dengan pencarian,
             paginasi, tambah/edit/detail lewat modal, dan hapus massal.

     Data dari AluminiumQuotationController@index:
     - $quotations      : koleksi AluminiumQuotation (paginate 15/halaman,
                          eager-load relasi groups.items, diurutkan
                          berdasarkan sequence_number menurun)
     - $paymentAccounts : daftar rekening pembayaran aktif (opsi form)
     - $search          : keyword pencarian (quotation_number, recipient,
                          subject)

     Komponen:
     - Header: Judul halaman
     - Toolbar: Form pencarian, tombol hapus massal, tambah
     - Tabel: Daftar penawaran dengan checkbox
     - Modal Tambah: Form tambah penawaran baru
     - Modal Edit: Form edit penawaran (satu per data)
     - Modal Detail: Tampilan detail penawaran (read-only)
     - Modal Hapus: Konfirmasi hapus massal
     - Pagination: Navigasi halaman

     JS: @vite('resources/js/pages/administrasi/aluminium-quotation/index.js')
         + meta aluminium-quotation-get-next-number untuk nomor otomatis
           via AJAX (AluminiumQuotationController@getNextQuotationNumber)
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Penawaran Aluminium')

@section('content')
    {{-- ═══════════════════════════════════════════════════════════════
         HEADER: Container utama dengan background surface
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header: Judul Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Penawaran Aluminium</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('aluminium-quotation.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari nomor / penerima..." responsive="custom" />
            </form>

            {{-- Tombol Aksi --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Penawaran --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Penawaran" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar penawaran
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.aluminium-quotation.table', ['quotations' => $quotations])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$quotations" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah penawaran baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.aluminium-quotation.add-modal')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL DETAIL & EDIT: Satu modal per data penawaran.
         Alur: iterasi setiap $quotation pada halaman aktif, lalu render
         sepasang modal detail (read-only) dan modal edit untuk baris tsb.
         ID modal dibangun per baris di komponen table/edit-modal agar
         tombol "Detail"/"Edit" pada setiap baris membuka modal yang sesuai.
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($quotations as $quotation)
        @include('components.administrasi.aluminium-quotation.detail-modal', ['quotation' => $quotation])
        @include('components.administrasi.aluminium-quotation.edit-modal', ['quotation' => $quotation])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus penawaran yang dipilih?
    </x-modal>

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        <meta name="aluminium-quotation-get-next-number" content="{{ route('aluminium-quotation.getNextNumber') }}">
        @vite('resources/js/pages/administrasi/aluminium-quotation/index.js')
    @endpush
@endsection
