{{-- Modal Tambah Karyawan --}}
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
        <label class="block text-text-primary mb-1">Proyek <span class="text-text-tertiary text-sm">(Opsional)</span></label>
        <div class="project-dropdown relative" data-route="{{ route('employee.projects-dropdown') }}">
            <input type="hidden" name="project_name" class="project-dropdown-hidden" value="{{ old('project_name') }}">
            <button type="button"
                class="project-dropdown-toggle w-full border border-border-strong rounded p-2 bg-surface-base text-text-input flex items-center justify-between">
                <span class="project-dropdown-label text-text-input">-- Pilih Proyek --</span>
                <span class="text-text-tertiary text-xs">▼</span>
            </button>
            <div class="project-dropdown-menu absolute z-50 w-full bg-surface-base border border-border-strong rounded-lg shadow-lg mt-1 hidden">
                <div class="p-2 border-b border-border-light">
                    <input type="text" class="project-dropdown-search w-full border border-border-light rounded px-2 py-1.5 text-sm bg-surface-base text-text-input"
                        placeholder="Cari nama proyek...">
                </div>
                <div class="project-dropdown-list max-h-60 overflow-y-auto">
                    <div class="p-2 text-sm text-text-secondary">Silakan klik untuk memuat data...</div>
                </div>
                <div class="p-2 border-t border-border-light">
                    <button type="button" class="project-dropdown-clear text-sm text-error hover:text-error">
                        Reset (- Tidak Ada Proyek -)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">No. Telepon</label>
        <input type="text" name="phone" class="w-full border rounded p-2" placeholder="Masukkan no. telepon (opsional)"
            maxlength="20">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Alamat <span class="text-error">*</span></label>
        <textarea name="address" class="w-full border rounded p-2" placeholder="Masukkan alamat" rows="3" required></textarea>
    </div>
</x-modal>
