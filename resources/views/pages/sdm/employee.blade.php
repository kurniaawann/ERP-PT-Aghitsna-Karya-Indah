{{-- =====================================================================
     Halaman: Data Karyawan (employee)
     Tujuan: Menampilkan daftar karyawan dengan fitur pencarian, tambah,
             edit, hapus massal, dan pagination.

     Data dari EmployeeController@index:
     - $employees : LengthAwarePaginator daftar karyawan (sudah dipaginasi)
     - $search    : Kata kunci pencarian saat ini (nullable)
     - $divisions : Collection divisi (disediakan controller; dipakai oleh
                    form/select terkait divisi pada modul karyawan)

     Komponen yang di-include:
     - components.sdm.employee.table      : tabel daftar karyawan
     - components.sdm.employee.add-modal  : modal tambah karyawan
     - components.sdm.employee.edit-modal : modal edit per karyawan (loop)
     - x-pagination                       : kontrol pagination
     - x-modal (deleteModal)              : konfirmasi hapus massal

     JS yang di-load:
     - @vite('resources/js/pages/sdm/employee/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Karyawan')

@section('content')
    {{-- ============================================================
         SECTION: Header
         Judul halaman + toolbar aksi.
         ============================================================ --}}
    {{-- Page Header --}}
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Karyawan</h1>

        {{-- ============================================================
             SECTION: Filter / Toolbar
             Form pencarian (GET ke employee.index) + tombol aksi.
             ============================================================ --}}
        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Search Form --}}
            <form method="GET" action="{{ route('employee.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                {{-- Filter Proyek --}}
                {{-- Dropdown searchable (10 item per load) mengambil data proyek
                     dari Rekap Proyek; memilih proyek otomatis submit form. --}}
                <x-filters.project-filter :route="route('employee.projects-dropdown')"
                    :value="request('project_name')" dropdown-id="filter_project_name"
                    :auto-submit="true" />

                {{-- Filter Jenis Karyawan --}}
                {{-- Memfilter daftar karyawan berdasarkan jenis (Harian/Bulanan);
                     berubah langsung submit form pencarian. --}}
                <div class="w-full min-[1280px]:w-auto">
                    <label for="filter_employment_type" class="sr-only">Jenis Karyawan</label>
                    <select name="employment_type" id="filter_employment_type"
                        onchange="this.form.requestSubmit()"
                        class="block w-full min-[1280px]:w-44 rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light">
                        <option value="">Semua Jenis</option>
                        <option value="harian" @selected(request('employment_type') === 'harian')>Harian (Tukang)</option>
                        <option value="bulanan" @selected(request('employment_type') === 'bulanan')>Bulanan (Slip Gaji)</option>
                    </select>
                </div>

                <x-filters.search-input :value="request('search')" placeholder="Cari karyawan..." />
            </form>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
                    <button type="button" id="bulk-project-button" onclick="openBulkProjectModal()" disabled
                        class="w-full xl:w-auto flex items-center justify-center gap-2 bg-btn-edit hover:bg-btn-edit-hover text-white px-3 py-3.5 rounded-lg transition-colors duration-200 text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-btn-edit">
                        <i class="fa-solid fa-arrows-rotate w-4 h-4"></i>
                        <span>Ubah Proyek</span>
                    </button>
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- ============================================================
             SECTION: Table
             Menampilkan daftar karyawan beserta checkbox seleksi massal.
             ============================================================ --}}
        {{-- Employee Table Component --}}
        @include('components.sdm.employee.table', ['employees' => $employees])
    </div>

    {{-- ============================================================
         SECTION: Pagination
         Kontrol navigasi halaman daftar karyawan.
         ============================================================ --}}
    {{-- Pagination --}}
    <x-pagination :paginator="$employees" />

    {{-- ============================================================
         SECTION: Modals
         - add-modal   : form tambah karyawan baru.
         - edit-modal  : satu modal edit per karyawan (loop).
         - deleteModal : konfirmasi hapus massal.
         ============================================================ --}}
    {{-- Add Modal --}}
    @include('components.sdm.employee.add-modal')

    {{-- Edit Modals --}}
    @foreach ($employees as $employee)
        @include('components.sdm.employee.edit-modal', ['employee' => $employee])
    @endforeach

    {{-- Bulk Delete Confirmation Modal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Bulk Update Project Modal --}}
    <form id="bulkProjectForm" method="POST" action="{{ route('employee.bulk-update-project') }}" class="hidden">
        @csrf
        <input type="hidden" id="bulkProjectIds" name="ids">
        <input type="hidden" id="bulkClearProject" name="clear_project" value="0">
        <input type="hidden" id="bulkProjectValue" name="project_name">
    </form>
    <x-modal id="bulkProjectModal" title="Ubah Proyek Massal" :onConfirm="'submitBulkProjectForm()'"
        buttonText="Simpan">
        <p class="text-sm text-text-secondary mb-4">
            Ubah proyek untuk <span id="bulkProjectCount" class="font-semibold text-text-heading">0</span> karyawan
            terpilih. Hanya kolom proyek yang diubah.
        </p>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Proyek Tujuan <span class="text-text-tertiary text-sm">(Opsional)</span></label>
            <div class="project-dropdown relative" data-route="{{ route('employee.projects-dropdown') }}"
                id="bulkProjectDropdown">
                <input type="hidden" class="project-dropdown-hidden" id="bulkProjectHidden">
                <button type="button"
                    class="project-dropdown-toggle w-full border border-border-strong rounded p-2 bg-surface-base text-text-input flex items-center justify-between">
                    <span class="project-dropdown-label text-text-input">-- Pilih Proyek --</span>
                    <span class="text-text-tertiary text-xs">▼</span>
                </button>
                <div class="project-dropdown-menu absolute z-50 w-full bg-surface-base border border-border-strong rounded-lg shadow-lg mt-1 hidden">
                    <div class="p-2 border-b border-border-light">
                        <input type="text" class="project-dropdown-search w-full border border-border-light rounded px-2 py-1.5 text-sm bg-surface-base text-text-input"
                            placeholder="Cari nama proyek...">
                    </div>
                    <div class="project-dropdown-list max-h-60 overflow-y-auto">
                        <div class="p-2 text-sm text-text-secondary">Silakan klik untuk memuat data...</div>
                    </div>
                    <div class="p-2 border-t border-border-light">
                        <button type="button" class="project-dropdown-clear text-sm text-error hover:text-error">
                            Reset (- Tidak Ada Proyek -)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <label class="mt-3 flex items-start gap-2 cursor-pointer">
            <input type="checkbox" id="clearProjectCheckbox" class="mt-1 w-4 h-4 accent-error">
            <span class="text-sm text-text-primary">
                <span class="font-semibold text-text-heading">Kosongkan proyek</span>
                <span class="block text-xs text-text-tertiary">Hapus proyek dari karyawan terpilih (tidak menghapus data karyawan)</span>
            </span>
        </label>
    </x-modal>

    {{-- ============================================================
         SECTION: Scripts
         Modul JS halaman karyawan (diproses oleh Vite) — menangani
         interaksi modal tambah/edit dan submit hapus massal.
         ============================================================ --}}
    {{-- ============================================================
         SECTION: Scripts
         window.employeeConfig menyuplai route dropdown proyek ke modul
         JS karyawan (dipakai dropdown proyek dengan pencarian &
         pagination infinite scroll). Modul JS halaman karyawan
         di-load via Vite.
         ============================================================ --}}
    {{-- Pass server data to JavaScript --}}
    <script>
        window.employeeConfig = {
            projectsDropdownUrl: '{{ route("employee.projects-dropdown") }}',
        };
    </script>

    {{-- JavaScript --}}
    @vite('resources/js/pages/sdm/employee/index.js')
@endsection
