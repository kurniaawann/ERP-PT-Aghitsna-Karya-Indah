{{-- Add Employee Modal --}}
<x-modal id="addModal" title="Tambah Karyawan" action="{{ route('employee.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Lengkap <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Masukkan nama lengkap"
            required maxlength="255">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Upah Per Hari <span class="text-error">*</span></label>
        <input type="text" inputmode="numeric" name="daily_wage" class="w-full border rounded p-2 daily-wage-input"
            placeholder="Masukkan upah per hari" required min="0">
    </div>

    <x-forms.searchable-select name="division" label="Divisi" :required="true"
        placeholder="Cari divisi..."
        :options="$divisions->map(fn($d) => ['value' => $d->name, 'label' => $d->name])->values()"
        selected="{{ old('division') }}" />

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No. Telepon <span class="text-error">*</span></label>
        <input type="text" name="phone" class="w-full border rounded p-2" placeholder="Masukkan no. telepon"
            required maxlength="20">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alamat <span class="text-error">*</span></label>
        <textarea name="address" class="w-full border rounded p-2" placeholder="Masukkan alamat" rows="3" required></textarea>
    </div>
</x-modal>
