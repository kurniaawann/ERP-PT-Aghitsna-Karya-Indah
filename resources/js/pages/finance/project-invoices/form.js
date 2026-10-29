/**
 * Invoice Proyek - Halaman Form Tambah/Edit JavaScript
 *
 * Pengganti logika modal tambah/edit di index.js lama. Perhitungan (total
 * baris, total invoice, diskon, DP, PPN), No Invoice admin, dan Rekap Proyek
 * disalin apa adanya dari index.js lama sehingga hasilnya sama persis; yang
 * baru hanya pemasangan event di halaman.
 *
 * Modul ini menangani:
 * - Parsing & format input mata uang / desimal berformat Indonesia
 * - Format item sesuai role (data-item-format pada form):
 *   admin → deskripsi, harga, persentase (opsional; kosong → Jumlah = Harga)
 *   lainnya → keterangan, volume & satuan (opsional; volume kosong →
 *   borongan, Jumlah = Harga), harga
 * - Baris item dinamis (tambah dari <template id="item-row-template">, hapus;
 *   minimal 1 baris)
 * - Total per baris, total invoice, diskon & DP (non-admin), PPN live dengan
 *   validasi batas (warning) — ditampilkan di kartu Ringkasan
 * - No Invoice admin: preview {nomor}/AKI/{bulan romawi}/{yyyy} + cek nomor dobel
 * - Tautan Rekap Proyek (opsional): prefill field kosong + ringkasan sisa tagihan
 * - Validasi rekening pembayaran (minimal 1)
 * - Serialisasi item saat submit (input baris tidak ber-name):
 *   tambah → JSON di #items-json (hanya baris lengkap, seperti dulu),
 *   edit → input hidden items[i][field] (harga dinormalisasi jadi angka)
 * - Loading state saat submit (anti double-submit)
 *
 * Referensi backend: app/Services/Finance/ProyekInvoiceService.php
 */

/* global handleFormSubmit, resetFormSubmitState */

// Nilai field diskon/DP saat tipenya dikosongkan: '' (tambah) / 0 (edit), sama seperti modal lama
let emptyTypeValue = '';

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

// Ekspos ke window (sama seperti halaman lama; dipakai juga modal bukti pembayaran)
window.parseCurrencyInput = parseCurrencyInput;
window.formatCurrencyInput = formatCurrencyInput;
window.formatDecimalInput = formatDecimalInput;

// ==========================================
// DETEKSI FORMAT ITEM (ADMIN vs SUPERADMIN)
// ==========================================

/**
 * Apakah form memakai format item admin (deskripsi, harga, persentase)
 * atau format standar superadmin (keterangan, volume, satuan, harga).
 *
 * @return {boolean} true bila format admin
 */
function isAdminItemFormat() {
    return document.getElementById('proyek-invoice-form')?.dataset.itemFormat === 'admin';
}

/**
 * Ambil persentase item format admin (bersifat OPSIONAL).
 *
 * @param  {HTMLElement} row  Elemen baris .item-row
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
 * @param  {HTMLElement} row  Elemen baris .item-row
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
 * @param  {HTMLElement} row  Elemen baris .item-row
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
// PERHITUNGAN LIVE (sama seperti modal tambah lama)
// ==========================================

/**
 * Hitung total per baris item lalu grand total.
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
 * Hitung grand total seluruh item, tampilkan di #invoice-total-preview, lalu
 * hitung ulang diskon, DP & PPN.
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
    updateSummaryExtras();
}

/**
 * Aktifkan / nonaktifkan field diskon, DP & PPN (aktif hanya bila total item > 0).
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

// ==========================================
// PERHITUNGAN DISCOUNT, DP & PPN
// ==========================================

/**
 * Hitung discount (halaman form).
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
            discountValueInput.value = emptyTypeValue;
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
    refreshRecapSummary(document.getElementById('proyek-invoice-form'));
}

/**
 * Hitung DP (halaman form).
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
            dpValueInput.value = emptyTypeValue;
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
 * Hitung PPN (halaman form).
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
 * Tampilan tambahan halaman: jumlah item (Ringkasan) & total di tfoot tabel
 * (sama dengan #invoice-total-preview).
 */
function updateSummaryExtras() {
    const rows = document.querySelectorAll('.item-row');
    const totalText = document.getElementById('invoice-total-preview')?.textContent ?? 'Rp 0';

    document.querySelectorAll('[data-summary="rows"]').forEach(el => { el.textContent = rows.length; });
    document.querySelectorAll('[data-summary="subtotal"]').forEach(el => { el.textContent = totalText; });

    // Minimal 1 baris: tombol hapus nonaktif bila tinggal satu
    rows.forEach(row => {
        const btn = row.querySelector('.remove-row-btn');
        if (btn) btn.disabled = rows.length <= 1;
    });
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
 * Pasang listener untuk field No Invoice admin pada form:
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
 * @param  {HTMLElement} scope  Form yang memuat item & diskon
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
 * Perbarui panel ringkasan Rekap Proyek di dalam form.
 *
 * Alur:
 * - Tanpa rekap terpilih → panel disembunyikan.
 * - Sudah Ditagih = total invoice tertaut (data-billed) dikurangi nilai
 *   invoice ini yang tersimpan bila rekapnya sama (form edit), agar tidak
 *   terhitung dua kali.
 * - Sisa = Nilai Proyek − Sudah Ditagih − Invoice Ini; negatif → peringatan.
 *
 * @param {HTMLElement|null} container  Form tambah / edit
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
 * Pasang listener pilihan Rekap Proyek pada form, lalu tampilkan ringkasan
 * awal (form edit yang sudah tertaut).
 */
function initRecapPickers() {
    document.querySelectorAll('[data-recap-picker]').forEach(picker => {
        const select = picker.querySelector('.recap-select');
        const container = picker.closest('form');
        if (!select) return;

        select.addEventListener('change', () => {
            applyRecapPrefill(picker.closest('form'), select.selectedOptions?.[0]);
            refreshRecapSummary(container);
        });

        refreshRecapSummary(container);
    });
}

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
// DOM READY
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('proyek-invoice-form');
    const tbody = document.getElementById('items-list');
    const template = document.getElementById('item-row-template');
    if (!form || !tbody || !template) return;

    const isEdit = form.dataset.mode === 'edit';
    emptyTypeValue = isEdit ? 0 : '';

    const itemsError = document.getElementById('items-error');
    const paymentError = document.getElementById('payment-account-error');
    const payload = document.getElementById('items-payload');

    // ---------- Baris item ----------

    document.querySelectorAll('[data-add-item-row]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            if (itemsError) itemsError.classList.add('hidden');

            tbody.appendChild(template.content.cloneNode(true));
            updateInvoiceTotal();
            tbody.lastElementChild?.querySelector('input')?.focus();
        });
    });

    tbody.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-row-btn');
        if (!removeBtn || tbody.querySelectorAll('.item-row').length <= 1) return;

        e.preventDefault();
        removeBtn.closest('.item-row').remove();
        updateInvoiceTotal();
    });

    tbody.addEventListener('input', function (e) {
        const input = e.target;
        if (input.classList.contains('item-harga')) {
            formatCurrencyInput(input);
            calculateRowTotal(input);
        } else if (input.classList.contains('item-persentase')) {
            formatDecimalInput(input);
            calculateRowTotal(input);
        } else if (input.classList.contains('item-volume')) {
            calculateRowTotal(input);
        }
    });

    // ---------- Diskon, DP & PPN ----------

    document.getElementById('discount-type')?.addEventListener('change', () => calculateDiscount());
    document.getElementById('discount-value')?.addEventListener('input', function () {
        formatDecimalInput(this);
        calculateDiscount();
    });
    document.getElementById('dp-type')?.addEventListener('change', () => calculateDP());
    document.getElementById('dp-value')?.addEventListener('input', function () {
        formatDecimalInput(this);
        calculateDP();
    });
    document.getElementById('ppn-value')?.addEventListener('input', function () {
        formatDecimalInput(this);
        calculatePPN();
    });

    // ---------- Rekening pembayaran ----------

    /**
     * Minimal 1 rekening dipilih (sama seperti validasi modal lama).
     *
     * @param  {boolean} showError  tampilkan pesan bila belum ada yang dipilih
     * @return {boolean}
     */
    function validatePaymentSelection(showError) {
        const anyChecked = Array.from(form.querySelectorAll('[name="selected_payment_accounts[]"]')).some(cb => cb.checked);
        if (paymentError && (showError || anyChecked)) paymentError.classList.toggle('hidden', anyChecked);
        return anyChecked;
    }

    form.addEventListener('change', function (e) {
        if (e.target.name === 'selected_payment_accounts[]') validatePaymentSelection(false);
    });

    // ---------- Submit ----------

    /**
     * Tampilkan error item & batalkan submit.
     *
     * @param {Event} e
     * @param {string} message
     */
    function rejectItems(e, message) {
        e.preventDefault();
        showItemsError(itemsError, message);
        itemsError?.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }

    form.addEventListener('submit', function (e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const rows = Array.from(tbody.querySelectorAll('.item-row'));

        if (!isEdit) {
            // Tambah: hanya baris lengkap yang dikirim sebagai JSON (seperti modal lama)
            const items = [];
            let hasInvalidPercentage = false;

            rows.forEach(row => {
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
                rejectItems(e, hasInvalidPercentage ? PERCENTAGE_ERROR_MESSAGE : DEFAULT_ITEMS_ERROR_MESSAGE);
                return false;
            }

            document.getElementById('items-json').value = JSON.stringify(items);
        } else {
            // Edit: semua baris dikirim sebagai items[i][field], harga dinormalisasi
            // jadi angka murni (normalizeInvoicePriceFields modal lama)
            if (rows.length === 0) {
                rejectItems(e, DEFAULT_ITEMS_ERROR_MESSAGE);
                return false;
            }

            // Persentase item admin opsional, tapi bila diisi harus > 0 dan ≤ 100
            const hasInvalidPercentage = isAdminItemFormat()
                && rows.some(row => !isValidItemPercentage(getItemPercentage(row)));
            if (hasInvalidPercentage) {
                rejectItems(e, PERCENTAGE_ERROR_MESSAGE);
                return false;
            }

            payload.textContent = '';
            rows.forEach((row, index) => {
                const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
                const fields = isAdminItemFormat()
                    ? {
                        deskripsi: row.querySelector('.item-deskripsi')?.value ?? '',
                        harga: harga ? String(harga) : '0',
                        persentase: row.querySelector('.item-persentase')?.value ?? '',
                    }
                    : {
                        keterangan: row.querySelector('.item-keterangan')?.value ?? '',
                        volume: row.querySelector('.item-volume')?.value ?? '',
                        satuan: row.querySelector('.item-satuan')?.value ?? '',
                        harga: harga ? String(harga) : '0',
                    };

                Object.keys(fields).forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${index}][${key}]`;
                    input.value = fields[key];
                    payload.appendChild(input);
                });
            });
        }

        if (!validatePaymentSelection(true)) {
            e.preventDefault();
            paymentError?.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (typeof window.showToast === 'function') {
                window.showToast('Pilih minimal 1 rekening pembayaran.', 'error');
            }
            return false;
        }

        if (!handleFormSubmit(submitBtn, submitBtn ? submitBtn.innerHTML : '')) {
            e.preventDefault();
            return false;
        }
    });

    // Kembali lewat tombol Back (bfcache) → tombol Simpan aktif lagi
    window.addEventListener('pageshow', function (e) {
        if (e.persisted && typeof resetFormSubmitState === 'function') resetFormSubmitState();
    });

    // ---------- Inisialisasi ----------

    tbody.querySelectorAll('.item-row .item-harga').forEach(input => calculateRowTotal(input));
    updateInvoiceTotal();

    // No Invoice admin (preview nomor lengkap + cek nomor dobel) &
    // pilihan Rekap Proyek (prefill + ringkasan sisa tagihan)
    initAdminNumberFields();
    initRecapPickers();
});
