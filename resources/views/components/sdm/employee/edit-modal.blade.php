{{-- ═══════════════════════════════════════════════════════════════════════════
     MODAL EDIT KARYAWAN (gaya Filament) — satu modal per karyawan.
     Hook JS (resources/js/pages/sdm/employee/index.js):
     [id^="editModal-"] form, .edit-employment-type-select (data-employee-code),
     .edit-wage-field-harian-{kode} / .edit-wage-field-bulanan-{kode} /
     .edit-harian-extra-{kode} (ditampilkan/disembunyikan via style.display),
     .daily-wage-input, .base-salary-input, .monthly-currency-input,
     .project-dropdown (components/project-dropdown.js).
     Parameter: $employee, $divisions
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    $code = $employee->employee_code;
    $isBulanan = $employee->employment_type === 'bulanan';
    $fid = fn ($key) => "edit-{$code}-{$key}";
@endphp

<x-fi.modal id="editModal-{{ $code }}" heading="Edit Karyawan" :description="$code" icon="pencil-square" width="2xl"
    :action="route('employee.update', $code)" method="PUT" submitLabel="Update">

    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
        <x-fi.field label="Kode Karyawan" :for="$fid('code')">
            <x-fi.input :id="$fid('code')" name="employee_code" :value="$code" readonly />
        </x-fi.field>

        <x-fi.field label="Nama Lengkap" :for="$fid('name')" required>
            <x-fi.input :id="$fid('name')" name="name" :value="$employee->name" placeholder="Masukkan nama lengkap"
                required maxlength="255" />
        </x-fi.field>

        <x-fi.field label="Jabatan" :for="$fid('position')" hint="Opsional">
            <x-fi.input :id="$fid('position')" name="position" :value="$employee->position"
                placeholder="Masukkan jabatan (opsional)" maxlength="100" />
        </x-fi.field>

        <x-fi.field label="Jenis Karyawan" for="edit-employment-type-{{ $code }}" required>
            <x-fi.select name="employment_type" id="edit-employment-type-{{ $code }}"
                class="edit-employment-type-select" data-employee-code="{{ $code }}" data-fi-select
                :value="$employee->employment_type"
                :options="['harian' => 'TUKANG PROYEK', 'bulanan' => 'KARYAWAN KANTOR']" />
        </x-fi.field>

        {{-- Tukang proyek: upah per hari --}}
        <div class="edit-wage-field-harian-{{ $code }} sm:col-span-2" @if ($isBulanan) style="display: none;" @endif>
            <x-fi.field label="Upah Per Hari" :for="$fid('daily_wage')" required>
                <x-fi.input :id="$fid('daily_wage')" inputmode="numeric" name="daily_wage" prefix="Rp"
                    class="daily-wage-input" :value="$employee->daily_wage" placeholder="Masukkan upah per hari"
                    required min="0" />
            </x-fi.field>
        </div>

        {{-- Karyawan kantor: gaji pokok & tunjangan harian --}}
        <div class="edit-wage-field-bulanan-{{ $code }} hidden sm:col-span-2" @if ($isBulanan) style="display: block;" @endif>
            <div class="grid grid-cols-1 gap-x-4 gap-y-5 rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5 sm:grid-cols-2">
                <x-fi.field label="Gaji Pokok / Bulan" :for="$fid('base_salary')" required>
                    <x-fi.input :id="$fid('base_salary')" inputmode="numeric" name="base_salary" prefix="Rp"
                        class="base-salary-input" :value="$employee->base_salary"
                        placeholder="Masukkan gaji pokok per bulan" min="0" />
                </x-fi.field>

                <x-fi.field label="Status Karyawan" :for="$fid('status')" hint="Opsional">
                    <x-fi.input :id="$fid('status')" name="status" :value="$employee->status"
                        placeholder="cth: Karyawan Tetap / Kontrak" maxlength="100" />
                </x-fi.field>

                <x-fi.field label="Transport / Hari" :for="$fid('transport_rate')" hint="Opsional">
                    <x-fi.input :id="$fid('transport_rate')" inputmode="numeric" name="transport_rate" prefix="Rp"
                        class="monthly-currency-input" :value="$employee->transport_rate" placeholder="cth: 5.000" min="0" />
                </x-fi.field>

                <x-fi.field label="Makan / Hari" :for="$fid('meal_rate')" hint="Opsional">
                    <x-fi.input :id="$fid('meal_rate')" inputmode="numeric" name="meal_rate" prefix="Rp"
                        class="monthly-currency-input" :value="$employee->meal_rate" placeholder="cth: 10.000" min="0" />
                </x-fi.field>

                <x-fi.field label="UMP" :for="$fid('ump')" hint="Opsional">
                    <x-fi.input :id="$fid('ump')" inputmode="numeric" name="ump" prefix="Rp"
                        class="monthly-currency-input" :value="$employee->ump" placeholder="cth: 4.200.000" min="0" />
                </x-fi.field>
            </div>
        </div>

        {{-- Detail tukang proyek (disembunyikan untuk karyawan kantor) --}}
        <div class="edit-harian-extra-{{ $code }}" @if ($isBulanan) style="display: none;" @endif>
            <x-fi.field label="Divisi" :for="$fid('division')" hint="Opsional">
                <x-fi.select :id="$fid('division')" name="division" data-fi-select="search" placeholder="-- Pilih Divisi --"
                    :value="$employee->division ?? ''" :options="$divisions->pluck('name', 'name')" />
            </x-fi.field>
        </div>

        <div class="edit-harian-extra-{{ $code }}" @if ($isBulanan) style="display: none;" @endif>
            <x-fi.field label="No. Telepon" :for="$fid('phone')">
                <x-fi.input :id="$fid('phone')" name="phone" :value="$employee->phone"
                    placeholder="Masukkan no. telepon (opsional)" maxlength="20" />
            </x-fi.field>
        </div>

        <div class="edit-harian-extra-{{ $code }} sm:col-span-2" @if ($isBulanan) style="display: none;" @endif>
            <x-fi.field label="Proyek" hint="Opsional">
                <x-fi.project-dropdown :value="$employee->project_name ?? ''" clearable inline />
            </x-fi.field>
        </div>

        <div class="edit-harian-extra-{{ $code }} sm:col-span-2" @if ($isBulanan) style="display: none;" @endif>
            <x-fi.field label="Alamat" :for="$fid('address')" hint="Opsional">
                <x-fi.textarea :id="$fid('address')" name="address" rows="3"
                    placeholder="Masukkan alamat">{{ $employee->address }}</x-fi.textarea>
            </x-fi.field>
        </div>
    </div>
</x-fi.modal>
