{{-- =====================================================================
     Halaman Utama Modul Kwitansi Administrasi
     PT Aghitsna Karya Indah

     Tujuan: Menampilkan daftar kwitansi dengan pencarian, paginasi,
             tambah/edit lewat modal, hapus massal, dan export PDF.

     Data dari KwintansiController@index:
     - $kwintansis : koleksi Kwintansi (paginate 15/halaman, eager-load
                     relasi paymentAccount, hanya data milik user yang
                     login, urut created_at terbaru; pencarian berdasarkan
                     id_kwintansi, received_from, payment_for)
     - $search     : keyword pencarian

     Komponen:
     - Header: Judul halaman
     - Toolbar: Form pencarian, tombol print, hapus massal, tambah
     - Tabel: Daftar kwitansi dengan checkbox
     - Modal Tambah: Form tambah kwitansi baru
     - Modal Edit: Form edit kwitansi (satu per kwitansi)
     - Modal Hapus: Konfirmasi hapus massal
     - Pagination: Navigasi halaman

     JS: @vite('resources/js/pages/administrasi/kwitansi/index.js')
         + hidden input kwintansi-print-selected-route (route export PDF
           data terpilih yang dipakai JS)
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

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('kwintansi.index') }}"
                class="w-full min-[1530px]:w-auto min-[1530px]:flex-1 flex flex-col min-[1530px]:flex-row gap-3">
                {{-- Filter Jenis Invoice: hanya untuk role superadmin (paling kiri) --}}
                @if (auth()->user()?->role === 'superadmin')
                    <div class="w-full min-[1530px]:w-auto">
                        <label for="invoice-type-select" class="sr-only">Filter Jenis Invoice</label>
                        <select name="invoice_type" id="invoice-type-select"
                            onchange="this.form.requestSubmit()"
                            class="block w-full min-[1530px]:w-48 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
                                   focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                            <option value="">Semua Jenis Invoice</option>
                            <option value="proyek" @selected(request('invoice_type') === 'proyek')>Invoice Proyek</option>
                            <option value="alumunium" @selected(request('invoice_type') === 'alumunium')>Invoice Alumunium</option>
                            <option value="barang" @selected(request('invoice_type') === 'barang')>Invoice Barang</option>
                        </select>
                    </div>
                @endif

                <x-filters.month-filter :value="request('month')" responsive="custom" />
                <x-filters.year-filter :value="request('year')" responsive="custom" />
                <x-filters.search-input :value="request('search')" placeholder="Cari kwintansi..." responsive="custom" />
            </form>

            {{-- Tombol Aksi: Print, Hapus, Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1530px]:mt-0 w-full min-[1530px]:w-auto">
                <div class="flex flex-col min-[1530px]:flex-row gap-2 w-full min-[1530px]:w-auto">

                    {{-- Dropdown Export PDF --}}
                    <x-buttons.print-dropdown-with-selected :pdfRoute="route('kwintansi.export.pdf')" :queryParams="['search' => request('search'), 'invoice_type' => request('invoice_type'), 'month' => request('month'), 'year' => request('year')]" responsive="custom" fill />

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
    @include('components.administrasi.kwintansi.add-modal', ['executives' => $executives, 'paymentAccounts' => $paymentAccounts])

    {{-- ═══════════════════════════════════════════════════════════════
         MODAL EDIT: Form edit kwitansi (satu modal per kwitansi).
         Alur: iterasi setiap $kwintansi pada halaman aktif lalu render
         satu modal edit per baris data.
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

    {{-- Hidden input untuk route print selected (digunakan oleh JS).
         JS membaca nilai route ini saat user memilih baris lalu klik
         "Print Selected" pada print-dropdown-with-selected. --}}
    <input type="hidden" id="kwintansi-print-selected-route" value="{{ route('kwintansi.export.pdf.selected') }}">

    {{-- ═══════════════════════════════════════════════════════════════
         JAVASCRIPT: Load via Vite (modular)
         ═══════════════════════════════════════════════════════════════ --}}
    @push('scripts')
        @vite('resources/js/pages/administrasi/kwitansi/index.js')
    @endpush
@endsection
