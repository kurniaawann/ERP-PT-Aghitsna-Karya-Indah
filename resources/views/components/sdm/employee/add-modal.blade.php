{{-- Modal Tambah Karyawan (massal).
     Mendukung penambahan beberapa karyawan sekaligus. Setiap baris memuat
     satu data karyawan; tombol "Tambah Karyawan Lagi" menambahkan baris baru
     melalui template dinamis (di-handle resources/js/pages/sdm/employee/index.js). --}}
<x-modal id="addModal" title="Tambah Karyawan" action="{{ route('employee.store') }}" method="POST"
    buttonText="Simpan" formId="addEmployeesForm" size="4xl">

    <p class="text-sm text-text-secondary">Isi data karyawan. Untuk menambahkan lebih dari satu, klik
        "Tambah Karyawan Lagi".</p>

    <div id="employeesContainer" class="space-y-4">
        @include('components.sdm.employee.row-form', ['index' => 0, 'divisions' => $divisions])
    </div>

    <button type="button" id="addEmployeeRowBtn"
        class="w-full flex items-center justify-center gap-2 border-2 border-dashed border-primary text-primary rounded p-3 hover:bg-primary-light transition-colors">
        <i class="fa-solid fa-plus"></i> Tambah Karyawan Lagi
    </button>
</x-modal>

{{-- Template baris karyawan untuk penambahan dinamis.
     Placeholder '__INDEX__' diganti dengan nomor urut oleh JavaScript. --}}
<template id="employeeRowTemplate">
    @include('components.sdm.employee.row-form', ['index' => '__INDEX__', 'divisions' => $divisions])
</template>
