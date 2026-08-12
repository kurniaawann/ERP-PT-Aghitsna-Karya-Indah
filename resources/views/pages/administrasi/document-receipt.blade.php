{{-- =====================================================================
     Halaman Utama Modul Tanda Terima Dokumen (Document Receipt)
     PT Aghitsna Karya Indah

     Tujuan: Menampilkan daftar tanda terima dokumen dengan pencarian,
             paginasi, tambah/edit lewat modal, hapus massal, dan export PDF.

     Data dari DocumentReceiptController@index:
     - $documents : koleksi DocumentReceipt (paginate 15/halaman, hanya
                    data milik user yang login, urut terbaru;
                    pencarian berdasarkan id_document, received_from,
                    regarding)
     - $search    : keyword pencarian

     Komponen:
     - Header: Judul halaman
     - Toolbar: Form pencarian, tombol print, hapus massal, tambah
     - Tabel: Daftar dokumen dengan checkbox
     - Modal Tambah: Form tambah dokumen baru
     - Modal Edit: Form edit dokumen (satu per dokumen)
     - Modal Hapus: Konfirmasi hapus massal
     - Pagination: Navigasi halaman

     JS: @vite('resources/js/pages/administrasi/document-receipt/index.js')
         + hidden input document-receipt-print-selected-route (route export
           PDF data terpilih yang dipakai JS)
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Tanda Terima Dokumen')

@section('content')
    {{-- ═══════════════════════════════════════════════════════════════
         HEADER: Container utama dengan background surface
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header: Judul Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Tanda Terima Dokumen</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('document-receipt.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari dokumen..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

                    {{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('document-receipt.export.pdf')" :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" responsive="custom" fill />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Dokumen --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Dokumen" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar dokumen
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.document-receipt.table', ['documents' => $documents])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$documents" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah dokumen baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.document-receipt.add-modal')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit dokumen (satu modal per dokumen).
         Alur: iterasi setiap $document pada halaman aktif lalu render
         satu modal edit per baris data.
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($documents as $document)
        @include('components.administrasi.document-receipt.edit-modal', ['document' => $document])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS).
         JS membaca nilai route ini saat user memilih baris lalu klik
         "Print Selected" pada print-dropdown-with-selected. --}}
    <input type="hidden" id="document-receipt-print-selected-route" value="{{ route('document-receipt.export.pdf.selected') }}">

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/document-receipt/index.js')
    @endpush
@endsection
