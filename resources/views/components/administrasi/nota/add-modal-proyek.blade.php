{{-- =====================================================================
     Modal Tambah Nota Proyek
     PT Aghitsna Karya Indah

     Komponen form untuk menambahkan nota proyek baru.
     Tipe ini memiliki field yang berbeda dari nota sewa/jual:
     - Nama Proyek
     - Tanggal Nota
     - Daftar Barang (qty, satuan, nama barang, harga, jumlah)
     - Tanda Terima / Penerima

     Tanpa: faktur no, sj no, periode, biaya tambahan, PPN.
     ===================================================================== --}}

{{-- Modal Tambah Nota Proyek --}}
<x-modal id="addModalProyek" title="Tambah Nota Proyek"
    action="{{ route('nota.administrasi.store') }}" method="POST" buttonText="Simpan">

    {{-- Tipe nota tersembunyi --}}
    <input type="hidden" name="tipe_nota" value="proyek">

    {{-- ═══════════════════════════════════════════════════════════
         FIELD: Nama Proyek & Tanggal
         ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Nama Proyek <span class="text-error">*</span></label>
            <input type="text" name="nama_proyek"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Nama proyek" required maxlength="255"
                oninvalid="this.setCustomValidity('Nama proyek tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="nota_date"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
                value="{{ date('Y-m-d') }}" oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         FIELD: Kepada (Penerima)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Kepada Yang Terhormat <span class="text-error">*</span></label>
        <input type="text" name="kepada"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Nama penerima" required maxlength="255"
            oninvalid="this.setCustomValidity('Kepada tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         SECTION: Daftar Barang (Item Rows)
         Setiap item memiliki: Quantity, Satuan, Nama Barang, Harga, Jumlah
         Total per item = Quantity × Harga
         Grand Total = Σ Total per item
         ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-3">
            <label class="block text-text-primary font-semibold text-base">Daftar Barang <span
                    class="text-error">*</span></label>
            <button type="button" id="addItemBtn-addModalProyek"
                class="bg-btn-add hover:bg-btn-add-hover text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        <div id="itemsContainer-addModalProyek" data-tipe="proyek" class="space-y-3">
            {{-- Item Row Pertama (default) --}}
            <div
                class="item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="space-y-3">
                    {{-- Row 1: Qty, Satuan, Nama Barang --}}
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-3">
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Quantity <span
                                    class="text-error">*</span></label>
                            <input type="number" name="item_quantity[]"
                                class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="0" min="1" required>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Satuan</label>
                            <input type="text" name="item_satuan[]"
                                class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="unit" maxlength="50">
                        </div>
                        <div class="col-span-6">
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span
                                    class="text-error">*</span></label>
                            <input type="text" name="item_nama_barang[]"
                                class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="Masukkan nama barang..." required>
                        </div>
                    </div>

                    {{-- Row 2: Harga, Jumlah, Tombol Hapus --}}
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-4">
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Harga <span
                                    class="text-error">*</span></label>
                            <input type="text" name="item_harga[]"
                                class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right text-text-input price-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="0" required>
                        </div>
                        <div class="col-span-3">
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Jumlah</label>
                            <div
                                class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-right bg-surface-secondary text-text-input item-total">
                                0
                            </div>
                        </div>
                        <div class="col-span-5 flex items-end">
                            <button type="button" style="display: none;"
                                class="delete-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-trash"></i>
                                <span>Hapus</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Info Penggunaan --}}
        <p class="text-xs text-text-secondary mt-3 flex items-center gap-1.5">
            <i class="fa-solid fa-info-circle text-info"></i>
            <span>Klik <strong>"Tambah Item"</strong> untuk menambahkan barang baru ke daftar.</span>
        </p>

        {{-- ═══════════════════════════════════════════════════════
             GRAND TOTAL: Total seluruh items
             ═══════════════════════════════════════════════════════ --}}
        <div class="flex justify-end mt-3">
            <div class="bg-surface-secondary border border-border-strong rounded-lg px-4 py-2">
                <span class="text-sm font-semibold text-text-primary">Total Barang: Rp </span>
                <span id="grandTotal-addModalProyek"
                    class="text-sm font-bold text-text-heading">0</span>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         FIELD: Penanda Tangan (Petinggi) & Divisi
         Data diambil dari modul Data Petinggi (Executive) dan sub menu
         Divisi (Division). Disimpan sebagai snapshot di kolom
         penandatangan untuk blok "Hormat Kami" pada PDF.
         ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <x-forms.searchable-select
            name="petinggi_id"
            id="addModalProyek-petinggi_id"
            label="Penanda Tangan (Petinggi)"
            placeholder="Cari petinggi..."
            :options="$executives->map(fn($e) => ['value' => (string) $e->id, 'label' => $e->name . ($e->position ? ' — ' . $e->position : '')])->values()" />

        <x-forms.searchable-select
            name="divisi"
            id="addModalProyek-divisi"
            label="Divisi"
            placeholder="Cari divisi..."
            :options="$divisions->map(fn($d) => ['value' => $d->name, 'label' => $d->name])->values()" />
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         FIELD: Tanda Terima (Penerima)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanda Terima</label>
        <input type="text" name="penerima"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Nama yang menerima" maxlength="255">
    </div>

</x-modal>