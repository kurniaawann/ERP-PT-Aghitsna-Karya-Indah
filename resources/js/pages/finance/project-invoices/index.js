/**
 * Invoice Proyek — JavaScript Halaman Index
 *
 * Modul ini menangani seluruh logika front-end halaman invoice proyek:
 * - Parsing & format input mata uang / desimal berformat Indonesia
 * - Perhitungan live total per baris item dan grand total (mode tambah & edit)
 * - Perhitungan diskon & DP secara live dengan validasi batas (warning)
 * - Validasi pemilihan rekening pembayaran (submit dinonaktifkan bila kosong)
 * - Tambah / hapus item row secara dinamis + re-index field items
 * - Submit form dengan serialisasi item ke JSON + proteksi submit ganda
 * - Hapus massal & hapus tunggal
 * - Checkbox select all dan filter URL bulan/tahun
 * - No Invoice admin: user mengetik nomor urut saja; preview nomor lengkap
 *   {nomor}/AKI/{bulan romawi}/{yyyy} dari Tanggal Invoice + cek nomor dobel
 * - Persentase item admin opsional (kosong → Jumlah = Harga)
 * - Volume & satuan item superadmin opsional (volume kosong → item borongan,
 *   Jumlah = Harga)
 * - Tautan Rekap Proyek (opsional): prefill field kosong + ringkasan nilai
 *   proyek, sudah ditagih, invoice ini, dan sisa tagihan (live)
 *
 * Referensi backend: app/Services/Finance/ProyekInvoiceService.php
 */

/* global parseCurrencyInput, handleFormSubmit, resetFormSubmitState, submitDeleteForm */

// ==========================================
// PARSER MATA UANG
// ==========================================

/**
 * Parsing input mata uang sesuai format Indonesia.
 * - "1.000" => 1000 (titik sebagai pemisah ribuan)
 * - "Rp 1.000" => 1000
 *
 * @param  {string|number} value  Nilai input mentah
 * @return {number} Nilai numerik hasil parsing
 */
function parseCurrencyInput(value) {
    const str = String(value ?? '').trim();
    if (!str) return 0;

    const cleaned = str.replace(/Rp\s*/gi, '');

    if (cleaned.includes(',')) {
        const normalized = cleaned.replace(/\./g, '').replace(',', '.');
        const num = parseFloat(normalized);
        return Number.isFinite(num) ? num : 0;
    }

    const normalized = cleaned.replace(/\./g, '');
    const num = parseFloat(normalized);
    return Number.isFinite(num) ? num : 0;
}

/**
 * Format nilai input sebagai mata uang Indonesia (tanpa prefiks "Rp").
 *
 * @param  {HTMLInputElement} input  Element input yang akan diformat
 */
function formatCurrencyInput(input) {
    if (!input) return;

    const str = String(input.value ?? '').trim();
    if (!str) return;

    const num = parseCurrencyInput(str);
    input.value = num ? Math.round(num).toLocaleString('id-ID') : '';
}

/**
 * Parse decimal input yang mendukung koma sebagai pemisah desimal.
 *
 * @param  {HTMLInputElement} inputElement  Element input
 * @return {number} Nilai desimal
 */
function parseDecimalInput(inputElement) {
    const rawValue = String(inputElement?.value ?? '').trim();
    if (!rawValue) return 0;
    return parseFloat(rawValue.replace(',', '.')) || 0;
}

/**
 * Format input desimal: hanya menyisakan angka, titik, dan koma.
 *
 * Alur:
 * - Buang semua karakter selain angka, titik, dan koma.
 * - Pertahankan hanya pemisah desimal TERAKHIR; pemisah sebelumnya dihapus
 *   agar nilai seperti "1.000,5" tetap terbaca sebagai 1000,5.
 *
 * @param  {HTMLInputElement} inputElement  Element input yang akan diformat
 */
function formatDecimalInput(inputElement) {
    if (!inputElement) return;
    let value = String(inputElement.value).replace(/[^0-9.,]/g, '');
    const lastSepIndex = Math.max(value.lastIndexOf(','), value.lastIndexOf('.'));
    if (lastSepIndex !== -1) {
        value = value.slice(0, lastSepIndex).replace(/[.,]/g, '') + value.slice(lastSepIndex);
    }
    inputElement.value = value;
}

/**
 * Normalisasi semua field harga pada form menjadi numeric string.
 *
 * @param  {HTMLFormElement} form  Form element
 */
function normalizeInvoicePriceFields(form) {
    form.querySelectorAll('input[name*="[harga]"]').forEach(input => {
        const numeric = parseCurrencyInput(input.value);
        input.value = numeric ? String(numeric) : '0';
    });
}

// Ekspos ke window untuk handler inline Blade
window.parseCurrencyInput = parseCurrencyInput;
window.formatCurrencyInput = formatCurrencyInput;
window.formatDecimalInput = formatDecimalInput;

// ==========================================
// DETEKSI FORMAT ITEM (ADMIN vs SUPERADMIN)
// ==========================================

/**
 * Deteksi apakah halaman ini memakai format item admin
 * (deskripsi, harga, persentase) atau format standar superadmin
 * (keterangan, volume, satuan, harga).
 *
 * @return {boolean} true bila format admin
 */
function isAdminItemFormat() {
    return document.querySelector('.item-persentase') !== null;
}

/**
 * Ambil persentase item format admin (bersifat OPSIONAL).
 *
 * @param  {HTMLElement} row  Elemen baris (.item-row | .item-row-edit)
 * @return {number|null} Nilai persentase, atau null bila field dikosongkan
 */
function getItemPercentage(row) {
    const input = row?.querySelector('.item-persentase');
    if (!input || String(input.value ?? '').trim() === '') return null;
    return parseDecimalInput(input);
}

/**
 * Cek persentase item admin: kosong (null) boleh; bila diisi harus > 0
 * dan maksimal 100 (selaras validasi server).
 *
 * @param  {number|null} persentase
 * @return {boolean}
 */
function isValidItemPercentage(persentase) {
    return persentase === null || (persentase > 0 && persentase <= 100);
}

/**
 * Ambil volume item format superadmin (bersifat OPSIONAL).
 *
 * @param  {HTMLElement} row  Elemen baris (.item-row | .item-row-edit)
 * @return {number|null} Nilai volume, atau null bila field dikosongkan (borongan)
 */
function getItemVolume(row) {
    const input = row?.querySelector('.item-volume');
    if (!input || String(input.value ?? '').trim() === '') return null;
    return parseDecimalInput(input);
}

/**
 * Hitung jumlah (total) sebuah baris item.
 *
 * Format admin: harga x (persentase / 100); persentase kosong → harga
 * Format superadmin: volume x harga; volume kosong → harga (borongan)
 *
 * Referensi backend: InvoiceProyek::itemAmount().
 *
 * @param  {HTMLElement} row  Elemen baris (.item-row | .item-row-edit)
 * @return {number} Total baris
 */
function getItemRowTotal(row) {
    if (!row) return 0;
    const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);

    if (isAdminItemFormat()) {
        const persentase = getItemPercentage(row);
        return persentase === null ? harga : (harga * persentase) / 100;
    }

    const volume = getItemVolume(row);
    return volume === null ? harga : volume * harga;
}

// ==========================================
// FUNGSI PERHITUNGAN LIVE
// ==========================================

/**
 * Hitung total per baris item (ADD modal).
 *
 * @param  {HTMLInputElement} input  Element input yang berubah
 */
function calculateRowTotal(input) {
    const row = input.closest('.item-row');
    const total = getItemRowTotal(row);

    const totalSpan = row.querySelector('.item-total');
    if (totalSpan) {
        totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    updateInvoiceTotal();
}

/**
 * Hitung total per baris item (EDIT modal).
 *
 * @param  {HTMLInputElement} input  Element input yang berubah
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function calculateRowTotalEdit(input, invoiceNumber) {
    const row = input.closest('.item-row-edit');
    const total = getItemRowTotal(row);

    const totalSpan = row.querySelector('.item-total');
    if (totalSpan) {
        totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    updateEditInvoiceTotal(invoiceNumber);
}

/**
 * Hitung grand total untuk ADD modal.
 */
function updateInvoiceTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        grandTotal += getItemRowTotal(row);
    });

    const totalPreview = document.getElementById('invoice-total-preview');
    if (totalPreview) {
        totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    // Hitung ulang discount dan DP saat total berubah
    calculateDiscount();
    calculatePPN();
}

/**
 * Hitung grand total untuk EDIT modal.
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function updateEditInvoiceTotal(invoiceNumber) {
    if (!invoiceNumber) return;

    const modal = document.getElementById('editModal-' + invoiceNumber);
    if (!modal) return;

    let grandTotal = 0;

    modal.querySelectorAll('.item-row-edit').forEach(row => {
        grandTotal += getItemRowTotal(row);
    });

    const totalPreview = document.getElementById('invoice-total-preview-edit-' + invoiceNumber);
    if (totalPreview) {
        totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    calculateDiscountEdit(invoiceNumber);
    calculatePPNEdit(invoiceNumber);
}

/**
 * Aktifkan / nonaktifkan section & field diskon dan DP pada modal ADD.
 *
 * Alur:
 * - Bila belum ada item (hasTotal = false): field tipe/nilai diskon & DP
 *   dinonaktifkan (disabled), section diberi kelas opacity-40, dan semua
 *   pesan error/summary disembunyikan.
 * - Bila sudah ada total: field & section dikembalikan aktif.
 *
 * @param  {boolean} hasTotal  true bila total item > 0
 */
function setAddDependentSections(hasTotal) {
    ['discount-type', 'discount-value', 'dp-type', 'dp-value', 'ppn-value'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !hasTotal;
    });
    ['discount-section', 'dp-section', 'ppn-section'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('opacity-40', !hasTotal);
    });
    if (!hasTotal) {
        ['discount-error', 'discount-amount-error', 'dp-error', 'dp-amount-error', 'discount-summary', 'ppn-error', 'ppn-summary'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
    }
}

/**
 * Aktifkan / nonaktifkan section & field diskon dan DP pada modal EDIT.
 *
 * Versi edit memakai suffix "-{invoiceNumber}" pada seluruh id elemen,
 * sehingga setiap modal dihitung dan divalidasi secara terpisah.
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 * @param  {boolean} hasTotal      true bila total item > 0
 */
function setEditDependentSections(invoiceNumber, hasTotal) {
    ['discount-type-edit-', 'discount-value-edit-', 'dp-type-edit-', 'dp-value-edit-', 'ppn-value-edit-'].forEach(prefix => {
        const el = document.getElementById(prefix + invoiceNumber);
        if (el) el.disabled = !hasTotal;
    });
    ['discount-section-edit-', 'dp-section-edit-', 'ppn-section-edit-'].forEach(prefix => {
        const el = document.getElementById(prefix + invoiceNumber);
        if (el) el.classList.toggle('opacity-40', !hasTotal);
    });
    if (!hasTotal) {
        ['discount-error-edit-', 'discount-amount-error-edit-', 'dp-error-edit-', 'dp-amount-error-edit-', 'discount-summary-edit-', 'ppn-error-edit-', 'ppn-summary-edit-'].forEach(prefix => {
            const el = document.getElementById(prefix + invoiceNumber);
            if (el) el.classList.add('hidden');
        });
    }
}

// ==========================================
// PERHITUNGAN DISCOUNT & DP
// ==========================================

/**
 * Hitung discount untuk ADD modal.
 *
 * Alur:
 * 1. Ambil tipe discount (percentage | amount) dan nilai; reset bila tipe kosong.
 * 2. baseTotal = Σ jumlah baris item (lihat getItemRowTotal).
 * 3. Guard diskon melebihi total:
 *    - percentage ≥ 100% → tampilkan #discount-error, nilai di-cap ke 100.
 *    - amount ≥ baseTotal → tampilkan #discount-amount-error.
 * 4. discountAmount = percentage ? round(baseTotal × nilai / 100) : nilai.
 *    Untuk tipe amount, discountAmount di-cap agar tidak melebihi baseTotal.
 * 5. totalAfterDiscount = baseTotal − discountAmount, lalu tampilkan summary.
 * 6. PENTING: DP dihitung dari SISA SETELAH DISKON → panggil calculateDP().
 *
 * Catatan field nilai: untuk tipe 'percentage' nilai dibaca sebagai persen
 * (mis. "1,5" = 1,5%, batas maksimal 100%), untuk tipe 'amount' dibaca
 * sebagai nominal Rupiah.
 *
 * Referensi backend: InvoiceCalculatorService::calculateDiscountAmount().
 */
function calculateDiscount() {
    const discountType = document.getElementById('discount-type')?.value;
    const discountValueInput = document.getElementById('discount-value');
    let discountValue = parseDecimalInput(discountValueInput);
    const discountError = document.getElementById('discount-error');

    // Aktifkan/nonaktifkan berdasarkan tipe
    if (discountValueInput) {
        if (!discountType) {
            discountValueInput.value = '';
            discountValue = 0;
        }
    }

    // Ambil total dasar
    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        baseTotal += getItemRowTotal(row);
    });
    baseTotal = Math.round(baseTotal);

    setAddDependentSections(baseTotal > 0);

    // Validasi: jika percentage, batasi maksimal 100
    const isOverLimitPercent = discountType === 'percentage' && discountValue >= 100;
    if (discountError) discountError.classList.toggle('hidden', !isOverLimitPercent);

    // Validasi: nominal diskon tidak boleh >= total invoice
    const isOverLimitAmount = discountType === 'amount'
        && discountValue > 0
        && baseTotal > 0
        && discountValue >= baseTotal;
    const discountAmountError = document.getElementById('discount-amount-error');
    if (discountAmountError) discountAmountError.classList.toggle('hidden', !isOverLimitAmount);

    if (discountType === 'percentage' && discountValue >= 100) {
        discountValue = 100;
    }

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }
    if (discountType === 'amount' && discountAmount > baseTotal) {
        discountAmount = baseTotal;
    }

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);

    // Perbarui UI
    const discountAmountEl = document.getElementById('discount-amount');
    const totalAfterDiscountEl = document.getElementById('total-after-discount');

    if (discountAmountEl) discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
    if (totalAfterDiscountEl) totalAfterDiscountEl.textContent = 'Rp ' + totalAfterDiscount.toLocaleString('id-ID');

    const hasDiscount = discountType && discountValue > 0;
    const discountSummaryEl = document.getElementById('discount-summary');
    if (discountSummaryEl) discountSummaryEl.classList.toggle('hidden', !hasDiscount);

    // Hitung ulang DP berdasarkan total setelah discount
    calculateDP();
    calculatePPN();

    // Perbarui ringkasan Rekap Proyek (nilai invoice ini berubah)
    refreshRecapSummary(document.getElementById('addModal'));
}

/**
 * Hitung DP untuk ADD modal.
 *
 * Alur:
 * 1. Ambil tipe DP (percentage | amount) dan nilai; reset bila tipe kosong.
 * 2. baseTotal = Σ jumlah baris item (lihat getItemRowTotal).
 * 3. Hitung ulang discountAmount (identik dengan calculateDiscount), lalu
 *    totalAfterDiscount = baseTotal − discountAmount.
 * 4. calculationBase = totalAfterDiscount bila > 0, else baseTotal.
 *    PENTING: DP dihitung dari SISA SETELAH DISKON, bukan total kotor.
 * 5. Guard DP melebihi total:
 *    - percentage ≥ 100% → tampilkan #dp-error, nilai di-cap ke 100.
 *    - amount ≥ calculationBase → tampilkan #dp-amount-error.
 * 6. dpAmount = percentage ? round(calculationBase × nilai / 100) : nilai,
 *    di-cap agar tidak melebihi calculationBase untuk tipe amount.
 *
 * Catatan field nilai: untuk tipe 'percentage' dibaca sebagai persen
 * (maksimal 100%), untuk tipe 'amount' dibaca sebagai nominal Rupiah.
 *
 * Referensi backend: InvoiceCalculatorService::calculateDpAmount().
 */
function calculateDP() {
    const dpType = document.getElementById('dp-type')?.value;
    const dpValueInput = document.getElementById('dp-value');
    let dpValue = parseDecimalInput(dpValueInput);
    const dpError = document.getElementById('dp-error');
    const dpAmountError = document.getElementById('dp-amount-error');

    // Aktifkan/nonaktifkan berdasarkan tipe
    if (dpValueInput) {
        if (!dpType) {
            dpValueInput.value = '';
            dpValue = 0;
        }
    }

    // Ambil total dasar
    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        baseTotal += getItemRowTotal(row);
    });
    baseTotal = Math.round(baseTotal);

    setAddDependentSections(baseTotal > 0);

    // Periksa apakah ada discount
    const discountType = document.getElementById('discount-type')?.value;
    let discountValue = parseDecimalInput(document.getElementById('discount-value'));
    if (discountType === 'percentage') discountValue = Math.min(discountValue, 100);

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }
    if (discountType === 'amount' && discountAmount > baseTotal) {
        discountAmount = baseTotal;
    }

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);
    const calculationBase = totalAfterDiscount > 0 ? totalAfterDiscount : baseTotal;

    // Validasi: jika percentage, batasi maksimal 100
    const isOverLimitPercent = dpType === 'percentage' && dpValue >= 100;
    if (dpError) dpError.classList.toggle('hidden', !isOverLimitPercent);
    if (dpType === 'percentage' && dpValue >= 100) {
        dpValue = 100;
    }

    const isOverLimitAmount = dpType === 'amount'
        && dpValue > 0
        && calculationBase > 0
        && dpValue >= calculationBase;
    if (dpAmountError) dpAmountError.classList.toggle('hidden', !isOverLimitAmount);

    let dpAmount = 0;
    if (dpType && dpValue > 0) {
        dpAmount = dpType === 'percentage'
            ? Math.round((calculationBase * dpValue) / 100)
            : Math.round(dpValue);
    }
    if (dpType === 'amount' && dpAmount > calculationBase) {
        dpAmount = calculationBase;
    }

    // Perbarui UI
    const dpAmountEl = document.getElementById('dp-amount');
    if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
}

/**
 * Hitung PPN untuk ADD modal.
 *
 * Alur:
 * - baseTotal = Σ jumlah baris item (lihat getItemRowTotal).
 * - Hitung ulang discountAmount & totalAfterDiscount (identik dengan
 *   calculateDiscount) sebagai dasar pengenaan PPN.
 * - PPN amount = round(totalAfterDiscount × ppn / 100).
 * - Guard PPN ≥ 100% → tampilkan #ppn-error, nilai di-cap ke 100.
 * - Tampilkan summary PPN & total setelah PPN.
 *
 * Referensi backend: InvoiceProyek::getPpnAmount().
 */
function calculatePPN() {
    const ppnInput = document.getElementById('ppn-value');
    let ppn = parseDecimalInput(ppnInput);
    const ppnError = document.getElementById('ppn-error');

    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        baseTotal += getItemRowTotal(row);
    });
    baseTotal = Math.round(baseTotal);

    setAddDependentSections(baseTotal > 0);

    const discountType = document.getElementById('discount-type')?.value;
    let discountValue = parseDecimalInput(document.getElementById('discount-value'));
    if (discountType === 'percentage') discountValue = Math.min(discountValue, 100);

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }
    if (discountType === 'amount' && discountAmount > baseTotal) {
        discountAmount = baseTotal;
    }

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);

    const isOverLimitPercent = ppn >= 100;
    if (ppnError) ppnError.classList.toggle('hidden', !isOverLimitPercent);
    if (ppn >= 100) ppn = 100;

    const ppnAmount = ppn > 0 ? Math.round((totalAfterDiscount * ppn) / 100) : 0;
    const totalAfterPpn = totalAfterDiscount + ppnAmount;

    const ppnAmountEl = document.getElementById('ppn-amount');
    const totalAfterPpnEl = document.getElementById('total-after-ppn');
    if (ppnAmountEl) ppnAmountEl.textContent = 'Rp ' + ppnAmount.toLocaleString('id-ID');
    if (totalAfterPpnEl) totalAfterPpnEl.textContent = 'Rp ' + totalAfterPpn.toLocaleString('id-ID');

    const ppnSummaryEl = document.getElementById('ppn-summary');
    if (ppnSummaryEl) ppnSummaryEl.classList.toggle('hidden', !(ppn > 0));
}

/**
 * Hitung discount untuk EDIT modal.
 *
 * Versi edit dari calculateDiscount(): seluruh elemen memakai suffix
 * "-{invoiceNumber}" (mis. discount-type-edit-X, discount-amount-edit-X)
 * sehingga tiap modal dihitung secara terpisah.
 *
 * Alur:
 * - Ambil tipe/nilai diskon dari elemen ber-suffix edit.
 * - baseTotal dari baris .item-row-edit di dalam modal.
 * - Guard: percentage ≥ 100% → warning + cap; amount ≥ total → warning.
 * - Hitung discountAmount, totalAfterDiscount, lalu tampilkan summary.
 * - Panggil calculateDPEdit() (DP dihitung dari sisa setelah diskon).
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function calculateDiscountEdit(invoiceNumber) {
    const discountType = document.getElementById('discount-type-edit-' + invoiceNumber)?.value;
    const discountValueInput = document.getElementById('discount-value-edit-' + invoiceNumber);
    let discountValue = parseDecimalInput(discountValueInput);

    // Aktifkan/nonaktifkan berdasarkan tipe
    if (discountValueInput) {
        if (!discountType) {
            discountValueInput.value = 0;
            discountValue = 0;
        }
    }

    const modal = document.getElementById('editModal-' + invoiceNumber);
    let baseTotal = 0;
    if (modal) {
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            baseTotal += getItemRowTotal(row);
        });
    }
    baseTotal = Math.round(baseTotal);

    setEditDependentSections(invoiceNumber, baseTotal > 0);

    const isOverLimitPercent = discountType === 'percentage' && discountValue >= 100;
    const discountError = document.getElementById('discount-error-edit-' + invoiceNumber);
    if (discountError) discountError.classList.toggle('hidden', !isOverLimitPercent);

    const isOverLimitAmount = discountType === 'amount'
        && discountValue > 0
        && baseTotal > 0
        && discountValue >= baseTotal;
    const discountAmountError = document.getElementById('discount-amount-error-edit-' + invoiceNumber);
    if (discountAmountError) discountAmountError.classList.toggle('hidden', !isOverLimitAmount);

    if (discountType === 'percentage' && discountValue >= 100) {
        discountValue = 100;
    }

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }
    if (discountType === 'amount' && discountAmount > baseTotal) {
        discountAmount = baseTotal;
    }

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);

    const discountAmountEl = document.getElementById('discount-amount-edit-' + invoiceNumber);
    const totalAfterDiscountEl = document.getElementById('total-after-discount-edit-' + invoiceNumber);

    if (discountAmountEl) discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
    if (totalAfterDiscountEl) totalAfterDiscountEl.textContent = 'Rp ' + totalAfterDiscount.toLocaleString('id-ID');

    const hasDiscount = discountType && discountValue > 0;
    const discountSummaryEl = document.getElementById('discount-summary-edit-' + invoiceNumber);
    if (discountSummaryEl) discountSummaryEl.classList.toggle('hidden', !hasDiscount);

    calculateDPEdit(invoiceNumber);
    calculatePPNEdit(invoiceNumber);

    // Perbarui ringkasan Rekap Proyek (nilai invoice ini berubah)
    refreshRecapSummary(modal);
}

/**
 * Hitung DP untuk EDIT modal.
 *
 * Versi edit dari calculateDP() dengan suffix "-{invoiceNumber}" pada
 * seluruh id elemen. DP dihitung dari total SETELAH diskon
 * (calculationBase), bukan dari total kotor invoice.
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function calculateDPEdit(invoiceNumber) {
    const dpType = document.getElementById('dp-type-edit-' + invoiceNumber)?.value;
    const dpValueInput = document.getElementById('dp-value-edit-' + invoiceNumber);
    let dpValue = parseDecimalInput(dpValueInput);
    const dpError = document.getElementById('dp-error-edit-' + invoiceNumber);
    const dpAmountError = document.getElementById('dp-amount-error-edit-' + invoiceNumber);

    // Aktifkan/nonaktifkan berdasarkan tipe
    if (dpValueInput) {
        if (!dpType) {
            dpValueInput.value = 0;
            dpValue = 0;
        }
    }

    const modal = document.getElementById('editModal-' + invoiceNumber);
    let baseTotal = 0;
    if (modal) {
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            baseTotal += getItemRowTotal(row);
        });
    }
    baseTotal = Math.round(baseTotal);

    setEditDependentSections(invoiceNumber, baseTotal > 0);

    const discountType = document.getElementById('discount-type-edit-' + invoiceNumber)?.value;
    let discountValue = parseDecimalInput(document.getElementById('discount-value-edit-' + invoiceNumber));
    if (discountType === 'percentage') discountValue = Math.min(discountValue, 100);

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }
    if (discountType === 'amount' && discountAmount > baseTotal) {
        discountAmount = baseTotal;
    }

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);
    const calculationBase = totalAfterDiscount > 0 ? totalAfterDiscount : baseTotal;

    // Validasi: jika percentage, batasi maksimal 100
    const isOverLimitPercent = dpType === 'percentage' && dpValue >= 100;
    if (dpError) dpError.classList.toggle('hidden', !isOverLimitPercent);
    if (dpType === 'percentage' && dpValue >= 100) {
        dpValue = 100;
    }

    const isOverLimitAmount = dpType === 'amount'
        && dpValue > 0
        && calculationBase > 0
        && dpValue >= calculationBase;
    if (dpAmountError) dpAmountError.classList.toggle('hidden', !isOverLimitAmount);

    let dpAmount = 0;
    if (dpType && dpValue > 0) {
        dpAmount = dpType === 'percentage'
            ? Math.round((calculationBase * dpValue) / 100)
            : Math.round(dpValue);
    }
    if (dpType === 'amount' && dpAmount > calculationBase) {
        dpAmount = calculationBase;
    }

    const dpAmountEl = document.getElementById('dp-amount-edit-' + invoiceNumber);
    if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
}

/**
 * Hitung PPN untuk EDIT modal.
 *
 * Versi edit dari calculatePPN() dengan suffix "-{invoiceNumber}" pada
 * seluruh id elemen. PPN dihitung dari total setelah diskon.
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function calculatePPNEdit(invoiceNumber) {
    const ppnInput = document.getElementById('ppn-value-edit-' + invoiceNumber);
    let ppn = parseDecimalInput(ppnInput);
    const ppnError = document.getElementById('ppn-error-edit-' + invoiceNumber);

    const modal = document.getElementById('editModal-' + invoiceNumber);
    let baseTotal = 0;
    if (modal) {
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            baseTotal += getItemRowTotal(row);
        });
    }
    baseTotal = Math.round(baseTotal);

    setEditDependentSections(invoiceNumber, baseTotal > 0);

    const discountType = document.getElementById('discount-type-edit-' + invoiceNumber)?.value;
    let discountValue = parseDecimalInput(document.getElementById('discount-value-edit-' + invoiceNumber));
    if (discountType === 'percentage') discountValue = Math.min(discountValue, 100);

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }
    if (discountType === 'amount' && discountAmount > baseTotal) {
        discountAmount = baseTotal;
    }

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);

    const isOverLimitPercent = ppn >= 100;
    if (ppnError) ppnError.classList.toggle('hidden', !isOverLimitPercent);
    if (ppn >= 100) ppn = 100;

    const ppnAmount = ppn > 0 ? Math.round((totalAfterDiscount * ppn) / 100) : 0;
    const totalAfterPpn = totalAfterDiscount + ppnAmount;

    const ppnAmountEl = document.getElementById('ppn-amount-edit-' + invoiceNumber);
    const totalAfterPpnEl = document.getElementById('total-after-ppn-edit-' + invoiceNumber);
    if (ppnAmountEl) ppnAmountEl.textContent = 'Rp ' + ppnAmount.toLocaleString('id-ID');
    if (totalAfterPpnEl) totalAfterPpnEl.textContent = 'Rp ' + totalAfterPpn.toLocaleString('id-ID');

    const ppnSummaryEl = document.getElementById('ppn-summary-edit-' + invoiceNumber);
    if (ppnSummaryEl) ppnSummaryEl.classList.toggle('hidden', !(ppn > 0));
}

// ==========================================
// NO INVOICE ADMIN ({nomor}/AKI/{bulan romawi}/{yyyy})
// ==========================================

/** Pola nomor invoice admin format baru (selaras InvoiceProyek::ADMIN_NUMBER_PATTERN). */
const ADMIN_NUMBER_PATTERN = /^(\d+)\/AKI\/([IVXLCDM]+)\/(\d{4})$/;

let takenAdminNumbersCache = null;

/**
 * Daftar nomor invoice admin format baru yang sudah dipakai.
 *
 * Sumber: <script type="application/json" id="proyek-invoice-taken-numbers">
 * yang dirender halaman (ProyekInvoiceService::getTakenAdminInvoiceNumbers).
 *
 * @return {string[]}
 */
function getTakenAdminNumbers() {
    if (takenAdminNumbersCache !== null) return takenAdminNumbersCache;

    try {
        const raw = document.getElementById('proyek-invoice-taken-numbers')?.textContent || '[]';
        const parsed = JSON.parse(raw);
        takenAdminNumbersCache = Array.isArray(parsed) ? parsed : [];
    } catch (err) {
        takenAdminNumbersCache = [];
    }

    return takenAdminNumbersCache;
}

/**
 * Konversi angka 1-12 (bulan) ke numerals Romawi.
 *
 * @param  {number} number
 * @return {string}
 */
function toRoman(number) {
    const map = [[1000, 'M'], [900, 'CM'], [500, 'D'], [400, 'CD'], [100, 'C'], [90, 'XC'],
        [50, 'L'], [40, 'XL'], [10, 'X'], [9, 'IX'], [5, 'V'], [4, 'IV'], [1, 'I']];
    let result = '';
    let rest = number;
    map.forEach(([value, symbol]) => {
        while (rest >= value) {
            result += symbol;
            rest -= value;
        }
    });
    return result;
}

/**
 * Pecah nilai input tanggal (YYYY-MM-DD) menjadi bulan romawi & tahun.
 *
 * @param  {string} dateValue
 * @return {{roman: string, year: string}|null}
 */
function parseInvoiceDateParts(dateValue) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(dateValue || ''));
    if (!match) return null;
    const month = parseInt(match[2], 10);
    if (month < 1 || month > 12) return null;
    return { roman: toRoman(month), year: match[1] };
}

/**
 * Perbarui satu field No Invoice admin: preview nomor lengkap, saran nomor
 * berikutnya, dan cek nomor dobel.
 *
 * Alur:
 * 1. Nomor lengkap = {nomor urut}/AKI/{bulan romawi tgl invoice}/{tahun}.
 * 2. Hint: nomor terbesar yang sudah dipakai pada tahun tsb + saran
 *    nomor berikutnya (placeholder input pada modal tambah).
 * 3. Nomor dobel → setCustomValidity agar submit diblok browser dengan
 *    pesan yang jelas; pesan juga tampil di bawah input.
 *
 * Referensi backend: ProyekInvoiceService::composeAdminInvoiceNumber().
 *
 * @param {HTMLElement} field  Elemen [data-admin-number-field]
 */
function refreshAdminNumberField(field) {
    const form = field.closest('form');
    const seqInput = field.querySelector('.invoice-seq-input');
    const dateInput = form?.querySelector('input[name="invoice_date"]');
    const preview = field.querySelector('.invoice-number-preview');
    const hint = field.querySelector('.invoice-number-hint');
    const errorBox = field.querySelector('.invoice-number-error');
    const errorText = field.querySelector('.invoice-number-error-text');
    if (!seqInput) return;

    const currentNumber = field.dataset.currentNumber || '';
    const sequence = String(seqInput.value ?? '').trim();
    const dateParts = parseInvoiceDateParts(dateInput?.value);
    const fullNumber = sequence && dateParts ? `${sequence}/AKI/${dateParts.roman}/${dateParts.year}` : null;

    if (preview) {
        preview.textContent = `${sequence || '___'}/AKI/${dateParts ? dateParts.roman : '{bulan}'}/${dateParts ? dateParts.year : '{tahun}'}`;
    }

    // Saran nomor berikutnya berdasarkan nomor terbesar pada tahun yang sama
    const year = dateParts ? dateParts.year : String(new Date().getFullYear());
    let maxSequence = null;
    let maxNumber = '';
    let width = 3;
    getTakenAdminNumbers().forEach(number => {
        const match = ADMIN_NUMBER_PATTERN.exec(number);
        if (!match || match[3] !== year || number === currentNumber) return;
        const value = parseInt(match[1], 10);
        if (maxSequence === null || value > maxSequence) {
            maxSequence = value;
            maxNumber = number;
            width = Math.max(3, match[1].length);
        }
    });

    if (hint) {
        if (maxSequence !== null) {
            const suggestion = String(maxSequence + 1).padStart(width, '0');
            hint.textContent = `Nomor terakhir tahun ${year}: ${maxNumber} — saran nomor berikutnya: ${suggestion}`;
            hint.classList.remove('hidden');
            if (!currentNumber) seqInput.placeholder = `Contoh: ${suggestion}`;
        } else {
            hint.classList.add('hidden');
        }
    }

    let message = '';
    if (sequence && !/^\d+$/.test(sequence)) {
        message = 'No invoice hanya boleh berisi angka (contoh 060).';
    } else if (fullNumber && fullNumber !== currentNumber && getTakenAdminNumbers().includes(fullNumber)) {
        message = `No invoice ${fullNumber} sudah digunakan. Silakan gunakan nomor lain.`;
    }

    seqInput.setCustomValidity(message);
    if (errorText) errorText.textContent = message;
    if (errorBox) errorBox.classList.toggle('hidden', !message);
}

/**
 * Pasang listener untuk semua field No Invoice admin (modal tambah & edit):
 * input nomor urut & perubahan Tanggal Invoice memicu refresh preview.
 */
function initAdminNumberFields() {
    document.querySelectorAll('[data-admin-number-field]').forEach(field => {
        const form = field.closest('form');
        const seqInput = field.querySelector('.invoice-seq-input');
        const dateInput = form?.querySelector('input[name="invoice_date"]');
        const refresh = () => refreshAdminNumberField(field);

        seqInput?.addEventListener('input', refresh);
        dateInput?.addEventListener('input', refresh);
        dateInput?.addEventListener('change', refresh);
        refresh();
    });
}

// ==========================================
// TAUTAN REKAP PROYEK (OPSIONAL)
// ==========================================

/**
 * Format angka ke Rupiah (dengan tanda minus bila negatif).
 *
 * @param  {number} value
 * @return {string}
 */
function formatRupiah(value) {
    const rounded = Math.round(value || 0);
    return (rounded < 0 ? '-' : '') + 'Rp ' + Math.abs(rounded).toLocaleString('id-ID');
}

/**
 * Nilai tagihan invoice pada sebuah form: total item setelah diskon,
 * sebelum PPN (selaras InvoiceProyek::getBilledAmount()).
 *
 * @param  {HTMLElement} scope  Form / modal yang memuat item & diskon
 * @return {number}
 */
function getFormBilledAmount(scope) {
    if (!scope) return 0;

    let baseTotal = 0;
    scope.querySelectorAll('.item-row, .item-row-edit').forEach(row => {
        baseTotal += getItemRowTotal(row);
    });
    baseTotal = Math.round(baseTotal);

    const discountType = scope.querySelector('select[name="discount_type"]')?.value;
    let discountValue = parseDecimalInput(scope.querySelector('input[name="discount_value"]'));
    if (discountType === 'percentage') discountValue = Math.min(discountValue, 100);

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }
    discountAmount = Math.min(discountAmount, baseTotal);

    return Math.max(0, baseTotal - discountAmount);
}

/**
 * Perbarui panel ringkasan Rekap Proyek di dalam sebuah modal.
 *
 * Alur:
 * - Tanpa rekap terpilih → panel disembunyikan.
 * - Sudah Ditagih = total invoice tertaut (data-billed) dikurangi nilai
 *   invoice ini yang tersimpan bila rekapnya sama (modal edit), agar tidak
 *   terhitung dua kali.
 * - Sisa = Nilai Proyek − Sudah Ditagih − Invoice Ini; negatif → peringatan.
 *
 * @param {HTMLElement|null} container  Modal tambah / edit
 */
function refreshRecapSummary(container) {
    if (!container) return;

    container.querySelectorAll('[data-recap-picker]').forEach(picker => {
        const select = picker.querySelector('.recap-select');
        const summary = picker.querySelector('.recap-summary');
        const option = select?.selectedOptions?.[0];

        if (!summary) return;
        if (!option || !option.value) {
            summary.classList.add('hidden');
            return;
        }

        const total = parseInt(option.dataset.total, 10) || 0;
        let billed = parseInt(option.dataset.billed, 10) || 0;
        if (picker.dataset.currentRecap && picker.dataset.currentRecap === option.value) {
            billed -= parseInt(picker.dataset.currentBilled, 10) || 0;
        }
        billed = Math.max(0, billed);

        const current = getFormBilledAmount(picker.closest('form') || container);
        const remaining = total - billed - current;

        summary.querySelector('.recap-summary-total').textContent = formatRupiah(total);
        summary.querySelector('.recap-summary-billed').textContent = formatRupiah(billed);
        summary.querySelector('.recap-summary-current').textContent = formatRupiah(current);

        const remainingEl = summary.querySelector('.recap-summary-remaining');
        remainingEl.textContent = formatRupiah(remaining);
        remainingEl.classList.toggle('text-success', remaining >= 0);
        remainingEl.classList.toggle('text-error', remaining < 0);

        const overBox = summary.querySelector('.recap-summary-over');
        const overText = summary.querySelector('.recap-summary-over-text');
        if (overText) overText.textContent = `Total tagihan melebihi nilai proyek sebesar ${formatRupiah(Math.abs(remaining))}.`;
        if (overBox) overBox.classList.toggle('hidden', remaining >= 0);

        summary.classList.remove('hidden');
    });
}

/**
 * Prefill field yang masih kosong dari data rekap terpilih (tanpa mengunci).
 *
 * Field yang sebelumnya diisi otomatis (dan belum diubah user) ikut
 * diganti saat user berpindah rekap; field yang diketik user tidak disentuh.
 * - Superadmin: Nama Proyek ← nama proyek rekap.
 * - Admin (tanpa field Nama Proyek): Deskripsi Proyek ← nama proyek rekap.
 * - Lokasi ← lokasi rekap; Kepada ← penerima RAB sumber (bila ada).
 *
 * @param {HTMLFormElement} form
 * @param {HTMLOptionElement} option  Opsi rekap terpilih
 */
function applyRecapPrefill(form, option) {
    if (!form || !option || !option.value) return;

    const prefill = (field, value) => {
        if (!field || !value) return;
        const current = String(field.value ?? '').trim();
        const wasAutofilled = field.dataset.recapAutofill !== undefined && current === field.dataset.recapAutofill;
        if (current !== '' && !wasAutofilled) return;

        field.value = value;
        field.dataset.recapAutofill = value;
        field.setCustomValidity?.('');
    };

    const projectName = option.dataset.projectName || '';
    const proyekField = form.querySelector('[name="proyek"]');
    if (proyekField) {
        prefill(proyekField, projectName);
    } else {
        prefill(form.querySelector('[name="project_description"]'), projectName);
    }
    prefill(form.querySelector('[name="location"]'), option.dataset.location || '');
    prefill(form.querySelector('[name="recipient"]'), option.dataset.recipient || '');
}

/**
 * Pasang listener pilihan Rekap Proyek pada modal tambah & edit, lalu
 * tampilkan ringkasan awal (modal edit yang sudah tertaut).
 */
function initRecapPickers() {
    document.querySelectorAll('[data-recap-picker]').forEach(picker => {
        const select = picker.querySelector('.recap-select');
        const container = picker.closest('[id^="editModal-"], #addModal');
        if (!select) return;

        select.addEventListener('change', () => {
            applyRecapPrefill(picker.closest('form'), select.selectedOptions?.[0]);
            refreshRecapSummary(container);
        });

        refreshRecapSummary(container);
    });
}

// Ekspos ke window untuk handler inline Blade
window.calculateRowTotal = calculateRowTotal;
window.calculateRowTotalEdit = calculateRowTotalEdit;
window.calculateDiscount = calculateDiscount;
window.calculateDP = calculateDP;
window.calculatePPN = calculatePPN;
window.calculateDiscountEdit = calculateDiscountEdit;
window.calculateDPEdit = calculateDPEdit;
window.calculatePPNEdit = calculatePPNEdit;

// ==========================================
// PESAN ERROR ITEM
// ==========================================

const DEFAULT_ITEMS_ERROR_MESSAGE = 'Minimal harus ada 1 item dalam invoice dengan data lengkap';
const PERCENTAGE_ERROR_MESSAGE = 'Persentase item harus lebih dari 0 dan maksimal 100, atau dikosongkan (Jumlah = Harga)';

/**
 * Tampilkan kotak error item dengan pesan tertentu.
 *
 * @param {HTMLElement|null} errorBox  #items-error / .items-error-edit
 * @param {string} message
 */
function showItemsError(errorBox, message) {
    if (!errorBox) return;
    const textEl = errorBox.querySelector('span');
    if (textEl) textEl.textContent = message;
    errorBox.classList.remove('hidden');
}

// ==========================================
// VALIDASI REKENING PEMBAYARAN
// ==========================================

/**
 * Validasi pemilihan rekening pembayaran pada ADD modal.
 *
 * @return {boolean} Apakah ada minimal 1 rekening yang dipilih
 */
function validatePaymentSelection() {
    const addModal = document.getElementById('addModal');
    const checkboxes = addModal?.querySelectorAll('.payment-account-checkbox') ?? [];
    const errorDiv = document.getElementById('payment-account-error');
    const submitBtn = document.getElementById('submit-btn-addModal');

    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);

    if (!anyChecked) {
        errorDiv?.classList.remove('hidden');
    } else {
        errorDiv?.classList.add('hidden');
    }

    if (submitBtn) {
        submitBtn.disabled = !anyChecked;
        submitBtn.classList.toggle('opacity-50', !anyChecked);
        submitBtn.classList.toggle('cursor-not-allowed', !anyChecked);
    }

    return anyChecked;
}

/**
 * Validasi pemilihan rekening pembayaran pada EDIT modal.
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 * @return {boolean} Apakah ada minimal 1 rekening yang dipilih
 */
function validatePaymentSelectionEdit(invoiceNumber) {
    const modal = document.getElementById('editModal-' + invoiceNumber);
    const checkboxes = modal?.querySelectorAll('.payment-account-checkbox') ?? [];
    const submitBtn = document.getElementById('submit-btn-editModal-' + invoiceNumber);

    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);

    if (submitBtn) {
        submitBtn.disabled = !anyChecked;
        submitBtn.classList.toggle('opacity-50', !anyChecked);
        submitBtn.classList.toggle('cursor-not-allowed', !anyChecked);
    }

    return anyChecked;
}

window.validatePaymentSelection = validatePaymentSelection;
window.validatePaymentSelectionEdit = validatePaymentSelectionEdit;

// ==========================================
// HAPUS MASSAL
// ==========================================

/**
 * Submit form hapus massal.
 */
function submitDeleteForm() {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById('deleteForm');
    if (form) form.submit();
}

window.submitDeleteForm = submitDeleteForm;

// ==========================================
// HAPUS INVOICE TUNGGAL
// ==========================================

/**
 * Hapus invoice proyek tunggal via hidden form submission.
 *
 * @param  {string} invoiceNumber  Nomor invoice yang akan dihapus
 */
function deleteInvoiceProyek(invoiceNumber) {
    closeModal('deleteModal-' + invoiceNumber);

    const form = document.getElementById('deleteForm-' + invoiceNumber);
    if (form) {
        form.submit();
    }
}

window.deleteInvoiceProyek = deleteInvoiceProyek;

// ==========================================
// DOM SIAP
// ==========================================

/**
 * Inisialisasi seluruh fungsionalitas halaman setelah DOM siap.
 *
 * Alur inisialisasi:
 * - Checkbox select all & tombol hapus massal.
 * - Tambah/hapus item row (modal ADD & EDIT) + re-index field items.
 * - Submit form ADD (serialisasi item ke JSON) & form EDIT (normalisasi harga).
 * - Inisialisasi total, diskon/DP, dan status tombol rekening pembayaran.
 * - Filter URL bulan/tahun.
 * - Reset status submit saat navigasi kembali (pageshow).
 */
document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // FUNGSIONALITAS CHECKBOX PILIH SEMUA
    // ==========================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Update status tombol hapus massal berdasarkan checkbox tercentang.
     *
     * Tombol #delete-button dinonaktifkan bila tidak ada invoice dipilih.
     */
    function updateDeleteButtonState() {
        const anyChecked = Array.from(invoiceCheckboxes).some(cb => cb.checked);
        if (deleteButton) deleteButton.disabled = !anyChecked;
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            invoiceCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
            updateDeleteButtonState();
        });
    }

    invoiceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                selectAllCheckbox.checked = Array.from(invoiceCheckboxes).every(cb => cb.checked);
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ==========================================
    // FUNGSIONALITAS TAMBAH ITEM - MODAL ADD
    // ==========================================

    /**
     * Template baris item untuk modal ADD (tanpa atribut name).
     *
     * Menyesuaikan dengan format role: admin (deskripsi, harga, persentase)
     * atau superadmin (keterangan, volume, satuan, harga).
     *
     * @return {string} HTML baris item
     */
    function itemRowHtml() {
        if (isAdminItemFormat()) {
            return `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" class="item-deskripsi border rounded p-2 w-full" placeholder="Deskripsi *" required
                        oninvalid="this.setCustomValidity('Deskripsi tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" class="item-harga border rounded p-2 w-full"
                        placeholder="Harga (Rp) *" required
                        oninput="formatCurrencyInput(this); calculateRowTotal(this)"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" inputmode="decimal" class="item-persentase border rounded p-2 w-full"
                        placeholder="% (opsional)" title="Kosongkan bila Jumlah = Harga"
                        oninput="formatDecimalInput(this); calculateRowTotal(this)">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <div></div>
                    <button type="button"
                        class="remove-item bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
        }

        return `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                <input type="text" class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                    oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
                <input type="number" step="0.01" min="0" class="item-volume border rounded p-2 w-full"
                    placeholder="Volume (opsional)" title="Kosongkan bila Jumlah = Harga (borongan)"
                    oninput="calculateRowTotal(this); this.setCustomValidity('')">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                <input type="text" class="item-satuan border rounded p-2 w-full"
                    placeholder="Satuan (opsional)">
                <input type="text" inputmode="numeric" class="item-harga border rounded p-2 w-full"
                    placeholder="Rp 0" required
                    oninput="formatCurrencyInput(this); calculateRowTotal(this); this.setCustomValidity('')"
                    oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                <div class="flex items-center">
                    <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                </div>
                <button type="button"
                    class="remove-item bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
    }

    /**
     * Listener tombol "Tambah Item" (modal ADD).
     *
     * Membuat baris .item-row baru sesuai format role (admin: deskripsi/harga/%
     * atau superadmin: keterangan/volume/satuan/harga), menempelkannya ke
     * #items-list, memasang ulang listener hapus, dan menghitung ulang grand total.
     */
    const addItemBtn = document.getElementById('add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const itemsError = document.getElementById('items-error');
            if (itemsError) itemsError.classList.add('hidden');
            const itemsContainer = document.getElementById('items-list');
            const newItem = document.createElement('div');
            newItem.className = 'item-row mb-3 p-3 border rounded bg-surface-secondary';
            newItem.innerHTML = itemRowHtml();
            itemsContainer.appendChild(newItem);
            attachRemoveListener();
            updateInvoiceTotal();
        });
    }

    /**
     * Pasang listener hapus untuk semua tombol .remove-item.
     *
     * removeEventListener + addEventListener dipakai untuk mencegah
     * duplikasi listener saat baris item baru ditambahkan.
     */
    function attachRemoveListener() {
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.removeEventListener('click', removeItemClickHandler);
            btn.addEventListener('click', removeItemClickHandler);
        });
    }

    /**
     * Hapus baris item (modal ADD) saat tombol hapus diklik.
     *
     * Baris terdekat .item-row dihapus lalu grand total dihitung ulang.
     *
     * @param  {Event} e  Event klik
     */
    function removeItemClickHandler(e) {
        e.preventDefault();
        this.closest('.item-row').remove();
        updateInvoiceTotal();
    }

    attachRemoveListener();

    // ==========================================
    // FUNGSIONALITAS TAMBAH ITEM - MODAL EDIT
    // ==========================================

    /**
     * Template baris item untuk modal EDIT (memakai atribut name).
     *
     * Menyesuaikan dengan format role: admin (deskripsi, harga, persentase)
     * atau superadmin (keterangan, volume, satuan, harga).
     *
     * @param  {number} index          Indeks untuk name items[index][...]
     * @param  {string} invoiceNumber  Nomor invoice untuk memicu perhitungan edit
     * @return {string} HTML baris item
     */
    function itemRowEditHtml(index, invoiceNumber) {
        if (isAdminItemFormat()) {
            return `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" name="items[${index}][deskripsi]"
                        class="item-deskripsi border rounded p-2 w-full" placeholder="Deskripsi *" required
                        oninvalid="this.setCustomValidity('Deskripsi tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" name="items[${index}][harga]"
                        class="item-harga border rounded p-2 w-full" placeholder="Harga (Rp) *" required
                        oninput="formatCurrencyInput(this); calculateRowTotalEdit(this, '${invoiceNumber}')"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" inputmode="decimal" name="items[${index}][persentase]"
                        class="item-persentase border rounded p-2 w-full" placeholder="% (opsional)"
                        title="Kosongkan bila Jumlah = Harga"
                        oninput="formatDecimalInput(this); calculateRowTotalEdit(this, '${invoiceNumber}')">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <div></div>
                    <button type="button"
                        class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
        }

        return `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                <input type="text" name="items[${index}][keterangan]"
                    class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                    oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
                <input type="number" step="0.01" min="0" name="items[${index}][volume]"
                    class="item-volume border rounded p-2 w-full" placeholder="Volume (opsional)"
                    title="Kosongkan bila Jumlah = Harga (borongan)"
                    oninput="calculateRowTotalEdit(this, '${invoiceNumber}'); this.setCustomValidity('')">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                <input type="text" name="items[${index}][satuan]"
                    class="item-satuan border rounded p-2 w-full" placeholder="Satuan (opsional)">
                <input type="text" inputmode="numeric" name="items[${index}][harga]"
                    class="item-harga border rounded p-2 w-full" placeholder="Rp 0" required
                    oninput="formatCurrencyInput(this); calculateRowTotalEdit(this, '${invoiceNumber}')"
                    oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                <div class="flex items-center">
                    <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                </div>
                <button type="button"
                    class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
    }

    /**
     * Listener tombol "Tambah Item" pada modal EDIT.
     *
     * Membuat baris .item-row-edit baru sesuai format role dengan name field
     * items[{index}][...] berdasarkan jumlah baris saat ini, lalu memasang
     * listener hapus-edit.
     */
    document.querySelectorAll('[id^="add-item-edit-"]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const container = this.closest('[id^="items-container-edit-"]');
            const errorDiv = container ? container.querySelector('.items-error-edit') : null;
            if (errorDiv) errorDiv.classList.add('hidden');
            const invoiceNumber = this.id.replace('add-item-edit-', '');
            const itemsContainer = document.getElementById('items-list-edit-' + invoiceNumber);
            const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
            const newIndex = currentItems.length;

            const newItem = document.createElement('div');
            newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-surface-secondary';
            newItem.innerHTML = itemRowEditHtml(newIndex, invoiceNumber);
            itemsContainer.appendChild(newItem);
            attachRemoveListenerEdit();
        });
    });

    /**
     * Pasang listener hapus untuk semua tombol .remove-item-edit.
     *
     * Menghindari duplikasi listener dengan melepas handler lama terlebih
     * dahulu sebelum memasang yang baru.
     */
    function attachRemoveListenerEdit() {
        document.querySelectorAll('.remove-item-edit').forEach(btn => {
            btn.removeEventListener('click', removeItemEditClickHandler);
            btn.addEventListener('click', removeItemEditClickHandler);
        });
    }

    /**
     * Hapus baris item (modal EDIT) saat tombol hapus-edit diklik.
     *
     * Alur:
     * - Baris minimal 1 dijaga: bila tersisa ≤ 1 baris, tampilkan error
     *   #items-error-edit dan batalkan penghapusan.
     * - Hapus baris terdekat, lalu re-index semua name field items
     *   (items[{index}][{field}]) agar urutan tetap konsisten saat submit.
     * - Hitung ulang total modal.
     *
     * @param  {Event} e  Event klik
     */
    function removeItemEditClickHandler(e) {
        e.preventDefault();
        const itemsContainer = this.closest('[id^="items-list-edit-"]');
        const remainingItems = itemsContainer.querySelectorAll('.item-row-edit');

        if (remainingItems.length <= 1) {
            const container = this.closest('[id^="items-container-edit-"]');
            const errorDiv = container ? container.querySelector('.items-error-edit') : null;
            if (errorDiv) errorDiv.classList.remove('hidden');
            return;
        }

        this.closest('.item-row-edit').remove();

        // Indeks ulang items
        itemsContainer.querySelectorAll('.item-row-edit').forEach((row, index) => {
            row.querySelectorAll('input[name^="items"]').forEach(input => {
                const fieldName = input.name.match(/\[(\w+)\]$/)[1];
                input.name = `items[${index}][${fieldName}]`;
            });
        });

        // Perbarui total setelah item dihapus
        const invoiceNumber = itemsContainer.id.replace('items-list-edit-', '');
        if (invoiceNumber) {
            updateEditInvoiceTotal(invoiceNumber);
        }
    }

    attachRemoveListenerEdit();

    // ==========================================
    // PENANGANAN SUBMIT FORM - MODAL ADD
    // ==========================================

    /**
     * Submit form modal ADD.
     *
     * Alur:
     * - Serialisasi setiap baris .item-row menjadi { keterangan, volume,
     *   satuan, harga }; baris yang tidak lengkap dilewati. Volume & satuan
     *   opsional: kosong → null (volume kosong = borongan, Jumlah = Harga).
     * - Bila tidak ada item valid, tampilkan #items-error dan batalkan submit.
     * - Tulis JSON ke field hidden #items-json.
     * - Panggil handleFormSubmit() untuk proteksi submit ganda.
     */
    const addModalElement = document.getElementById('addModal');
    if (addModalElement) {
        const addForm = addModalElement.querySelector('form');
        if (addForm) {
            addForm.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');

                // Serialisasi items
                const items = [];
                const itemRows = this.querySelectorAll('.item-row');
                let hasInvalidPercentage = false;

                itemRows.forEach(row => {
                    if (isAdminItemFormat()) {
                        const deskripsi = row.querySelector('.item-deskripsi')?.value || '';
                        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
                        // Persentase opsional: kosong → null (Jumlah = Harga)
                        const persentase = getItemPercentage(row);

                        if (!isValidItemPercentage(persentase)) {
                            hasInvalidPercentage = true;
                            return;
                        }

                        if (deskripsi && !isNaN(harga) && harga > 0) {
                            items.push({ deskripsi, harga, persentase });
                        }
                        return;
                    }

                    const keterangan = row.querySelector('.item-keterangan')?.value || '';
                    const hargaInput = row.querySelector('.item-harga');

                    // Volume & satuan opsional: kosong → null (volume kosong = borongan)
                    const volume = getItemVolume(row);
                    const satuan = String(row.querySelector('.item-satuan')?.value ?? '').trim() || null;
                    const harga = hargaInput ? parseCurrencyInput(hargaInput.value) : 0;
                    const isVolumeValid = volume === null || (!isNaN(volume) && volume > 0);

                    if (keterangan && isVolumeValid && !isNaN(harga) && harga > 0) {
                        items.push({ keterangan, volume, satuan, harga });
                    }
                });

                if (hasInvalidPercentage || items.length === 0) {
                    e.preventDefault();
                    showItemsError(this.querySelector('#items-error'), hasInvalidPercentage
                        ? PERCENTAGE_ERROR_MESSAGE
                        : DEFAULT_ITEMS_ERROR_MESSAGE);
                    return false;
                }

                const itemsJsonField = this.querySelector('#items-json');
                if (!itemsJsonField) {
                    e.preventDefault();
                    alert('Error: Field items tidak ditemukan');
                    return false;
                }

                itemsJsonField.value = JSON.stringify(items);

                // Cegah submit ganda
                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }

                return true;
            });
        }
    }

    // ==========================================
    // PENANGANAN SUBMIT FORM - MODAL EDIT
    // ==========================================

    /**
     * Submit form modal EDIT (PUT).
     *
     * Alur:
     * - Pastikan minimal ada 1 baris .item-row-edit (jika tidak, tampilkan
     *   #items-error-edit dan batalkan).
     * - Normalisasi field harga via normalizeInvoicePriceFields().
     * - Panggil handleFormSubmit() untuk proteksi submit ganda.
     */
    document.querySelectorAll('form[action*="proyek-invoice"]').forEach(form => {
        if (form.querySelector('[name="_method"][value="PUT"]')) {
            form.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');

                const editItems = this.querySelectorAll('.item-row-edit');
                if (editItems.length === 0) {
                    e.preventDefault();
                    showItemsError(this.querySelector('.items-error-edit'), DEFAULT_ITEMS_ERROR_MESSAGE);
                    return false;
                }

                // Persentase item admin opsional, tapi bila diisi harus > 0 dan ≤ 100
                const hasInvalidPercentage = isAdminItemFormat()
                    && Array.from(editItems).some(row => !isValidItemPercentage(getItemPercentage(row)));
                if (hasInvalidPercentage) {
                    e.preventDefault();
                    showItemsError(this.querySelector('.items-error-edit'), PERCENTAGE_ERROR_MESSAGE);
                    return false;
                }

                normalizeInvoicePriceFields(this);

                // Cegah submit ganda
                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });

    // Format input harga yang sudah ada pada modal edit
    document.querySelectorAll('[id^="editModal-"] .item-harga').forEach(input => {
        if (input.value) formatCurrencyInput(input);
    });

    // ==========================================
    // INISIALISASI TOTAL SAAT HALAMAN DIMUAT
    // ==========================================

    updateInvoiceTotal();

    // No Invoice admin (preview nomor lengkap + cek nomor dobel) &
    // pilihan Rekap Proyek (prefill + ringkasan sisa tagihan)
    initAdminNumberFields();
    initRecapPickers();

    // Inisialisasi total & tampilan PPN untuk semua modal edit
    // (dilakukan di sini agar format admin -- yang tidak punya discount/DP --
    //  tetap menghitung ulang total & PPN saat halaman dimuat)
    document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
        const invoiceNumber = modal.id.replace('editModal-', '');
        updateEditInvoiceTotal(invoiceNumber);
    });

    // ==========================================
    // INISIALISASI STATUS TOMBOL REKENING PEMBAYARAN
    // ==========================================

    // Modal ADD: nonaktifkan submit jika tidak ada checkbox tercentang saat dimuat
    validatePaymentSelection();

    // Modal EDIT: nonaktifkan submit jika tidak ada checkbox tercentang, tambahkan event listener change
    document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
        const invoiceNumber = modal.id.replace('editModal-', '');
        validatePaymentSelectionEdit(invoiceNumber);

        modal.querySelectorAll('.payment-account-checkbox').forEach(cb => {
            cb.addEventListener('change', () => validatePaymentSelectionEdit(invoiceNumber));
        });
    });

    // ==========================================
    // PENANGANAN URL FILTER
    // ==========================================

    const monthSelect = document.getElementById('month-select');
    const yearSelect = document.getElementById('year-select');

    /**
     * Perbarui URL filter bulan/tahun lalu muat ulang halaman.
     *
     * Alur:
     * - Set / delete query param 'month' dan 'year' dari URL saat ini.
     * - Hapus param 'page' agar kembali ke halaman pertama hasil filter.
     */
    function updateInvoiceFilterUrl() {
        const url = new URL(window.location.href);

        if (monthSelect?.value) {
            url.searchParams.set('month', monthSelect.value);
        } else {
            url.searchParams.delete('month');
        }

        if (yearSelect?.value) {
            url.searchParams.set('year', yearSelect.value);
        } else {
            url.searchParams.delete('year');
        }

        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    if (monthSelect) monthSelect.addEventListener('change', updateInvoiceFilterUrl);
    if (yearSelect) yearSelect.addEventListener('change', updateInvoiceFilterUrl);

    // ==========================================
    // RESET STATUS SUBMIT SAAT HALAMAN DITAMPILKAN
    // ==========================================

    window.addEventListener('pageshow', () => resetFormSubmitState());
});
