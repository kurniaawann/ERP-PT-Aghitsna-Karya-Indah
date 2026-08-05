{{-- =====================================================================
     Modal Tambah Nota
     PT Aghitsna Karya Indah

     Komponen form untuk menambahkan nota baru.
     Setiap field memiliki validasi dan format Rupiah.

     Field:
     - Lokasi (default: Jakarta)
     - Tanggal Nota
     - Kepada (penerima)
     - Faktur No
     - SJ No
     - Daftar Barang (dynamic rows)
     - Penerima
     - Biaya Tambahan (opsional): Sewa/Jual, Ongkos Kirim, Bongkar/Pasang, Lembur, Uang Jaminan
     - PPN (%)
     ===================================================================== --}}

{{-- Modal Tambah Nota --}}
<x-modal id="addModal" title="Tambah Nota" action="{{ route('nota.administrasi.store') }}" method="POST"
    buttonText="Simpan">

    {{-- ═══════════════════════════════════════════════════════════
         FIELD: Lokasi & Tanggal
         ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Lokasi</label>
            <input type="text" name="location"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Jakarta (default)" maxlength="100" value="Jakarta">
            <small class="text-text-secondary text-xs">Contoh: Jakarta, Depok, Bogor</small>
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
         FIELD: Periode (Awal s/d Akhir)
         ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3 items-end">
        <div>
            <label class="block text-text-primary mb-1">Periode Awal</label>
            <input type="date" name="periode_start"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                oninvalid="this.setCustomValidity('Format tanggal periode tidak valid')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">s/d</label>
            <input type="date" name="periode_end"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                oninvalid="this.setCustomValidity('Format tanggal periode tidak valid')"
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
         FIELD: Faktur No & SJ No
         ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Faktur No <span class="text-error">*</span></label>
            <input type="text" name="faktur_no"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Masukkan faktur no" required maxlength="100"
                oninvalid="this.setCustomValidity('Faktur No tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">SJ.NO <span class="text-error">*</span></label>
            <input type="text" name="sj_no"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="Masukkan SJ No" required maxlength="100"
                oninvalid="this.setCustomValidity('SJ No tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         SECTION: Daftar Barang (Item Rows)
         Setiap item memiliki: Qty, Nama Barang, Harga Satuan, Jumlah
         Total per item = Qty × Harga Satuan
         Grand Total = Σ Total per item
         ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-3">
            <label class="block text-text-primary font-semibold text-base">Daftar Barang <span
                    class="text-error">*</span></label>
            <button type="button" id="addItemBtn-addModal"
                class="bg-btn-add hover:bg-btn-add-hover text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        <div id="itemsContainer-addModal" class="space-y-3">
            {{-- Item Row Pertama (default) --}}
            <div
                class="item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="space-y-3">
                    {{-- Row 1: Qty & Nama Barang --}}
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Qty <span
                                    class="text-error">*</span></label>
                            <input type="number" name="item_banyaknya[]"
                                class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="0" min="1" required>
                        </div>
                        <div class="col-span-10">
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span
                                    class="text-error">*</span></label>
                            <input type="text" name="item_nama_barang[]"
                                class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                placeholder="Masukkan nama barang..." required>
                        </div>
                    </div>

                    {{-- Row 2: Harga Satuan, Jumlah, Tombol Hapus --}}
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-4">
                            <label class="block text-xs font-semibold text-text-label mb-1.5">Harga Satuan <span
                                    class="text-error">*</span></label>
                            <input type="text" name="item_harga_satuan[]"
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
                <span id="grandTotal-addModal"
                    class="text-sm font-bold text-text-heading">0</span>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         FIELD: Penerima
         ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Penerima s/d</label>
        <input type="text" name="penerima"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Nama penerima" maxlength="255">
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         SECTION: Biaya Tambahan (Opsional)
         Semua field menggunakan format Rupiah
         ═══════════════════════════════════════════════════════════ --}}
    <div class="border-t pt-3 mt-3">
        <label class="block text-text-primary font-semibold mb-2">Biaya Tambahan (Opsional)</label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-text-primary mb-1 text-sm">Sewa / Jual</label>
                <input type="text" name="sewa_jual"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input price-input"
                    placeholder="0">
            </div>

            <div>
                <label class="block text-text-primary mb-1 text-sm">Ongkos Kirim PP / 1x</label>
                <input type="text" name="ongkos_kirim"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input price-input"
                    placeholder="0">
            </div>

            <div>
                <label class="block text-text-primary mb-1 text-sm">Bongkar / Pasang</label>
                <input type="text" name="bongkar_pasang"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input price-input"
                    placeholder="0">
            </div>

            <div>
                <label class="block text-text-primary mb-1 text-sm">Lembur Antar / Ambil</label>
                <input type="text" name="lembur"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input price-input"
                    placeholder="0">
            </div>

            <div class="md:col-span-2">
                <label class="block text-text-primary mb-1 text-sm">Uang Jaminan</label>
                <input type="text" name="uang_jaminan"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input price-input"
                    placeholder="0">
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         SECTION: PPN
         Default PPN: 12%
         ═══════════════════════════════════════════════════════════ --}}
    <div class="border-t pt-3 mt-3">
        <label class="block text-text-primary font-semibold mb-2">PPN</label>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-text-primary mb-1 text-sm">Persentase PPN (%)</label>
                <input type="text" inputmode="decimal" name="ppn_percentage"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                    placeholder="12,5" min="0" max="100" value="12"
                    oninput="this.setCustomValidity('')">
            </div>
        </div>
    </div>

</x-modal>
