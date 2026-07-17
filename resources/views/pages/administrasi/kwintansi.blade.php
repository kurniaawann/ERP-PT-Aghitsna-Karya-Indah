{{-- =====================================================================
     Halaman Utama Modul Kwitansi Administrasi
     PT Aghitsna Karya Indah

     Komponen:
     - Header: Judul halaman
     - Toolbar: Form pencarian, tombol print, hapus massal, tambah
     - Tabel: Daftar kwitansi dengan checkbox
     - Modal Tambah: Form tambah kwitansi baru
     - Modal Edit: Form edit kwitansi (satu per kwitansi)
     - Modal Hapus: Konfirmasi hapus massal
     - Pagination: Navigasi halaman
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Kwintansi')

@section('content')
    {{-- ═══════════════════════════════════════════════════════════════
         HEADER: Container utama dengan background surface
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header: Judul Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Kwintansi</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('kwintansi.index') }}"
                class="w-full lg:w-auto lg:flex-1 flex flex-col lg:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari kwintansi..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 lg:mt-0 w-full lg:w-auto">
                <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">

                    {{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('kwintansi.export.pdf')" :queryParams="['search' => request('search')]" />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Kwintansi --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Kwintansi" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar kwitansi
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.kwintansi.table', ['kwintansis' => $kwintansis])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$kwintansis" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah kwitansi baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.kwintansi.add-modal')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit kwitansi (satu modal per kwitansi)
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($kwintansis as $kwintansi)
        @include('components.administrasi.kwintansi.edit-modal', ['kwintansi' => $kwintansi])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS) --}}
    <input type="hidden" id="kwintansi-print-selected-route" value="{{ route('kwintansi.export.pdf.selected') }}">

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/kwitansi/index.js')
    @endpush

    {{-- Script dropdown print --}}
    @include('partials.shared.print-dropdown-script')

    {{-- Script print selected (harus dalam <script> karena tidak punya tag sendiri) --}}
    <script>
        @include('partials.shared.print-selected-script')
    </script>
@endsection
