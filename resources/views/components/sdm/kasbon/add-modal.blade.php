{{-- ═══════════════════════════════════════════════════════════════════════
     Komponen Modal Tambah Kasbon (gaya Filament)
     Formulir untuk membuat kasbon personal atau tim baru: toggle jenis,
     pemilihan karyawan/divisi, validasi jumlah, dan resolusi tanggal periode.

     Hook JS (resources/js/pages/sdm/kasbon/index.js):
     - toggleEmployeeSelect('add') menampilkan/menyembunyikan wrapper
       #add_employee_field / #add_kasbon_detail_field / #add_division_field /
       #add_project_field lewat style.display dan mengatur required.
     - Baris proyek (Tim) dibuat dari <template id="add_project_row_template">
       oleh projectRows (#add_project_rows, #add_project_row_btn); id
       add_project_select_N & name projects[N][...] di-reindex JS.
     - checkMaxKasbon('add') / resolveProjectPeriod(input) mengisi periode.
     ═══════════════════════════════════════════════════════════════════════ --}}
@php
    $employeeOptions = $employees->map(fn ($e) => [
        'value' => $e->employee_code,
        'label' => $e->name . ' (' . $e->employee_code . ') - ' . ($e->employment_type === 'bulanan' ? 'KARYAWAN KANTOR' : 'TUKANG PROYEK'),
        'type' => $e->employment_type,
    ])->values();
@endphp

<x-fi.modal id="addModal" heading="Tambah Kasbon" icon="banknotes" width="xl" :action="route('kasbon.store')">

    {{-- Kotak Informasi --}}
    <div class="flex gap-3 rounded-lg bg-blue-50 p-4 text-sm ring-1 ring-inset ring-blue-600/10">
        <x-fi.icon name="information-circle" class="h-5 w-5 shrink-0 text-primary" />
        <div class="text-gray-700">
            <p class="font-semibold text-gray-950">Informasi Kasbon</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                <li><strong>Personal:</strong> Kasbon untuk 1 orang karyawan (otomatis dipotong saat generate payroll)</li>
                <li><strong>Tim:</strong> Kasbon untuk divisi tertentu, diisi per proyek. Setiap proyek disimpan sebagai kasbon terpisah (otomatis lunas saat payroll proyek tersebut dibayar)</li>
            </ul>
        </div>
    </div>

    {{-- Jenis Kasbon --}}
    <x-fi.field label="Jenis Kasbon" for="add_kasbon_type" required>
        <x-fi.select name="kasbon_type" id="add_kasbon_type" required data-fi-select placeholder="Pilih Jenis"
            :options="['personal' => 'Per Orang', 'team' => 'Per Tim/Divisi']"
            onchange="toggleEmployeeSelect('add')" oninvalid="this.setCustomValidity('Jenis kasbon tidak boleh kosong')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Karyawan (Personal) --}}
    <div id="add_employee_field" class="grid gap-y-2">
        <p class="flex items-center gap-2 text-sm font-medium leading-6 text-gray-950">
            <span>Karyawan<sup class="ms-0.5 text-red-600">*</sup></span>
            <span id="add_employee_type_label" class="hidden rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10"></span>
        </p>
        <x-fi.searchable-select name="employee_id" id="add_employee_id" invalidMessage="Karyawan" placeholder="Cari karyawan..."
            :options="$employeeOptions" :extraData="['type']" />
    </div>

    {{-- Detail Kasbon (Personal) --}}
    <div id="add_kasbon_detail_field" style="display: none;">
        <div class="grid gap-y-5">
            <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
                <x-fi.field label="Jumlah Kasbon" for="add_amount" required>
                    <x-fi.input inputmode="numeric" name="amount" id="add_amount" prefix="Rp" class="kasbon-amount-input"
                        placeholder="Masukkan jumlah kasbon" min="1000" step="1000" />
                </x-fi.field>

                <x-fi.field label="Periode Kasbon" for="add_kasbon_date" required>
                    <x-fi.input type="date" name="kasbon_date" id="add_kasbon_date" :value="date('Y-m-d')"
                        onchange="checkMaxKasbon('add')" />
                </x-fi.field>
            </div>

            {{-- Periode payroll (diisi otomatis dari tanggal periode) --}}
            <input type="hidden" name="week_number" id="add_week_number" value="">
            <input type="hidden" name="period_start_date" id="add_period_start_date" value="">
            <input type="hidden" name="period_end_date" id="add_period_end_date" value="">

            <x-fi.field label="Catatan" for="add_kasbon_notes">
                <x-fi.textarea id="add_kasbon_notes" name="notes" rows="3" maxlength="500" placeholder="Catatan tambahan"></x-fi.textarea>
            </x-fi.field>
        </div>
    </div>

    {{-- Divisi (Tim) --}}
    <div id="add_division_field" style="display: none;">
        <p class="mb-2 text-sm font-medium leading-6 text-gray-950">Divisi<sup class="ms-0.5 text-red-600">*</sup></p>
        <x-fi.searchable-select name="division" id="add_division" invalidMessage="Divisi" placeholder="Cari divisi..."
            :options="$divisions->map(fn ($d) => ['value' => $d->name, 'label' => $d->name])->values()" />
    </div>

    {{-- Proyek & detail per proyek (Tim, dinamis) --}}
    <div id="add_project_field" style="display: none;">
        <div class="grid gap-y-3">
            <div id="add_project_rows" class="grid gap-y-3"></div>
            <button type="button" id="add_project_row_btn"
                class="flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 p-3 text-sm font-semibold text-primary transition hover:border-primary hover:bg-blue-50">
                <x-fi.icon name="plus" class="h-5 w-5" /> Tambah Proyek
            </button>
            <p class="text-sm text-gray-500">Setiap proyek disimpan sebagai kasbon terpisah dan otomatis lunas saat payroll
                proyek tersebut dibayar.</p>
        </div>
    </div>

    {{-- Template baris proyek (Tim) --}}
    <template id="add_project_row_template">
        <div class="kasbon-project-row relative rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-950/5">
            <button type="button" title="Hapus proyek"
                class="remove-project-row absolute end-2 top-2 rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600">
                <x-fi.icon name="x-mark" class="h-5 w-5" />
            </button>

            <div class="kasbon-project-toggle me-8 flex cursor-pointer select-none items-center justify-between"
                onclick="toggleKasbonProjectSection(this)">
                <span class="text-sm font-medium text-gray-950">Proyek<sup class="ms-0.5 text-red-600">*</sup></span>
                <x-fi.icon name="chevron-down" class="kasbon-project-chevron fa-chevron-up h-5 w-5 rotate-180 text-gray-400 transition-transform" />
            </div>

            <div class="kasbon-project-body mt-3 grid gap-y-4">
                <x-fi.searchable-select name="projects[0][project]" id="add_project_select_0" invalidMessage="Proyek"
                    :required="true" placeholder="Cari proyek..."
                    :options="$projects->map(fn ($p) => ['value' => $p, 'label' => $p])->values()" />

                <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                    <x-fi.field label="Jumlah Kasbon" required>
                        <x-fi.input inputmode="numeric" name="projects[0][amount]" prefix="Rp" class="kasbon-amount-input"
                            placeholder="Masukkan jumlah" min="1000" step="1000" oninput="formatCurrencyInput(this)" />
                    </x-fi.field>
                    <x-fi.field label="Periode Kasbon" required>
                        <x-fi.input type="date" name="projects[0][kasbon_date]" class="kasbon-project-date"
                            :value="date('Y-m-d')" onchange="resolveProjectPeriod(this)" />
                    </x-fi.field>
                </div>

                <input type="hidden" name="projects[0][period_start_date]" class="kasbon-project-period-start" value="">
                <input type="hidden" name="projects[0][period_end_date]" class="kasbon-project-period-end" value="">

                <x-fi.field label="Catatan">
                    <x-fi.textarea name="projects[0][notes]" rows="2" maxlength="500" placeholder="Catatan tambahan"></x-fi.textarea>
                </x-fi.field>
            </div>
        </div>
    </template>
</x-fi.modal>
