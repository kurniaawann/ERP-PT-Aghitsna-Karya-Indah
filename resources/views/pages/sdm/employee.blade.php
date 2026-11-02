{{-- =====================================================================
     Halaman: Data Karyawan (employee, gaya Filament)
     Tujuan: Menampilkan daftar karyawan dengan filter proyek & jenis,
             pencarian, tambah (halaman form), edit (modal), ubah proyek
             massal, hapus massal / satu data, dan pagination.

     Data dari EmployeeController@index:
     - $employees : LengthAwarePaginator daftar karyawan (sudah dipaginasi)
     - $search    : Kata kunci pencarian saat ini (nullable)
     - $divisions : Collection divisi (opsi Divisi di modal Edit)

     Komponen yang di-include:
     - components.sdm.employee.table      : toolbar + tabel + paginasi
     - components.sdm.employee.edit-modal : modal edit per karyawan (loop)
     - x-fi.modal / x-fi.delete-row-modal : konfirmasi hapus massal / satu data
     Tambah Karyawan → route('employee.create') (pages/sdm/employee-form).

     JS yang di-load:
     - @vite('resources/js/pages/sdm/employee/index.js')
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Karyawan')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi --}}
        <x-fi.page-header heading="Data Karyawan"
            :breadcrumbs="['Human Resource' => null, 'Data Karyawan' => route('employee.index')]">
            <x-slot:actions>
                <x-fi.button icon="plus" :href="route('employee.create')">Tambah Karyawan</x-fi.button>
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tabel (+ filter, aksi massal, paginasi) --}}
        @include('components.sdm.employee.table', ['employees' => $employees])
    </div>

    {{-- SECTION: Modal Edit per karyawan --}}
    @foreach ($employees as $employee)
        @include('components.sdm.employee.edit-modal', ['employee' => $employee])
    @endforeach

    {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?
    </x-fi.modal>

    {{-- SECTION: Modal Konfirmasi Hapus 1 Karyawan (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu kode ke route('employee.destroy') yang sama dengan hapus massal (ids[]). --}}
    <x-fi.delete-row-modal :action="route('employee.destroy')" field="ids" heading="Hapus Karyawan"
        warning="Karyawan yang masih memiliki data payroll atau kasbon tidak dapat dihapus." />

    {{-- SECTION: Ubah Proyek Massal --}}
    <form id="bulkProjectForm" method="POST" action="{{ route('employee.bulk-update-project') }}" class="hidden">
        @csrf
        <input type="hidden" id="bulkProjectIds" name="ids">
        <input type="hidden" id="bulkClearProject" name="clear_project" value="0">
        <input type="hidden" id="bulkProjectValue" name="project_name">
    </form>
    <x-fi.modal id="bulkProjectModal" heading="Ubah Proyek Massal" icon="folder-open" width="md">
        <div class="flex flex-col gap-y-5">
            <p class="text-sm text-gray-500">
                Ubah proyek untuk <span id="bulkProjectCount" class="font-semibold text-gray-950">0</span> karyawan
                terpilih. Hanya kolom proyek yang diubah.
            </p>

            <x-fi.field label="Proyek Tujuan" hint="Opsional">
                <x-fi.project-dropdown name="" dropdown-id="bulkProjectDropdown" input-id="bulkProjectHidden"
                    clearable inline />
            </x-fi.field>

            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" id="clearProjectCheckbox" class="fi-checkbox mt-0.5">
                <span class="text-sm">
                    <span class="font-medium text-gray-950">Kosongkan proyek</span>
                    <span class="block text-gray-500">Hapus proyek dari karyawan terpilih (tidak menghapus data karyawan)</span>
                </span>
            </label>
        </div>

        {{-- Tombol di kanan: [Batal] [Simpan] (Simpan paling kanan) --}}
        <div class="-mx-6 -mb-6 mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 px-6 py-4">
            <x-fi.button color="gray" onclick="closeModal('bulkProjectModal')">Batal</x-fi.button>
            <x-fi.button id="confirm-btn-bulkProjectModal" onclick="submitBulkProjectForm()">Simpan</x-fi.button>
        </div>
    </x-fi.modal>

    @push('scripts')
        {{-- window.employeeConfig: route dropdown proyek untuk modul JS karyawan --}}
        <script>
            window.employeeConfig = {
                projectsDropdownUrl: '{{ route('employee.projects-dropdown') }}',
            };
        </script>
        @vite('resources/js/pages/sdm/employee/index.js')
    @endpush
@endsection
