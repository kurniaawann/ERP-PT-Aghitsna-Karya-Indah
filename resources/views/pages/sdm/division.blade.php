@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Divisi')

@section('content')
    {{-- ============================================================
         Header Divisi
         Berisi judul halaman, form pencarian, dan tombol aksi
         (Tambah Data dan Hapus).
         ============================================================ --}}
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Divisi</h1>

        {{-- Pencarian & Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('division.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari divisi..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- ============================================================
             Tabel Divisi
             Menampilkan daftar divisi dengan jumlah karyawan dan aksi edit.
             Menggunakan component terpisah untuk reusability.
             ============================================================ --}}
        @include('components.sdm.division.table', ['divisions' => $divisions])

    </div>

    {{-- Paginasi --}}
    <x-pagination :paginator="$divisions" />

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

    {{-- JavaScript --}}
    @include('partials.sdm.division-scripts')
@endsection
