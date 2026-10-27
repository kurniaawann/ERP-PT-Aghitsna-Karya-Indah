{{-- Modal Tambah DO Semen (gaya Filament): info DO + repeater baris Data Semen --}}
<x-fi.modal id="addModal" heading="Tambah DO Semen" description="Isi informasi DO lalu tambahkan baris data semen."
    icon="truck" :action="route('cement-do.store')" method="POST" submitLabel="Simpan" width="6xl">

    {{-- Info DO (header) --}}
    <x-fi.fieldset label="Informasi DO">
        <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">

            {{-- Field: Tanggal DO --}}
            <x-fi.field label="Tanggal DO" for="add-tanggal" required>
                <x-fi.input type="date" id="add-tanggal" name="tanggal" required
                    oninvalid="this.setCustomValidity('Tanggal DO tidak boleh kosong')" oninput="this.setCustomValidity('')" />
            </x-fi.field>

            {{-- Field: Tanggal Datang --}}
            <x-fi.field label="Tanggal Datang" for="add-tanggal-datang">
                <x-fi.input type="date" id="add-tanggal-datang" name="tanggal_datang" />
            </x-fi.field>

            {{-- Field: Tanggal Bayar --}}
            <x-fi.field label="Tanggal Bayar" for="add-tanggal-bayar">
                <x-fi.input type="date" id="add-tanggal-bayar" name="tanggal_bayar" />
            </x-fi.field>

            {{-- Field: Harga Modal (per DO) --}}
            <x-fi.field label="Harga Modal" for="add-harga-modal" required
                hint="Total modal untuk seluruh baris semen dalam DO ini.">
                <x-fi.input name="harga_modal" value="Rp 0" placeholder="Rp 0" prefix-icon="banknotes"
                    required inputmode="numeric" id="add-harga-modal"
                    oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')" oninput="this.setCustomValidity('')" />
            </x-fi.field>
        </div>
    </x-fi.fieldset>

    {{-- Baris Data Semen (detail) — baris ditambah lewat tombol "Tambah Baris" (JS) --}}
    <x-inventory.cement-do.cement-rows-table mode="add" />

    {{-- Template baris semen (referensi markup baris; baris baru dibuat JS buildCementRow) --}}
    <template id="cement-row-template">
        @include('components.inventory.cement-do.cement-row', ['index' => 'IDX', 'cement' => null])
    </template>
</x-fi.modal>
