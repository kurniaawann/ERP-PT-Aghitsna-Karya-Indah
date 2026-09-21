{{-- =====================================================================
     Row form satu kartu Faktur Pembelian (dipakai pada modal Tambah yang
     mendukung multiple record + collapse).

     $index : indeks kartu. Pada template dinamis (purchaseInvoiceRowTemplate)
              diisi placeholder '__INDEX__' yang diganti JavaScript saat
              kartu baru ditambahkan.

     Struktur kartu:
     - Header (klik untuk tutup/buka) berisi nomor faktur, tombol hapus,
       dan indikator chevron.
     - Checkbox "Sama dengan Faktur ke-1" (untuk kartu ke-2 dst; pada kartu
       pertama disembunyikan oleh JavaScript).
     - Grid field: tanggal, nama material, NPWP, kode nomor seri pajak,
       nama barang, harga jual, persentase PPN, PPN pajak (auto), keterangan.

     Class yang dipakai JavaScript:
     - .purchase-invoice-card / .card-header / .card-body / .card-chevron
     - .card-number (nomor faktur tampilan)
     - .purchase-invoice-remove (tombol hapus kartu)
     - .pi-copy-wrapper / .pi-copy-from-first (checkbox salin data)
     - .pi-date / .pi-material / .pi-npwp / .pi-tax-code / .pi-item
     - .pi-selling-price / .pi-ppn-percentage / .pi-ppn-tax / .pi-notes
     ===================================================================== --}}
<div class="purchase-invoice-card border-2 border-border-strong rounded-xl overflow-hidden shadow-sm bg-surface-base">

    {{-- Header kartu (klik untuk tutup/buka) --}}
    <div class="card-header flex items-center justify-between gap-2 px-4 py-3 bg-surface-secondary cursor-pointer select-none"
        onclick="toggleInvoiceCard(this)">
        <div class="flex items-center gap-2 min-w-0">
            <i class="fa-solid fa-file-invoice text-text-tertiary"></i>
            <h3 class="text-sm font-semibold text-text-primary truncate">
                Faktur <span class="card-number">1</span>
            </h3>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <button type="button" onclick="removeInvoiceCard(event, this)"
                class="purchase-invoice-remove text-xs text-error hover:text-error flex items-center gap-1"
                style="display: none;" title="Hapus faktur ini">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
            <i class="fa-solid fa-chevron-up card-chevron transition-transform duration-200 text-text-tertiary"></i>
        </div>
    </div>

    {{-- Body kartu --}}
    <div class="card-body px-4 py-4 space-y-3">
        {{-- Checkbox Sama dengan Faktur ke-1 (kartu ke-2 dst) --}}
        <div class="pi-copy-wrapper items-center gap-3 p-3 rounded-lg bg-primary-light border border-primary-light hidden">
            <input type="checkbox" class="pi-copy-from-first w-4 h-4 accent-primary cursor-pointer shrink-0"
                onchange="handleCopyFromFirst(this)">
            <label class="text-sm font-medium text-text-primary cursor-pointer">
                Sama dengan Faktur ke-1
                <span class="block text-xs font-normal text-text-secondary">Salin semua kolom dari faktur pertama</span>
            </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            {{-- Tanggal --}}
            <div>
                <label class="block text-text-primary mb-1 text-sm">Tanggal <span class="text-error">*</span></label>
                <input type="date" name="invoices[{{ $index }}][date]" class="pi-date w-full border rounded p-2"
                    required oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>

            {{-- Nama Material --}}
            <div class="sm:col-span-2">
                <label class="block text-text-primary mb-1 text-sm">Nama Material <span class="text-error">*</span></label>
                <input type="text" name="invoices[{{ $index }}][material_name]" class="pi-material w-full border rounded p-2"
                    placeholder="Contoh: PT. MANGGALA DIPO PRATAMA" required
                    oninvalid="this.setCustomValidity('Nama material tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>

            {{-- NPWP --}}
            <div>
                <label class="block text-text-primary mb-1 text-sm">NPWP <span class="text-error">*</span></label>
                <input type="text" name="invoices[{{ $index }}][npwp]" class="pi-npwp w-full border rounded p-2"
                    placeholder="Contoh: 80.948.827.3-047.000" required
                    oninvalid="this.setCustomValidity('NPWP tidak boleh kosong')" oninput="this.setCustomValidity('')">
            </div>

            {{-- Kode Nomor Seri Pajak --}}
            <div>
                <label class="block text-text-primary mb-1 text-sm">Kode Nomor Seri Pajak <span class="text-error">*</span></label>
                <input type="text" name="invoices[{{ $index }}][tax_number_code]" class="pi-tax-code w-full border rounded p-2"
                    placeholder="Contoh: 011.011-23.82258306" required
                    oninvalid="this.setCustomValidity('Kode nomor seri pajak tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>

            {{-- Nama Barang --}}
            <div class="sm:col-span-2 md:col-span-1">
                <label class="block text-text-primary mb-1 text-sm">Nama Barang <span class="text-error">*</span></label>
                <input type="text" name="invoices[{{ $index }}][item_name]" class="pi-item w-full border rounded p-2"
                    placeholder="Contoh: SEMEN 40 KG" required
                    oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>

            {{-- Harga Jual --}}
            <div>
                <label class="block text-text-primary mb-1 text-sm">Harga Jual (Rp) <span class="text-error">*</span></label>
                <input type="text" name="invoices[{{ $index }}][selling_price]" inputmode="numeric"
                    class="pi-selling-price w-full border rounded p-2" placeholder="Rp 0" required
                    oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>

            {{-- Persentase PPN --}}
            <div>
                <label class="block text-text-primary mb-1 text-sm">Persentase PPN (%) <span class="text-error">*</span></label>
                <input type="text" name="invoices[{{ $index }}][ppn_percentage]" inputmode="decimal"
                    class="pi-ppn-percentage w-full border rounded p-2" placeholder="11" value="11" required
                    oninvalid="this.setCustomValidity('Persentase PPN tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
                <p class="text-xs text-text-secondary mt-1">Default: 11%. Boleh pakai koma, contoh 11,5</p>
            </div>

            {{-- PPN Pengenaan Pajak (Auto-calculated) --}}
            <div>
                <label class="block text-text-primary mb-1 text-sm">PPN Pengenaan Pajak (Rp) <span class="text-error">*</span></label>
                <input type="text" class="pi-ppn-tax w-full border rounded p-2 bg-surface-hover cursor-not-allowed"
                    placeholder="Rp 0" readonly>
                <p class="text-xs text-text-secondary mt-1">Dihitung otomatis dari harga jual × persentase PPN</p>
            </div>

            {{-- Keterangan --}}
            <div class="sm:col-span-2 md:col-span-3">
                <label class="block text-text-primary mb-1 text-sm">Keterangan</label>
                <textarea name="invoices[{{ $index }}][notes]" class="pi-notes w-full border rounded p-2"
                    rows="2" placeholder="Keterangan tambahan (opsional)"></textarea>
            </div>
        </div>
    </div>
</div>