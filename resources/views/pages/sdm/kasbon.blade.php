{{-- ═══════════════════════════════════════════════════════════════════════
     Halaman: Index Kasbon (kasbon)
     Tujuan: Halaman utama untuk mengelola kasbon (cash advance).
     Menampilkan daftar kasbon yang sudah difilter/dicari dengan fitur
     tambah, edit, hapus massal, dan pembayaran cicilan.

     Data dari KasbonController@index:
     - $kasbons   : LengthAwarePaginator daftar kasbon (dengan relasi employee)
     - $employees : Collection seluruh karyawan (dropdown pemilih di modal)
     - $divisions : Collection seluruh divisi (dropdown untuk kasbon tipe Tim)

     Komponen yang di-include:
     - components.sdm.kasbon.table      : tabel daftar kasbon
     - components.sdm.kasbon.add-modal  : modal tambah kasbon
     - components.sdm.kasbon.edit-modal : modal edit per kasbon (loop)
     - components.sdm.kasbon.pay-modal  : modal bayar cicilan
     - x-pagination                     : kontrol pagination
     - x-modal (deleteModal)            : konfirmasi hapus massal

     Alur logika yang perlu diperhatikan:
     - Dropdown karyawan di modal menyesuaikan TIPE kasbon: kasbon "Per Orang"
       memilih satu karyawan (employee_id), sedangkan kasbon "Per Tim"
       memilih divisi (division) — logika disetel lewat JS modul.
     - Validasi max kasbon via endpoint AJAX checkMaxKasbon (dicek saat
       memilih karyawan/periode pada modal tambah).
     - Filter bulan/tahun berdasar period_start_date (periode payroll),
       bukan tanggal kasbon.

     JS yang di-load:
     - @vite('resources/js/pages/sdm/kasbon/index.js')
     ═══════════════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Kasbon')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        {{-- ============================================================
             SECTION: Header
             Judul halaman.
             ============================================================ --}}
        {{-- Header Halaman --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Kasbon</h1>

        {{-- ============================================================
             SECTION: Filter / Toolbar
             Form filter lengkap (bulan, tahun, status, payment_status,
             jenis, proyek, pencarian) yang auto-submit saat berubah, plus
             tombol reset dan tombol aksi tambah/hapus.
             ============================================================ --}}
        {{-- Pencarian, Filter & Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Filter & Pencarian --}}
            <form method="GET" action="{{ route('kasbon.index') }}" id="filterForm"
                class="w-full min-[1600px]:w-auto min-[1600px]:flex-1 flex flex-col min-[1600px]:flex-row flex-wrap gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" responsive="custom" fill onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" responsive="custom" fill onchange="document.getElementById('filterForm').submit()" />

                {{-- Filter Status --}}
                <div class="flex-1">
                    <label for="status-select" class="sr-only">Status</label>
                    <select name="status" id="status-select" onchange="document.getElementById('filterForm').submit()"
                        class="block w-full rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Belum Dipotong</option>
                        <option value="deducted" {{ request('status') == 'deducted' ? 'selected' : '' }}>Sudah Dipotong</option>
                    </select>
                </div>

                {{-- Filter Status Pembayaran --}}
                <div class="flex-1">
                    <label for="payment_status-select" class="sr-only">Status Pembayaran</label>
                    <select name="payment_status" id="payment_status-select" onchange="document.getElementById('filterForm').submit()"
                        class="block w-full rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Pembayaran</option>
                        <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Belum Dibayar</option>
                        <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Cicilan Berjalan</option>
                        <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                    </select>
                </div>

                {{-- Filter Jenis --}}
                <div class="flex-1">
                    <label for="type-select" class="sr-only">Jenis</label>
                    <select name="type" id="type-select" onchange="document.getElementById('filterForm').submit()"
                        class="block w-full rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Jenis</option>
                        <option value="personal" {{ request('type') == 'personal' ? 'selected' : '' }}>Per Orang</option>
                        <option value="team" {{ request('type') == 'team' ? 'selected' : '' }}>Per Tim</option>
                    </select>
                </div>

                {{-- Filter Proyek --}}
                {{-- Dropdown searchable (10 item per load) mengambil data proyek
                     dari Rekap Proyek; memilih proyek otomatis submit form. --}}
                <x-filters.project-filter :route="route('employee.projects-dropdown')"
                    :value="request('project_name')" fill responsive="custom" dropdown-id="filter_project_name"
                    :auto-submit="true" />

                {{-- Pencarian --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari kasbon..." responsive="custom" />

                {{-- Tombol Reset Filter --}}
                @if (request()->hasAny(['search', 'month', 'year', 'status', 'type', 'payment_status', 'project_name']))
                    <a href="{{ route('kasbon.index') }}"
                        class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <i class="fa-solid fa-rotate-left mr-2"></i>
                        Reset
                    </a>
                @endif
            </form>

            {{-- Tombol Aksi --}}
            <div class="flex items-center gap-2 mt-2 min-[1600px]:mt-0 w-full min-[1600px]:w-auto">
                <div class="flex flex-col min-[1600px]:flex-row gap-2 w-full min-[1600px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Kasbon" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ============================================================
             SECTION: Table
             Menampilkan daftar kasbon sesuai filter yang aktif.
             ============================================================ --}}
        {{-- Tabel Data --}}
        @include('components.sdm.kasbon.table', ['kasbons' => $kasbons])
    </div>

    {{-- ============================================================
         SECTION: Pagination
         Kontrol navigasi halaman; filter dipertahankan pada URL pagination.
         ============================================================ --}}
    {{-- Paginasi --}}
    <x-pagination :paginator="$kasbons" />

    {{-- ============================================================
         SECTION: Modals
         - add-modal   : form tambah kasbon (dropdown karyawan/divisi
                         menyesuaikan tipe, validasi max kasbon via AJAX).
         - edit-modal  : satu modal edit per kasbon (loop).
         - pay-modal   : modal bayar cicilan kasbon.
         - deleteModal : konfirmasi hapus massal (kasbon yang sudah
                         dipotong tidak bisa dihapus).
         ============================================================ --}}
    {{-- Modal Tambah Kasbon --}}
    @include('components.sdm.kasbon.add-modal', ['employees' => $employees])

    {{-- Modal Edit Kasbon (satu per baris) --}}
    @foreach ($kasbons as $kasbon)
        @include('components.sdm.kasbon.edit-modal', [
            'kasbon' => $kasbon,
            'employees' => $employees,
            'divisions' => $divisions,
        ])
    @endforeach

    {{-- Modal Bayar Cicilan --}}
    @include('components.sdm.kasbon.pay-modal')

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data kasbon yang dipilih? <br>
        <span class="text-error text-sm">Catatan: Kasbon yang masih terhubung payroll tidak bisa dihapus.</span>
    </x-modal>

    {{-- ============================================================
         SECTION: Scripts
         Kontainer #kasbon-page menyediakan data-url-get-weeks agar modul
         JS bisa mengambil daftar minggu (untuk dropdown periode payroll
         pada modal tambah). Modul JS di-load via Vite.
         ============================================================ --}}
    {{-- Kontainer halaman dengan atribut data untuk modul JavaScript --}}
    <div id="kasbon-page"
        data-url-get-weeks="{{ route('payroll.get-weeks') }}"
        class="hidden">
    </div>

    {{-- Modul JavaScript (dimuat melalui Vite) --}}
    @vite('resources/js/pages/sdm/kasbon/index.js')
@endsection
