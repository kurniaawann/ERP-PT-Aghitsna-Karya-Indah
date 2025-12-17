{{-- Modal Edit Kategori Transaksi --}}
<x-modal id="editModal-{{ $category->id }}" title="Edit Kategori Transaksi"
    action="{{ route('transaction-category.update', $category->id) }}" method="PUT" buttonText="Update">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">ID Kategori</label>
        <input type="text" value="{{ $category->id }}" class="w-full border rounded p-2 bg-surface-hover cursor-not-allowed"
            readonly>
        <p class="text-xs text-text-secondary mt-1">ID Kategori tidak dapat diubah</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Kategori <span class="text-error">*</span></label>
        <input type="text" name="name" value="{{ $category->name }}" class="w-full border rounded p-2" required
            maxlength="100" oninvalid="this.setCustomValidity('Nama kategori wajib diisi')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kode <span class="text-error">*</span></label>
        <input type="text" id="edit-code-{{ $category->id }}" name="code" value="{{ $category->code }}"
            class="w-full border rounded p-2 uppercase" data-category-id="{{ $category->id }}" required maxlength="50"
            pattern="[A-Z_]+"
            oninvalid="this.setCustomValidity('Kode wajib diisi dengan format: HURUF_BESAR_UNDERSCORE')"
            oninput="this.setCustomValidity(''); this.value = this.value.toUpperCase()">
        <p class="text-xs text-text-secondary mt-1">Kode harus unik dan menggunakan format: HURUF_BESAR_UNDERSCORE</p>

        {{-- Warning Error untuk kode duplikat --}}
        <div id="edit-code-warning-{{ $category->id }}"
            class="hidden mt-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
            <i class="fa-solid fa-exclamation-triangle"></i>
            <span id="edit-code-warning-text-{{ $category->id }}">Kode sudah digunakan!</span>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe <span class="text-error">*</span></label>
        <select name="type" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tipe kategori wajib dipilih')" oninput="this.setCustomValidity('')">
            <option value="">-- Pilih Tipe --</option>
            <option value="INCOME" {{ $category->type == 'INCOME' ? 'selected' : '' }}>Pemasukan</option>
            <option value="EXPENSE" {{ $category->type == 'EXPENSE' ? 'selected' : '' }}>Pengeluaran</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Urutan (Sort Order) <span class="text-error">*</span></label>
        <input type="number" name="sort_order" value="{{ $category->sort_order }}" class="w-full border rounded p-2"
            min="1" required oninvalid="this.setCustomValidity('Urutan wajib diisi (minimal 1)')"
            oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Ubah urutan akan menggeser kategori lain secara otomatis</p>
    </div>
</x-modal>
