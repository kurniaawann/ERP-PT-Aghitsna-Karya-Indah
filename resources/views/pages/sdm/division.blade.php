{{-- =====================================================================
     Halaman: Data Divisi (division, gaya Filament)
     Tujuan: Menampilkan daftar divisi dengan fitur pencarian, tambah,
             edit, hapus massal / satu data, dan pagination.

     Data dari DivisionController@index:
     - $divisions : LengthAwarePaginator daftar divisi (sudah dipaginasi)
     - $search    : Kata kunci pencarian saat ini (nullable)

     Komponen yang di-include:
     - components.sdm.division.table      : toolbar + tabel + paginasi
     - components.sdm.division.add-modal  : modal tambah divisi
     - components.sdm.division.edit-modal : modal edit per divisi (loop)
     - x-fi.modal / x-fi.delete-row-modal : konfirmasi hapus massal / satu data

     JS yang di-load:
     - @vite('resources/js/pages/sdm/division/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Divisi')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi --}}
        <x-fi.page-header heading="Data Divisi"
            :breadcrumbs="['Human Resource' => null, 'Data Divisi' => route('division.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Divisi</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel (+ pencarian, hapus massal, paginasi) --}}
        @include('components.sdm.division.table', ['divisions' => $divisions])
    </div>

    {{-- SECTION: Modal Tambah --}}
    @include('components.sdm.division.add-modal')

    {{-- SECTION: Modal Edit (satu per divisi) --}}
    @foreach ($divisions as $division)
        @include('components.sdm.division.edit-modal', ['division' => $division])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Divisi (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('division.destroy') yang sama dengan hapus massal (ids[]). --}}
    <x-fi.delete-row-modal :action="route('division.destroy')" field="ids" heading="Hapus Divisi" />

    @push('scripts')
        @vite('resources/js/pages/sdm/division/index.js')
    @endpush
@endsection
