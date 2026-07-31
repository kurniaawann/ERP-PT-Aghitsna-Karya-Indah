{{-- ============================================================
     HALAMAN BUKTI KAS KELUAR
     Menampilkan daftar bukti kas keluar dengan fitur:
     - Pencarian data
     - Tambah bukti kas keluar baru (modal)
     - Edit bukti kas keluar (modal per baris)
     - Hapus beberapa data sekaligus (bulk delete)
     - Export PDF (semua data atau data terpilih)
     - Paginasi (15 data per halaman)
============================================================ --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Bukti Kas Keluar')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Bukti Kas Keluar</h1>

        {{-- Filter Pencarian dan Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('cash-out-proof.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari bukti kas keluar..." />
            </form>

            {{-- Tombol Aksi: Export PDF, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
                    <x-buttons.print-dropdown-with-selected
                        :pdfRoute="route('cash-out-proof.export.pdf')"
                        :queryParams="['search' => request('search')]"
                        responsive="custom" fill />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah BKK" />
                </div>
            </div>
        </div>

        {{-- Tabel Data Bukti Kas Keluar --}}
        @include('components.administrasi.cash-out-proof.table', ['cashOuts' => $cashOuts])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$cashOuts" />

    {{-- Modal Form Tambah Bukti Kas Keluar --}}
    @include('components.administrasi.cash-out-proof.add-modal')

    {{-- Modal Form Edit Bukti Kas Keluar (satu modal per baris data) --}}
    @foreach ($cashOuts as $cashOut)
        @include('components.administrasi.cash-out-proof.edit-modal', ['cashOut' => $cashOut])
    @endforeach

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true"
        onConfirm="submitDeleteForm()" buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS) --}}
    <input type="hidden" id="cash-out-proof-print-selected-route" value="{{ route('cash-out-proof.export.pdf.selected') }}">

    {{-- JavaScript: Load via Vite (modular) --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/cash-out-proof/index.js')
    @endpush
@endsection
