{{-- ============================================================
     Modal Tambah Kategori Transaksi
     Form untuk menambah kategori baru dengan field: nama, kode, tipe
     Validasi kode duplikat dilakukan di JavaScript (client-side)
     ============================================================ --}}
<x-modal id="addModal" title="Tambah Kategori Transaksi" action="{{ route('transaction-category.store') }}" method="POST"
    buttonText="Simpan">

    {{-- Field: Nama Kategori --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Kategori <span class="text-error">*</span></label>
        <input type="text" name="name" class="w-full border rounded p-2" placeholder="Contoh: Belanja ATK" required
            maxlength="100" oninvalid="this.setCustomValidity('Nama kategori wajib diisi')"
            oninput="this.setCustomValidity('')">
    </div>

    {{-- Field: Kode (format: HURUF_BESAR_UNDERSCORE) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kode <span class="text-error">*</span></label>
        <input type="text" id="add-code" name="code" class="w-full border rounded p-2 uppercase"
            placeholder="Contoh: BELANJA_ATK" required maxlength="50" pattern="[A-Z_]+"
            oninvalid="this.setCustomValidity('Kode wajib diisi dengan format: HURUF_BESAR_UNDERSCORE')"
            oninput="this.setCustomValidity(''); this.value = this.value.toUpperCase()">
        <p class="text-xs text-text-secondary mt-1">Kode harus unik dan menggunakan format: HURUF_BESAR_UNDERSCORE</p>

        {{-- Warning untuk kode duplikat --}}
        <div id="add-code-warning"
            class="hidden mt-2 p-2 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm">
            <i class="fa-solid fa-exclamation-triangle"></i>
            <span id="add-code-warning-text">Kode sudah digunakan!</span>
        </div>
    </div>

    {{-- Field: Tipe (Pemasukan/Pengeluaran) --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tipe <span class="text-error">*</span></label>
        <select name="type" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tipe kategori wajib dipilih')" oninput="this.setCustomValidity('')">
            <option value="">-- Pilih Tipe --</option>
            <option value="INCOME">Pemasukan</option>
            <option value="EXPENSE">Pengeluaran</option>
        </select>
    </div>
</x-modal>
