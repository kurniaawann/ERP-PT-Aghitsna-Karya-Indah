{{-- =====================================================================
     HALAMAN BUKTI KAS KELUAR (Cash Out Proof)
     Menampilkan daftar bukti kas keluar dengan fitur:
     - Pencarian data
     - Tambah bukti kas keluar baru (modal)
     - Edit bukti kas keluar (modal per baris)
     - Hapus beberapa data sekaligus (bulk delete)
     - Export PDF (semua data atau data terpilih)
     - Paginasi (15 data per halaman)

     Data dari CashOutProofController@index:
     - $cashOuts : koleksi CashOutProof (paginate 15/halaman, hanya data
                   milik user yang login, urut created_at terbaru;
                   pencarian berdasarkan bkk_no, cek_no, paid_to,
                   description)
     - $search   : keyword pencarian

     Komponen: table, add-modal, edit-modal (per baris), deleteModal,
               print-dropdown-with-selected
     JS: @vite('resources/js/pages/administrasi/cash-out-proof/index.js')
         + hidden input cash-out-proof-print-selected-route (route export
           PDF data terpilih yang dipakai JS)
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Bukti Kas Keluar')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ═══════════════════════════════════════════════════════════
             HEADER: Container utama dengan background surface
             ═══════════════════════════════════════════════════════════ --}}

        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Bukti Kas Keluar</h1>

        {{-- ═══════════════════════════════════════════════════════════
             TOOLBAR: Filter Pencarian & Tombol Aksi
             ═══════════════════════════════════════════════════════════ --}}

        {{-- Filter Pencarian dan Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('cash-out-proof.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari bukti kas keluar..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Export PDF, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">
                    <x-buttons.print-dropdown-with-selected
                        :pdfRoute="route('cash-out-proof.export.pdf')"
                        :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]"
                        responsive="custom" fill />

                    <x-buttons.delete-button modalId="deleteModal" />

                    <x-buttons.add-button modalId="addModal" text="Tambah BKK" />
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             TABEL: Daftar bukti kas keluar
             ═══════════════════════════════════════════════════════════ --}}

        {{-- Tabel Data Bukti Kas Keluar --}}
        @include('components.administrasi.cash-out-proof.table', ['cashOuts' => $cashOuts])

    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         PAGINATION: Navigasi halaman data
         ═══════════════════════════════════════════════════════════════ --}}

    {{-- Pagination --}}
    <x-pagination :paginator="$cashOuts" />

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL TAMBAH: Form tambah bukti kas keluar baru
         ═══════════════════════════════════════════════════════════════ --}}

    {{-- Modal Form Tambah Bukti Kas Keluar --}}
    @include('components.administrasi.cash-out-proof.add-modal', ['executives' => $executives])

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit bukti kas keluar (satu modal per baris).
         Alur: iterasi setiap $cashOut pada halaman aktif lalu render
         modal edit untuk masing-masing baris data.
         ═══════════════════════════════════════════════════════════════ --}}
    @foreach ($cashOuts as $cashOut)
        @include('components.administrasi.cash-out-proof.edit-modal', ['cashOut' => $cashOut, 'executives' => $executives])
    @endforeach

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true"
        onConfirm="submitDeleteForm()" buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Hidden input untuk route print selected (digunakan oleh JS).
         JS membaca nilai route ini saat user memilih baris lalu klik
         "Print Selected" pada print-dropdown-with-selected. --}}
    <input type="hidden" id="cash-out-proof-print-selected-route" value="{{ route('cash-out-proof.export.pdf.selected') }}">

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/cash-out-proof/index.js')
    @endpush
@endsection
