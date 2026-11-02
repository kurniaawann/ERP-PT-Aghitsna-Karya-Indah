{{-- =====================================================================
     Halaman: Data Absensi (attendance, gaya Filament)
     Tujuan: Menampilkan daftar absensi karyawan dengan filter bulan/tahun/
             minggu, pencarian, tambah massal (halaman form), edit per
             record (modal), hapus massal / satu data, dan pagination.

     Data dari AttendanceController@index:
     - $attendances : LengthAwarePaginator record absensi (sudah dipaginasi)
     - $employees   : karyawan harian untuk pilihan di modal Edit
     - $search      : Kata kunci pencarian saat ini (nullable)

     Komponen yang di-include:
     - components.sdm.attendance.table      : toolbar + tabel + paginasi
     - components.sdm.attendance.edit-modal : modal edit per record (loop)
     - x-fi.modal / x-fi.delete-row-modal   : konfirmasi hapus massal / satu data
     Tambah Absensi → route('attendance.create') (pages/sdm/attendance-form).

     JS yang di-load:
     - @vite('resources/js/pages/sdm/attendance/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Absensi')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi --}}
        <x-fi.page-header heading="Data Absensi"
            :breadcrumbs="['Human Resource' => null, 'Data Absensi' => route('attendance.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" :href="route('attendance.create')">Tambah Absensi</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel (+ filter, hapus massal, paginasi) --}}
        @include('components.sdm.attendance.table', ['attendances' => $attendances])
    </div>

    {{-- SECTION: Modal Edit untuk setiap data absensi --}}
    @foreach ($attendances as $attendance)
        @include('components.sdm.attendance.edit-modal', ['attendance' => $attendance, 'employees' => $employees])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Absensi (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('attendance.destroy') yang sama dengan hapus massal (ids[]). --}}
    <x-fi.delete-row-modal :action="route('attendance.destroy')" field="ids" heading="Hapus Absensi" />

    @push('scripts')
        {{-- Konfigurasi filter minggu (route payroll.get-weeks + filter aktif) --}}
        @php
            $attendanceConfig = [
                'existingAttendance' => $existingAttendance ?? [],
                'getWeeksUrl' => route('payroll.get-weeks'),
                'currentYear' => (int) date('Y'),
                'filterMonth' => request('month'),
                'filterYear' => request('year'),
                'filterWeek' => request('week_number'),
            ];
        @endphp
        <script>
            window.attendanceConfig = @json($attendanceConfig);
        </script>
        @vite('resources/js/pages/sdm/attendance/index.js')
    @endpush
@endsection
