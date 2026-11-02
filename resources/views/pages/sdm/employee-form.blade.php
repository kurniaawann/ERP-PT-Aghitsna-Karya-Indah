{{-- =====================================================================
     Halaman: Tambah Karyawan (gaya Filament) — pengganti modal Tambah lama.
     Mendukung penambahan beberapa karyawan sekaligus: tiap kartu = satu
     karyawan; "Tambah Karyawan Lagi" menambah kartu baru dari <template>
     #employeeRowTemplate (resources/js/pages/sdm/employee/index.js).
     Submit → route('employee.store') (logic sama seperti sebelumnya).
     Bila validasi gagal, isian dikembalikan dari old('employees').

     Data dari EmployeeController@create:
     - $divisions : Collection divisi (opsi Divisi)
     ===================================================================== --}}
@extends('layouts.app')

@php
    $indexUrl = route('employee.index');
    $oldRows = array_values((array) old('employees', []));
    $rows = $oldRows ?: [[]];
@endphp

@section('title', 'PT Aghitsna Karya Indah - Tambah Karyawan')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman --}}
        <x-fi.page-header heading="Tambah Karyawan"
            :breadcrumbs="['Human Resource' => null, 'Data Karyawan' => $indexUrl, 'Tambah' => null]" />

        {{-- SECTION: Ringkasan error validasi server --}}
        <x-fi.validation-errors />

        <form id="addEmployeesForm" method="POST" action="{{ route('employee.store') }}" class="flex flex-col gap-y-6">
            @csrf

            <p class="text-sm text-gray-500">Isi data karyawan. Untuk menambahkan lebih dari satu, klik
                "Tambah Karyawan Lagi".</p>

            {{-- SECTION: Kartu karyawan (dinamis) --}}
            <div id="employeesContainer" class="flex flex-col gap-y-6">
                @foreach ($rows as $i => $row)
                    @include('components.sdm.employee.row-form', ['index' => $i, 'row' => $row, 'divisions' => $divisions])
                @endforeach
            </div>

            <button type="button" id="addEmployeeRowBtn"
                class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 p-4 text-sm font-semibold text-primary transition hover:border-primary hover:bg-blue-50">
                <x-fi.icon name="plus" class="h-5 w-5" /> Tambah Karyawan Lagi
            </button>

            {{-- SECTION: Aksi Form (kanan bawah: [Batal] [Simpan]) --}}
            <div class="flex flex-wrap items-center justify-end gap-3">
                <x-fi.button color="gray" :href="$indexUrl">Batal</x-fi.button>
                <x-fi.button type="submit" id="submit-btn-addEmployees">Simpan</x-fi.button>
            </div>
        </form>
    </div>

    {{-- Template kartu karyawan untuk penambahan dinamis.
         Placeholder '__INDEX__' diganti dengan nomor urut oleh JavaScript. --}}
    <template id="employeeRowTemplate">
        @include('components.sdm.employee.row-form', ['index' => '__INDEX__', 'row' => [], 'divisions' => $divisions])
    </template>

    @push('scripts')
        <script>
            window.employeeConfig = {
                projectsDropdownUrl: '{{ route('employee.projects-dropdown') }}',
            };
        </script>
        @vite('resources/js/pages/sdm/employee/index.js')
    @endpush
@endsection
