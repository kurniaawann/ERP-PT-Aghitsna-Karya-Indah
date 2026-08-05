{{-- ═══════════════════════════════════════════════════════════════════════════
     HALAMAN INDEX REIMBURSEMENT
     Tujuan: Menampilkan daftar reimbursement dengan filter status/bulan/
             tahun & pencarian, aksi CRUD, persetujuan (approve/reject),
             dan export PDF/Excel.
     Data dari ReimburseController@index:
     - $reimburses: Paginator Reimburse (15/halaman) hasil
                    ReimburseService::buildFilteredQuery($request),
                    difilter search, status, month, year.
     - $search / $status / $month / $year: nilai filter aktif dari
                    request (dipakai untuk mempertahankan pilihan).
     Logika role:
     - superadmin: dapat menambah & mengedit reimburse (status draft).
     - admin     : dapat menyetujui/menolak reimburse (bulk approve/reject).
     Komponen yang di-include:
     - x-filters.month-filter / year-filter / search-input : toolbar filter
     - x-buttons.print-dropdown / add-button / delete-button : tombol aksi
     - components.finance.reimburse.table                  : tabel reimburse
     - components.finance.reimburse.add-modal / edit-modal : modal CRUD (superadmin)
     - components.finance.reimburse.approve-modal / reject-modal : modal persetujuan (admin)
     - x-modal                                             : konfirmasi hapus
     - x-pagination                                        : navigasi halaman
     JS: @vite('resources/js/pages/finance/reimburse/index.js')
     ═══════════════════════════════════════════════════════════════════════════ --}}
{{-- ═══════════════════════════════════════════════════════════════════════════
     HALAMAN INDEX REIMBURSEMENT
     Menampilkan daftar reimbursement dengan filter, search, aksi CRUD,
     persetujuan (approve/reject), dan export.
     ═══════════════════════════════════════════════════════════════════════════ --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Reimbursement')

@section('content')
    <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">

        {{-- ─── Header Halaman ────────────────────────────────────────────────── --}}
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Reimbursement</h1>

        {{-- ─── Toolbar: Search, Filter, dan Tombol Aksi ─────────────────────── --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">

            {{-- Form Pencarian & Filter --}}
            <form method="GET" action="{{ route('reimburse.index') }}"
                class="w-full min-[1520px]:w-auto min-[1520px]:flex-1 flex flex-col min-[1520px]:flex-row gap-3">

                {{-- Filter Status --}}
                {{-- Dropdown status yang auto-submit. Atribut selected
                     memakai ternary request('status') === 'x' untuk
                     mempertahankan nilai filter setelah reload. --}}
                <div class="w-full min-[1520px]:w-auto">
                    <label for="status-select" class="sr-only">Semua Status</label>
                    <select name="status" id="status-select" onchange="this.form.requestSubmit()"
                        class="block w-full min-[1520px]:w-40 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input 
                               focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                {{-- Filter Bulan --}}
                <x-filters.month-filter name="month" :value="request('month')" responsive="custom" />

                {{-- Filter Tahun --}}
                <x-filters.year-filter name="year" :value="request('year')" responsive="custom" />

                {{-- Input Pencarian --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari proyek atau kode reimburse..." responsive="custom" />
            </form>

            {{-- Tombol Aksi (Kanan) --}}
            <div class="flex items-center gap-2 mt-2 min-[1520px]:mt-0 w-full min-[1520px]:w-auto">
                <div class="flex flex-col min-[1520px]:flex-row gap-2 w-full min-[1520px]:w-auto">

                    {{-- Tombol Print/Export --}}
                    <x-buttons.print-dropdown
                        :excelRoute="route('reimburse.export.excel')"
                        :pdfRoute="route('reimburse.export.pdf')"
                        :queryParams="[
                            'search' => request('search'),
                            'status' => request('status'),
                            'month'  => request('month'),
                            'year'   => request('year'),
                        ]"
                        responsive="custom" />

                    {{-- Tombol Tambah (Super Admin only) --}}
                    {{-- Hanya role superadmin yang boleh membuat pengajuan
                         reimburse baru; selain itu tombol tidak dirender. --}}
                    @if (Auth::user()->role === 'superadmin')
                        <x-buttons.add-button modalId="addModal" text="Tambah Reimburse" responsive="custom" />
                    @endif

                    {{-- Dropdown Persetujuan (Admin only) --}}
                    {{-- Alur: admin memilih baris reimburse lalu membuka
                         dropdown ini (Setujui/Tolak). Tombol dinonaktifkan
                         sampai JS menyalakannya setelah ada seleksi. --}}
                    @if (Auth::user()->role === 'admin')
                        <div class="relative inline-block text-left w-full min-[1520px]:w-auto">
                            <button type="button" id="approval-dropdown-button" disabled
                                class="w-full min-[1520px]:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium opacity-50 cursor-not-allowed">
                                <i class="fa-solid fa-check-circle"></i>
                                <span>Aksi Persetujuan</span>
                                <i class="fa-solid fa-chevron-down text-xs ml-auto min-[1520px]:ml-0"></i>
                            </button>

                            <div id="approval-dropdown-menu"
                                class="hidden absolute left-0 min-[1520px]:right-0 min-[1520px]:left-auto mt-2 w-full min-[1520px]:w-48 rounded-lg shadow-lg bg-surface-base border border-border-strong z-50">
                                <div class="py-1" role="menu">
                                    <button type="button" onclick="openModal('approveModal')"
                                        class="flex items-center gap-3 w-full px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                                        <i class="fa-solid fa-check text-success w-4"></i>
                                        <span>Setujui</span>
                                    </button>
                                    <button type="button" onclick="openModal('rejectModal')"
                                        class="flex items-center gap-3 w-full px-4 py-2 text-sm text-text-primary hover:bg-surface-hover transition-colors duration-150">
                                        <i class="fa-solid fa-times text-error w-4"></i>
                                        <span>Tolak</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Tombol Hapus --}}
                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />
                </div>
            </div>
        </div>

        {{-- ─── Ringkasan Data Terpilih (Admin) ────────────────────────────── --}}
        {{-- Hanya dirender untuk admin: menampilkan jumlah & total nominal
             reimburse yang dicentang; isinya diperbarui oleh JS saat
             seleksi checkbox berubah. --}}
        @if (Auth::user()->role === 'admin')
            <div id="selected-info" class="hidden mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-text-primary">
                    <span id="selected-count">0</span> reimburse terpilih | Total: <strong id="selected-total">Rp 0</strong>
                </p>
            </div>
        @endif

        {{-- ─── Tabel Reimbursement ─────────────────────────────────────────── --}}
        @include('components.finance.reimburse.table', ['reimburses' => $reimburses])

    </div>

    {{-- ─── Pagination ───────────────────────────────────────────────────────── --}}
    <x-pagination :paginator="$reimburses" />

    {{-- ─── Modal Tambah (Super Admin only) ────────────────────────────────── --}}
    @if (Auth::user()->role === 'superadmin')
        @include('components.finance.reimburse.add-modal')
    @endif

    {{-- ─── Modal Edit per baris (Super Admin only, status draft) ─────────── --}}
    {{-- Alur: hanya superadmin yang boleh mengedit, dan hanya reimburse
         berstatus 'draft' yang masih bisa diubah (sudah disetujui/ditolak
         tidak boleh diedit lagi). --}}
    @foreach ($reimburses as $reimburse)
        @if ($reimburse->status === 'draft' && Auth::user()->role === 'superadmin')
            @include('components.finance.reimburse.edit-modal', ['reimburse' => $reimburse])
        @endif
    @endforeach

    {{-- ─── Modal Konfirmasi Approve (Admin only) ─────────────────────────── --}}
    @if (Auth::user()->role === 'admin')
        @include('components.finance.reimburse.approve-modal')
        @include('components.finance.reimburse.reject-modal')
    @endif

    {{-- ─── Modal Konfirmasi Hapus ──────────────────────────────────────────── --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah Anda yakin ingin menghapus reimburse yang dipilih?
    </x-modal>

    {{-- ─── JavaScript Module ───────────────────────────────────────────────── --}}
    @vite('resources/js/pages/finance/reimburse/index.js')
@endsection
