{{--
    Edit Payroll Draft Modal (gaya Filament)

    Modal form untuk mengubah payroll berstatus 'draft' (sebelum dibayar).
    Yang bisa diubah: project_name (wajib) dan notes (opsional).
    Read-only: nama karyawan, periode, upah per hari, upah bersih.
    Validasi server: UpdatePayrollRequest.

    Included from: pages/sdm/payroll.blade.php (inside @foreach loop)
--}}
@php($pid = $payroll->id)
<x-fi.modal id="editModal-{{ $pid }}" heading="Edit Payroll Draft" :description="$payroll->employee->name" icon="pencil-square"
    width="xl" :action="route('payroll.update', $pid)" method="PUT" submitLabel="Update">

    <div class="flex gap-3 rounded-lg bg-amber-50 p-4 text-sm ring-1 ring-inset ring-amber-600/20">
        <x-fi.icon name="exclamation-triangle" class="h-5 w-5 shrink-0 text-amber-600" />
        <div class="text-amber-800">
            <p class="font-semibold">Payroll draft hanya bisa diubah sebelum dibayar.</p>
            <p class="mt-0.5">Data absensi, upah pokok, dan perhitungan inti tetap terkunci. Yang dapat diubah hanya data
                administratif.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        <x-fi.field label="Nama Karyawan">
            <x-fi.input :value="$payroll->employee->name" disabled />
        </x-fi.field>
        <x-fi.field label="Periode">
            <x-fi.input :value="$payroll->formatted_period" disabled />
        </x-fi.field>
        <x-fi.field label="Upah Per Hari">
            <x-fi.input :value="number_format($payroll->base_salary, 0, ',', '.')" prefix="Rp" disabled />
        </x-fi.field>
        <x-fi.field label="Upah Bersih">
            <x-fi.input :value="number_format($payroll->net_salary, 0, ',', '.')" prefix="Rp" disabled />
        </x-fi.field>
    </div>

    <x-fi.field label="Nama Proyek" for="edit-payroll-project-{{ $pid }}" required>
        <x-fi.input id="edit-payroll-project-{{ $pid }}" name="project_name" placeholder="Masukkan nama proyek"
            maxlength="255" required :value="old('project_name', $payroll->project_name)"
            oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')" oninput="this.setCustomValidity('')" />
    </x-fi.field>

    <x-fi.field label="Catatan" for="edit-payroll-notes-{{ $pid }}">
        <x-fi.textarea id="edit-payroll-notes-{{ $pid }}" name="notes" rows="2"
            placeholder="Catatan tambahan (opsional)">{{ old('notes', $payroll->notes) }}</x-fi.textarea>
    </x-fi.field>
</x-fi.modal>
