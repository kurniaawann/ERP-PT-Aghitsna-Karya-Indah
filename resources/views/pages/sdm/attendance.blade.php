{{-- =====================================================================
     Halaman: Data Absensi (attendance)
     Tujuan: Menampilkan daftar absensi karyawan dengan fitur pencarian,
             tambah massal (bulk create), edit per record, hapus massal,
             dan pagination.

     Data dari AttendanceController@index:
     - $attendances        : LengthAwarePaginator record absensi (sudah dipaginasi)
     - $employees          : Collection seluruh karyawan untuk pilihan di form modal
     - $search             : Kata kunci pencarian saat ini (nullable)
     - $existingAttendance : Data absensi yang sudah ada, untuk validasi duplikat
                             di sisi klien (via window.attendanceConfig)

     Komponen yang di-include:
     - components.sdm.attendance.table      : tabel daftar absensi
     - components.sdm.attendance.add-modal  : modal tambah massal
     - components.sdm.attendance.edit-modal : modal edit per record (loop)
     - x-pagination                         : kontrol pagination
     - x-modal (deleteModal)                : konfirmasi hapus massal

     JS yang di-load:
     - @vite('resources/js/pages/sdm/attendance/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Absensi')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Absensi</h1>

        {{-- ============================================================
             SECTION: Header / Toolbar
             Berisi judul halaman, form pencarian (GET ke attendance.index),
             serta tombol aksi hapus & tambah data.
             ============================================================ --}}
        {{-- Pencarian & Tombol Aksi --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian --}}
            <form method="GET" action="{{ route('attendance.index') }}"
                class="w-full min-[1280px]:w-auto min-[1280px]:flex-1 flex flex-col min-[1280px]:flex-row gap-3">
                <x-filters.search-input :value="request('search')" placeholder="Cari absensi..." />
            </form>

            {{-- Aksi: Hapus & Tambah --}}
            <div class="flex items-center gap-2 mt-2 min-[1280px]:mt-0 w-full min-[1280px]:w-auto">
                <div class="flex flex-col min-[1280px]:flex-row gap-2 w-full min-[1280px]:w-auto">
                    <x-buttons.delete-button modalId="deleteModal" />
                    <x-buttons.add-button modalId="addModal" text="Tambah Data" />
                </div>
            </div>
        </div>

        {{-- ============================================================
             SECTION: Table
             Menampilkan daftar absensi. Komponen tabel juga menyertakan
             checkbox untuk seleksi massal.
             ============================================================ --}}
        {{-- Komponen Tabel --}}
        @include('components.sdm.attendance.table', ['attendances' => $attendances])
    </div>

    {{-- ============================================================
         SECTION: Pagination
         Kontrol navigasi halaman. Karena query 'search' di-append,
         pindah halaman tetap mempertahankan kata kunci pencarian.
         ============================================================ --}}
    {{-- Paginasi --}}
    <x-pagination :paginator="$attendances" />

    {{-- ============================================================
         SECTION: Modals
         - add-modal   : form tambah massal (pilih karyawan + rentang tanggal)
         - edit-modal  : satu modal per record absensi (loop)
         - deleteModal : konfirmasi hapus massal sebelum submitDeleteForm()
         ============================================================ --}}
    {{-- Modal Tambah --}}
    @include('components.sdm.attendance.add-modal', ['employees' => $employees])

    {{-- Modal Edit untuk setiap data absensi --}}
    @foreach ($attendances as $attendance)
        @include('components.sdm.attendance.edit-modal', ['attendance' => $attendance, 'employees' => $employees])
    @endforeach

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-modal>

    {{-- Pass existing attendance data to JavaScript for client-side duplicate validation --}}
    <script>
        window.attendanceConfig = @json([
            'existingAttendance' => $existingAttendance ?? [],
        ]);
    </script>

    {{-- ============================================================
         SECTION: Scripts
         Modul JS halaman absensi (diproses oleh Vite). Data konfigurasi
         diteruskan lewat window.attendanceConfig.
         ============================================================ --}}
    {{-- JavaScript --}}
    @vite('resources/js/pages/sdm/attendance/index.js')
@endsection
