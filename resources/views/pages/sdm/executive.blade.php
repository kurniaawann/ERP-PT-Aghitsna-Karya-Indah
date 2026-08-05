{{-- =====================================================================
     Halaman: Data Petinggi (executive)
     Tujuan: Menampilkan daftar petinggi/pimpinan perusahaan beserta
             gambar tanda tangan, dengan fitur pencarian, tambah, edit,
             hapus massal, dan pagination.

     Data dari ExecutiveController@index:
     - $executives : LengthAwarePaginator daftar petinggi (sudah dipaginasi)
     - $search     : Kata kunci pencarian saat ini (nullable)

     Komponen yang di-include:
     - components.sdm.executive.table      : tabel daftar petinggi
     - components.sdm.executive.add-modal  : modal tambah petinggi
     - components.sdm.executive.edit-modal : modal edit per petinggi (loop)
     - x-pagination                        : kontrol pagination
     - x-modal (deleteModal)               : konfirmasi hapus massal

     JS yang di-load:
     - @vite('resources/js/pages/sdm/executive/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Petinggi')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Petinggi</h1>

        {{-- Pencarian & Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <form method="GET" action="{{ route('executive.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari nama / jabatan..." />
            </form>

            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- Tabel Petinggi --}}
        @include('components.sdm.executive.table', ['executives' => $executives])
    </div>

    {{-- Paginasi --}}
    <x-pagination :paginator="$executives" />

    {{-- Modal Tambah Petinggi --}}
    @include('components.sdm.executive.add-modal')

    {{-- Modal Edit Petinggi (satu per baris) --}}
    @foreach ($executives as $executive)
        @include('components.sdm.executive.edit-modal', ['executive' => $executive])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- JavaScript --}}
    @vite('resources/js/pages/sdm/executive/index.js')
@endsection
