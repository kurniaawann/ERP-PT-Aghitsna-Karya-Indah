{{-- =====================================================================
     Halaman: Data Lembur (overtime, gaya Filament)
     Tujuan: Menampilkan daftar data lembur karyawan dengan filter bulan/tahun,
             pencarian, tambah, edit, hapus massal / satu data, dan pagination.
             Catatan: data lembur disimpan pada tabel attendances dengan
             status = 'lembur'.

     Data dari OvertimeController@index:
     - $overtimes          : LengthAwarePaginator data lembur (sudah dipaginasi)
     - $employees          : karyawan untuk pilihan di modal tambah/edit
     - $search, $month, $year : filter aktif (nullable)
     - $existingAttendance : absensi yang sudah ada untuk validasi sisi klien
                             (window.overtimeExistingAttendance)

     Komponen yang di-include:
     - x-sdm.allowance-tabs (admin)        : tab Lembur | Kasbon
     - components.sdm.overtime.table       : toolbar + tabel + paginasi
     - components.sdm.overtime.add-modal   : modal tambah lembur
     - components.sdm.overtime.edit-modal  : modal edit per lembur (loop)
     - x-fi.modal / x-fi.delete-row-modal  : konfirmasi hapus massal / satu data

     JS yang di-load:
     - @vite('resources/js/pages/sdm/overtime/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Lembur')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi --}}
        <x-fi.page-header heading="Data Lembur"
            :breadcrumbs="['Human Resource' => null, 'Tunjangan & Potongan' => null, 'Lembur' => route('overtime.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" onclick="openModal('addModal')">Tambah Lembur</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tab Lembur | Kasbon (admin) --}}
        @if (auth()->user()->isAdmin())
            <x-sdm.allowance-tabs active="overtime" />
        @endif

        {{-- SECTION: Tabel (+ filter, hapus massal, paginasi) --}}
        @include('components.sdm.overtime.table', ['overtimes' => $overtimes])
    </div>

    {{-- SECTION: Modal Tambah --}}
    @include('components.sdm.overtime.add-modal', ['employees' => $employees])

    {{-- SECTION: Modal Edit (satu per data lembur di halaman ini) --}}
    @foreach ($overtimes as $overtime)
        @include('components.sdm.overtime.edit-modal', ['overtime' => $overtime])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Lembur (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('overtime.destroy') yang sama dengan hapus massal (ids[]). --}}
    <x-fi.delete-row-modal :action="route('overtime.destroy')" field="ids" heading="Hapus Lembur" />

    @push('scripts')
        {{-- Absensi yang sudah ada → validasi duplikat sisi klien --}}
        <script>
            window.overtimeExistingAttendance = @json($existingAttendance ?? []);
        </script>
        @vite('resources/js/pages/sdm/overtime/index.js')
    @endpush
@endsection
