{{-- Modal Edit Kategori Transaksi (gaya Filament, satu per kategori).
     Field: ID (readonly), nama, kode, tipe, modul, urutan.
     Validasi kode duplikat dilakukan di JavaScript (client-side, mengecualikan ID
     sendiri): #edit-code-{id} (data-category-id), #edit-code-warning-{id},
     #edit-code-warning-text-{id}, #submit-btn-editModal-{id}. --}}
<x-fi.modal id="editModal-{{ $category->id }}" heading="Edit Kategori Transaksi"
    :description="$category->name . ' · ' . $category->code" icon="pencil-square"
    :action="route('transaction-category.update', $category->id)" method="PUT" submitLabel="Update">

    {{-- Field: ID Kategori (readonly) --}}
    <x-fi.field label="ID Kategori" hint="ID Kategori tidak dapat diubah">
        <x-fi.input :value="$category->id" prefix-icon="lock-closed" aria-label="ID Kategori" readonly />
    </x-fi.field>

    {{-- Field: Nama Kategori --}}
    <x-fi.field label="Nama Kategori" for="edit-name-{{ $category->id }}" required>
        <x-fi.input id="edit-name-{{ $category->id }}" name="name" :value="$category->name" prefix-icon="tag" required
            maxlength="100" oninvalid="this.setCustomValidity('Nama kategori wajib diisi')"
            oninput="this.setCustomValidity('')" />
    </x-fi.field>

    {{-- Field: Kode (format: HURUF_BESAR_UNDERSCORE) --}}
    <x-fi.field label="Kode" for="edit-code-{{ $category->id }}" required
        hint="Kode harus unik dan menggunakan format: HURUF_BESAR_UNDERSCORE">
        <x-fi.input id="edit-code-{{ $category->id }}" name="code" :value="$category->code" class="font-mono uppercase"
            prefix-icon="hashtag" data-category-id="{{ $category->id }}" required maxlength="50" pattern="[A-Z_]+"
            oninvalid="this.setCustomValidity('Kode wajib diisi dengan format: HURUF_BESAR_UNDERSCORE')"
            oninput="this.setCustomValidity(''); this.value = this.value.toUpperCase()" />

        {{-- Warning untuk kode duplikat --}}
        <p id="edit-code-warning-{{ $category->id }}" class="hidden text-sm text-red-600">
            <x-fi.icon name="exclamation-triangle" class="me-1 inline-block h-4 w-4 align-[-3px]" />
            <span id="edit-code-warning-text-{{ $category->id }}">Kode sudah digunakan!</span>
        </p>
    </x-fi.field>

    {{-- Field: Tipe (Pemasukan/Pengeluaran) --}}
    <x-fi.field label="Tipe" for="edit-type-{{ $category->id }}" required>
        <x-fi.select id="edit-type-{{ $category->id }}" name="type" placeholder="-- Pilih Tipe --"
            :options="['INCOME' => 'Pemasukan', 'EXPENSE' => 'Pengeluaran']" :value="$category->type" required
            oninvalid="this.setCustomValidity('Tipe kategori wajib dipilih')" oninput="this.setCustomValidity('')"
            data-fi-select />
    </x-fi.field>

    {{-- Field: Modul (Rekap Pengeluaran / Keuangan Proyek) --}}
    <x-fi.field label="Modul" for="edit-module-{{ $category->id }}" required>
        <x-fi.select id="edit-module-{{ $category->id }}" name="module" placeholder="-- Pilih Modul --"
            :options="['expense_recap' => 'Rekap Pengeluaran', 'project_finance' => 'Keuangan Proyek']"
            :value="$category->module" required
            oninvalid="this.setCustomValidity('Modul kategori wajib dipilih')" oninput="this.setCustomValidity('')"
            data-fi-select />
    </x-fi.field>

    {{-- Field: Urutan (Sort Order) --}}
    <x-fi.field label="Urutan (Sort Order)" for="edit-sort-order-{{ $category->id }}" required
        hint="Ubah urutan akan menggeser kategori lain secara otomatis">
        <x-fi.input type="number" id="edit-sort-order-{{ $category->id }}" name="sort_order"
            :value="$category->sort_order" prefix-icon="bars-3" min="1" required
            oninvalid="this.setCustomValidity('Urutan wajib diisi (minimal 1)')" oninput="this.setCustomValidity('')" />
    </x-fi.field>
</x-fi.modal>
