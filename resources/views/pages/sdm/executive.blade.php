{{-- =====================================================================
     Halaman: Data Penandatangan (executive, gaya Filament)
     Tujuan: Menampilkan daftar penandatangan (pimpinan perusahaan) beserta
             gambar tanda tangan, dengan fitur pencarian, tambah, edit,
             hapus massal / satu data, dan pagination.

     Data dari ExecutiveController@index:
     - $executives : LengthAwarePaginator daftar penandatangan (sudah dipaginasi)
     - $search     : Kata kunci pencarian saat ini (nullable)

     Komponen yang di-include:
     - components.sdm.executive.table      : toolbar + tabel + paginasi
     - components.sdm.executive.add-modal  : modal tambah penandatangan
     - components.sdm.executive.edit-modal : modal edit per penandatangan (loop)
     - x-fi.modal / x-fi.delete-row-modal  : konfirmasi hapus massal / satu data

     JS yang di-load:
     - @vite('resources/js/pages/sdm/executive/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Penandatangan')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi --}}
        <x-fi.page-header heading="Data Penandatangan"
            :breadcrumbs="['Human Resource' => null, 'Data Penandatangan' => route('executive.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Penandatangan</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel (+ pencarian, hapus massal, paginasi) --}}
        @include('components.sdm.executive.table', ['executives' => $executives])
    </div>

    {{-- SECTION: Modal Tambah --}}
    @include('components.sdm.executive.add-modal')

    {{-- SECTION: Modal Edit (satu per baris) --}}
    @foreach ($executives as $executive)
        @include('components.sdm.executive.edit-modal', ['executive' => $executive])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Penandatangan (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('executive.destroy') yang sama dengan hapus massal (ids[]). --}}
    <x-fi.delete-row-modal :action="route('executive.destroy')" field="ids" heading="Hapus Penandatangan"
        warning="Gambar tanda tangannya ikut terhapus." />

    @push('scripts')
        @vite('resources/js/pages/sdm/executive/index.js')
    @endpush
@endsection
