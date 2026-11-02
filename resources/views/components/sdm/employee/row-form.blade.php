{{-- ═══════════════════════════════════════════════════════════════════════════
     BARIS FORM SATU KARYAWAN (gaya Filament) — halaman Tambah Karyawan.
     $index : indeks baris; pada <template> berisi '__INDEX__' yang diganti JS.
     $row   : nilai awal (old input setelah validasi gagal), opsional.
     $divisions : daftar Divisi (opsi Divisi).

     Hook JS (resources/js/pages/sdm/employee/index.js): .employee-row,
     .employee-row-number, .employee-remove-row, .employment-type-select,
     .wage-field-harian / .wage-field-bulanan / .harian-extra-field,
     .daily-wage-input, .base-salary-input, .monthly-currency-input,
     .project-dropdown (components/project-dropdown.js).
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    $row = $row ?? [];
    $field = fn ($key) => "employees[{$index}][{$key}]";
    $rowId = fn ($key) => "employees-{$index}-{$key}";
    $type = ($row['employment_type'] ?? 'harian') === 'bulanan' ? 'bulanan' : 'harian';
@endphp

<div class="employee-row rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5" data-row-index="{{ $index }}">
    <header class="flex items-center justify-between gap-3 border-b border-gray-200 px-6 py-3">
        <h2 class="flex items-center gap-2 text-base font-semibold text-gray-950">
            <x-fi.icon name="user" class="h-5 w-5 text-gray-400" />
            Karyawan <span class="employee-row-number"></span>
        </h2>
        <button type="button" title="Hapus baris ini"
            class="employee-remove-row inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-medium text-red-600 hover:bg-red-50">
            <x-fi.icon name="trash" class="h-4 w-4" /> Hapus
        </button>
    </header>

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 p-6 sm:grid-cols-2">
        <x-fi.field label="Nama Lengkap" :for="$rowId('name')" required>
            <x-fi.input :id="$rowId('name')" :name="$field('name')" :value="$row['name'] ?? ''"
                placeholder="Masukkan nama lengkap" required maxlength="255" />
        </x-fi.field>

        <x-fi.field label="Jabatan" :for="$rowId('position')" hint="Opsional">
            <x-fi.input :id="$rowId('position')" :name="$field('position')" :value="$row['position'] ?? ''"
                placeholder="Masukkan jabatan (opsional)" maxlength="100" />
        </x-fi.field>

        <x-fi.field label="Jenis Karyawan" :for="$rowId('employment_type')" required>
            <x-fi.select :id="$rowId('employment_type')" :name="$field('employment_type')"
                class="employment-type-select" data-row-index="{{ $index }}" data-fi-select
                :value="$type" :options="['harian' => 'TUKANG PROYEK', 'bulanan' => 'KARYAWAN KANTOR']" />
        </x-fi.field>

        {{-- Tukang proyek: upah per hari --}}
        <div class="wage-field-harian">
            <x-fi.field label="Upah Per Hari" :for="$rowId('daily_wage')" required>
                <x-fi.input :id="$rowId('daily_wage')" inputmode="numeric" :name="$field('daily_wage')" prefix="Rp"
                    class="daily-wage-input" :value="$row['daily_wage'] ?? ''" placeholder="Masukkan upah per hari"
                    required min="0" />
            </x-fi.field>
        </div>

        {{-- Karyawan kantor: gaji pokok & tunjangan harian --}}
        <div class="wage-field-bulanan hidden sm:col-span-2">
            <div class="grid grid-cols-1 gap-x-4 gap-y-5 rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5 sm:grid-cols-2">
                <x-fi.field label="Gaji Pokok / Bulan" :for="$rowId('base_salary')" required>
                    <x-fi.input :id="$rowId('base_salary')" inputmode="numeric" :name="$field('base_salary')" prefix="Rp"
                        class="base-salary-input" :value="$row['base_salary'] ?? ''"
                        placeholder="Masukkan gaji pokok per bulan" min="0" />
                </x-fi.field>

                <x-fi.field label="Status Karyawan" :for="$rowId('status')" hint="Opsional">
                    <x-fi.input :id="$rowId('status')" :name="$field('status')" :value="$row['status'] ?? ''"
                        placeholder="cth: Karyawan Tetap / Kontrak" maxlength="100" />
                </x-fi.field>

                <x-fi.field label="Uang Transport / Hari" :for="$rowId('transport_rate')" hint="Opsional">
                    <x-fi.input :id="$rowId('transport_rate')" inputmode="numeric" :name="$field('transport_rate')" prefix="Rp"
                        class="monthly-currency-input" :value="$row['transport_rate'] ?? ''" placeholder="cth: 5.000" min="0" />
                </x-fi.field>

                <x-fi.field label="Uang Makan / Hari" :for="$rowId('meal_rate')" hint="Opsional">
                    <x-fi.input :id="$rowId('meal_rate')" inputmode="numeric" :name="$field('meal_rate')" prefix="Rp"
                        class="monthly-currency-input" :value="$row['meal_rate'] ?? ''" placeholder="cth: 10.000" min="0" />
                </x-fi.field>

                <x-fi.field label="UMP" :for="$rowId('ump')" hint="Opsional, dasar potongan BPJS/JHT/JPN">
                    <x-fi.input :id="$rowId('ump')" inputmode="numeric" :name="$field('ump')" prefix="Rp"
                        class="monthly-currency-input" :value="$row['ump'] ?? ''" placeholder="cth: 4.200.000" min="0" />
                </x-fi.field>
            </div>
        </div>

        {{-- Detail tukang proyek (disembunyikan untuk karyawan kantor) --}}
        <div class="harian-extra-field">
            <x-fi.field label="No. Telepon" :for="$rowId('phone')">
                <x-fi.input :id="$rowId('phone')" :name="$field('phone')" :value="$row['phone'] ?? ''"
                    placeholder="Masukkan no. telepon (opsional)" maxlength="20" />
            </x-fi.field>
        </div>

        <div class="harian-extra-field">
            <x-fi.field label="Divisi" :for="$rowId('division')" hint="Opsional">
                <x-fi.select :id="$rowId('division')" :name="$field('division')" data-fi-select="search"
                    placeholder="-- Pilih Divisi --" :value="$row['division'] ?? ''"
                    :options="$divisions->pluck('name', 'name')" />
            </x-fi.field>
        </div>

        <div class="harian-extra-field">
            <x-fi.field label="Proyek" hint="Opsional">
                <x-fi.project-dropdown :name="$field('project_name')" :value="$row['project_name'] ?? ''" clearable />
            </x-fi.field>
        </div>

        <div class="harian-extra-field sm:col-span-2">
            <x-fi.field label="Alamat" :for="$rowId('address')" hint="Opsional">
                <x-fi.textarea :id="$rowId('address')" :name="$field('address')" rows="2"
                    placeholder="Masukkan alamat">{{ $row['address'] ?? '' }}</x-fi.textarea>
            </x-fi.field>
        </div>
    </div>
</div>
