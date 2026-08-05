{{-- =====================================================================
     Halaman: Data Lembur (overtime)
     Tujuan: Menampilkan daftar data lembur karyawan dengan fitur pencarian,
             tambah, edit, hapus massal, dan pagination.
             Catatan: data lembur disimpan pada tabel attendances
             dengan status = 'lembur'.

     Data dari OvertimeController@index:
     - $overtimes         : LengthAwarePaginator data lembur (sudah dipaginasi)
     - $employees         : Collection seluruh karyawan untuk pilihan di form
                            modal tambah/edit
     - $search            : Kata kunci pencarian saat ini (nullable)
     - $existingAttendance: Data absensi yang sudah ada untuk validasi duplikat
                            di sisi klien (via window.overtimeExistingAttendance)

     Komponen yang di-include:
     - components.sdm.overtime.table      : tabel daftar lembur
     - components.sdm.overtime.add-modal  : modal tambah lembur
     - components.sdm.overtime.edit-modal : modal edit per lembur (loop)
     - x-pagination                       : kontrol pagination
     - x-modal (deleteModal)              : konfirmasi hapus massal

     JS yang di-load:
     - @vite('resources/js/pages/sdm/overtime/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Lembur')

@section('content')
    {{-- ============================================================
         SECTION: Header
         Judul halaman + toolbar aksi.
         ============================================================ --}}
    {{-- Page Header --}}
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Lembur</h1>

        {{-- ============================================================
             SECTION: Filter / Toolbar
             Form pencarian (GET ke overtime.index) + tombol aksi.
             ============================================================ --}}
        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Search Form --}}
            <form method="GET" action="{{ route('overtime.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari lembur..." />
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
             Menampilkan daftar data lembur beserta checkbox seleksi massal.
             ============================================================ --}}
        {{-- Overtime Table Component --}}
        @include('components.sdm.overtime.table', ['overtimes' => $overtimes])

    </div>

    {{-- ============================================================
         SECTION: Pagination
         Kontrol navigasi halaman daftar lembur.
         ============================================================ --}}
    {{-- Pagination --}}
    <x-pagination :paginator="$overtimes" />

    {{-- ============================================================
         SECTION: Modals
         - add-modal   : form tambah lembur.
         - edit-modal  : satu modal edit per lembur (loop).
         - deleteModal : konfirmasi hapus massal.
         ============================================================ --}}
    {{-- Add Modal --}}
    @include('components.sdm.overtime.add-modal', ['employees' => $employees])

    {{-- Edit Modals (one per overtime record on current page) --}}
    @foreach ($overtimes as $overtime)
        @include('components.sdm.overtime.edit-modal', ['overtime' => $overtime])
    @endforeach

    {{-- Bulk Delete Confirmation Modal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- ============================================================
         SECTION: Scripts
         Data absensi yang sudah ada diteruskan ke JS melalui
         window.overtimeExistingAttendance untuk validasi duplikat.
         Modul JS halaman lembur di-load via Vite.
         ============================================================ --}}
    {{-- Pass existing attendance data to JavaScript for client-side duplicate validation --}}
    <script>
        window.overtimeExistingAttendance = @json($existingAttendance ?? []);
    </script>

    {{-- JavaScript --}}
    @vite('resources/js/pages/sdm/overtime/index.js')
@endsection
