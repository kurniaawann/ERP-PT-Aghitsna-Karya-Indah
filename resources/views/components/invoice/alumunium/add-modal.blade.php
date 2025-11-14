{{-- Modal Tambah Invoice Alumunium --}}
<x-modal id="addModal" title="Tambah Invoice Alumunium" action="{{ route('alumunium-invoice.store') }}" method="POST"
    buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tanggal Invoice <span class="text-error">*</span></label>
        <input type="date" name="invoice_date" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Kepada (Nama Penerima) <span class="text-error">*</span></label>
        <input type="text" name="recipient" class="w-full border rounded p-2" placeholder="Nama penerima invoice"
            required oninvalid="this.setCustomValidity('Nama penerima tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Hal / Regarding <span class="text-error">*</span></label>
        <input type="text" name="regarding" class="w-full border rounded p-2"
            placeholder="Contoh: Penagihan Pembayaran" required
            oninvalid="this.setCustomValidity('Hal/Regarding tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Deskripsi Proyek <span class="text-error">*</span></label>
        <textarea name="project_description" class="w-full border rounded p-2" rows="2"
            placeholder="Contoh: Proyek Karbela 3 / Pak Sis" required
            oninvalid="this.setCustomValidity('Deskripsi proyek tidak boleh kosong')" oninput="this.setCustomValidity('')"></textarea>
    </div>

    <div id="items-container" class="mb-4">
        <label class="block text-gray-700 font-semibold mb-2">Item-Item Invoice <span
                class="text-error">*</span></label>
        <div id="items-list">
            <div class="item-row mb-3 p-3 border rounded bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *"
                        required oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" class="item-volume border rounded p-2 w-full"
                        placeholder="Volume *" required oninput="calculateRowTotal(this)"
                        oninvalid="this.setCustomValidity('Volume tidak boleh kosong')"
                        oninput="calculateRowTotal(this); this.setCustomValidity('')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" class="item-satuan border rounded p-2 w-full"
                        placeholder="Satuan (m3, unit) *" required
                        oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" class="item-harga border rounded p-2 w-full"
                        placeholder="Harga *" required oninput="calculateRowTotal(this)"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')"
                        oninput="calculateRowTotal(this); this.setCustomValidity('')">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <button type="button"
                        class="remove-item bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <button type="button" id="add-item" class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-hover">
            <i class="fa-solid fa-plus"></i> Tambah Item
        </button>
    </div>

    <!-- Live Total Preview -->
    <div class="mb-4 p-4 bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border-2 border-primary/20">
        <div class="flex justify-between items-center">
            <span class="text-gray-700 font-semibold">Total Invoice:</span>
            <span id="invoice-total-preview" class="text-2xl font-bold text-primary">Rp 0</span>
        </div>
        <div class="text-xs text-gray-500 mt-1" id="invoice-total-words"></div>
    </div>

    <input type="hidden" name="items" id="items-json" value="[]">
</x-modal>
