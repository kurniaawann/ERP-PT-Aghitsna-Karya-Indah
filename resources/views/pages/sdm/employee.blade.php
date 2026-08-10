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
                <x-filters.search-input :value="request('search')" placeholder="Cari karyawan..." />
            </form>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
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
