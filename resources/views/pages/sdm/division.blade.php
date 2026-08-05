{{-- =====================================================================
     Halaman: Data Divisi (division)
     Tujuan: Menampilkan daftar divisi dengan fitur pencarian, tambah,
             edit, hapus massal, dan pagination.

     Data dari DivisionController@index:
     - $divisions : LengthAwarePaginator daftar divisi (sudah dipaginasi)
     - $search    : Kata kunci pencarian saat ini (nullable)

     Komponen yang di-include:
     - components.sdm.division.table      : tabel daftar divisi
     - components.sdm.division.add-modal  : modal tambah divisi
     - components.sdm.division.edit-modal : modal edit per divisi (loop)
     - x-pagination                       : kontrol pagination
     - x-modal (deleteModal)              : konfirmasi hapus massal

     JS yang di-load:
     - @vite('resources/js/pages/sdm/division/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Divisi')

@section('content')
    {{-- ============================================================
         SECTION: Header
         ============================================================ --}}
    {{-- ============================================================
         Header Divisi
         Berisi judul halaman, form pencarian, dan tombol aksi
         (Tambah Data dan Hapus).
         ============================================================ --}}
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Divisi</h1>

        {{-- ============================================================
             SECTION: Filter / Toolbar
             Form pencarian (GET ke division.index) + tombol aksi.
             ============================================================ --}}
        {{-- Pencarian & Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('division.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari divisi..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- ============================================================
             SECTION: Table
             ============================================================ --}}
        {{-- ============================================================
             Tabel Divisi
             Menampilkan daftar divisi dengan jumlah karyawan dan aksi edit.
             Menggunakan component terpisah untuk reusability.
             ============================================================ --}}
        @include('components.sdm.division.table', ['divisions' => $divisions])

    </div>

    {{-- ============================================================
         SECTION: Pagination
         Kontrol navigasi halaman daftar divisi.
         ============================================================ --}}
    {{-- Paginasi --}}
    <x-pagination :paginator="$divisions" />

    {{-- ============================================================
         SECTION: Modals
         - add-modal   : form tambah divisi baru.
         - edit-modal  : satu modal edit per divisi (loop).
         - deleteModal : konfirmasi hapus massal.
         ============================================================ --}}
    {{-- ============================================================
         Modal Tambah Divisi
         Form untuk menambah data divisi baru.
         ============================================================ --}}
    @include('components.sdm.division.add-modal')

    {{-- ============================================================
         Modal Edit Divisi
         Satu modal edit untuk setiap divisi pada halaman saat ini.
         Menggunakan loop untuk membuat modal dengan ID unik.
         ============================================================ --}}
    @foreach ($divisions as $division)
        @include('components.sdm.division.edit-modal', ['division' => $division])
    @endforeach

    {{-- ============================================================
         Modal Konfirmasi Bulk Delete
         Konfirmasi pengaman sebelum menghapus data yang dipilih.
         ============================================================ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- ============================================================
         SECTION: Scripts
         Modul JS halaman divisi (diproses oleh Vite) — menangani
         interaksi modal tambah/edit dan submit hapus massal.
         ============================================================ --}}
    {{-- JavaScript --}}
    @vite('resources/js/pages/sdm/division/index.js')
@endsection
