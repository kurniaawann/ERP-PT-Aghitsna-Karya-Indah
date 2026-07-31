{{-- =====================================================================
     Halaman Utama Modul Surat Jalan (Delivery Note)
     PT Aghitsna Karya Indah

     Komponen:
     - Header: Judul halaman
     - Toolbar: Form pencarian, tombol print, hapus massal, tambah
     - Tabel: Daftar surat jalan dengan checkbox
     - Modal Tambah: Form tambah surat jalan baru
     - Modal Edit: Form edit surat jalan (satu per data)
     - Modal Hapus: Konfirmasi hapus massal
     - Pagination: Navigasi halaman
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Surat Jalan')

@section('content')
    {{-- ═══════════════════════════════════════════════════════════════
         HEADER: Container utama dengan background surface
         ═══════════════════════════════════════════════════════════════ --}}
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- Header: Judul Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Surat Jalan</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('delivery-note.administrasi.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari surat jalan..." />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">

                    {{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('delivery-note.administrasi.export.pdf')" :queryParams="['search' => request('search')]" responsive="custom" fill />

                    {{-- Tombol Hapus Massal --}}
                    <x-buttons.delete-button modalId="deleteModal" />

                    {{-- Tombol Tambah Surat Jalan --}}
                    <x-buttons.add-button modalId="addModal" text="Tambah Surat Jalan" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Komponen tabel daftar surat jalan
             ═══════════════════════════════════════════════════════════ --}}
        @include('components.administrasi.delivery-note.table', ['deliveryNotes' => $deliveryNotes])

    </div>

    {{-- Pagination --}}
    <x-pagination :paginator="$deliveryNotes" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah surat jalan baru
         ═══════════════════════════════════════════════════════════════ --}}
    @include('components.administrasi.delivery-note.add-modal')

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL DETAIL: Tampilan detail surat jalan (read-only)
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($deliveryNotes as $deliveryNote)
        @include('components.administrasi.delivery-note.detail-modal', ['deliveryNote' => $deliveryNote])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit surat jalan (satu modal per data)
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($deliveryNotes as $deliveryNote)
        @include('components.administrasi.delivery-note.edit-modal', ['deliveryNote' => $deliveryNote])
    @endforeach

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL HAPUS: Konfirmasi hapus massal
         ═══════════════════════════════════════════════════════════════ --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus surat jalan yang dipilih?
    </x-modal>

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/delivery-notes/index.js')

        <script>
            /**
             * Membuka modal detail surat jalan berdasarkan ID.
             *
             * @param {string} id  ID surat jalan
             */
            function showDetailModal(id) {
                const modal = document.getElementById('detailModal-' + id);
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                }
            }
        </script>
    @endpush
@endsection
