{{--
    Halaman Data Absensi

    Menampilkan daftar absensi karyawan dengan fitur:
    - Pencarian (nama karyawan, kode, atau tanggal)
    - Tambah data (bulk create untuk multiple karyawan)
    - Edit data (single record)
    - Hapus data (bulk delete)
    - Pagination

    Variables:
    - $attendances: LengthAwarePaginator attendance records
    - $employees: Collection of all employees for form selects
    - $search: Current search keyword (nullable)
    - $existingAttendance: Array of existing attendance for duplicate validation
--}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Absensi')

@section('content')
    <div class="bg-white p-4 sm:p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Data Absensi</h1>

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

        {{-- Komponen Tabel --}}
        @include('components.sdm.attendance.table', ['attendances' => $attendances])
    </div>

    {{-- Paginasi --}}
    <x-pagination :paginator="$attendances" />

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

    {{-- JavaScript --}}
    @include('partials.sdm.attendance-scripts')
@endsection
