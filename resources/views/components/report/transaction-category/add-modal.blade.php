{{-- Modal Tambah Kategori Transaksi (gaya Filament).
     Field: nama, kode, tipe, modul.
     Validasi kode duplikat dilakukan di JavaScript (client-side):
     #add-code, #add-code-warning, #add-code-warning-text, #submit-btn-addModal. --}}
<x-fi.modal id="addModal" heading="Tambah Kategori Transaksi" icon="tag"
    :action="route('transaction-category.store')" method="POST" submitLabel="Simpan">

    {{-- Field: Nama Kategori --}}
    <x-fi.field label="Nama Kategori" for="add-name" required>
        <x-fi.input id="add-name" name="name" placeholder="Contoh: Belanja ATK" prefix-icon="tag" required
            maxlength="100" oninvalid="this.setCustomValidity('Nama kategori wajib diisi')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Kode (format: HURUF_BESAR_UNDERSCORE) --}}
    <x-fi.field label="Kode" for="add-code" required
        hint="Kode harus unik dan menggunakan format: HURUF_BESAR_UNDERSCORE">
        <x-fi.input id="add-code" name="code" class="font-mono uppercase" placeholder="Contoh: BELANJA_ATK"
            prefix-icon="hashtag" required maxlength="50" pattern="[A-Z_]+"
            oninvalid="this.setCustomValidity('Kode wajib diisi dengan format: HURUF_BESAR_UNDERSCORE')"
            oninput="this.setCustomValidity(''); this.value = this.value.toUpperCase()" />

        {{-- Warning untuk kode duplikat --}}
        <p id="add-code-warning" class="hidden text-sm text-red-600">
            <x-fi.icon name="exclamation-triangle" class="me-1 inline-block h-4 w-4 align-[-3px]" />
            <span id="add-code-warning-text">Kode sudah digunakan!</span>
        </p>
    </x-fi.field>

    {{-- Field: Tipe (Pemasukan/Pengeluaran) --}}
    <x-fi.field label="Tipe" for="add-type" required>
        <x-fi.select id="add-type" name="type" placeholder="-- Pilih Tipe --"
            :options="['INCOME' => 'Pemasukan', 'EXPENSE' => 'Pengeluaran']" required
            oninvalid="this.setCustomValidity('Tipe kategori wajib dipilih')" oninput="this.setCustomValidity('')"
            data-fi-select />
    </x-fi.field>

    {{-- Field: Modul (Rekap Pengeluaran / Keuangan Proyek) --}}
    <x-fi.field label="Modul" for="add-module" required>
        <x-fi.select id="add-module" name="module" placeholder="-- Pilih Modul --"
            :options="['expense_recap' => 'Rekap Pengeluaran', 'project_finance' => 'Keuangan Proyek']" required
            oninvalid="this.setCustomValidity('Modul kategori wajib dipilih')" oninput="this.setCustomValidity('')"
            data-fi-select />
    </x-fi.field>
</x-fi.modal>
