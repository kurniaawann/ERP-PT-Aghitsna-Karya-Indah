/**
 * Penawaran Proyek (Project Quotation) — JavaScript Halaman Index
 *
 * Modul ini menangani seluruh logika front-end halaman penawaran proyek.
 * Diadaptasi dari modul Penawaran Aluminium karena format item dan discount
 * kini identik:
 * - Parsing & format input mata uang / desimal berformat Indonesia
 * - Perhitungan live total per baris item dan grand total (mode tambah & edit)
 * - Perhitungan diskon & DP secara live dengan validasi batas (warning)
 * - Perhitungan PPN secara live (opsional, dihitung dari total setelah diskon)
 * - Auto-generate nomor penawaran saat modal tambah dibuka (AJAX)
 * - Validasi pemilihan rekening pembayaran (submit dinonaktifkan bila kosong)
 * - Tambah / hapus item row secara dinamis + re-index field items
 * - Submit form dengan serialisasi item ke JSON + proteksi submit ganda
 * - Checkbox select all untuk hapus massal
 *
 * Referensi backend: app/Services/Administrasi/ProjectQuotationService.php
 */

/* global handleFormSubmit, resetFormSubmitState */

// ==========================================
// PARSER KHUSUS PROYEK
// ==========================================

/**
 * Parsing input mata uang sesuai format Indonesia:
 * - "1.000" => 1000 (titik sebagai pemisah ribuan)
 * - "1,5" => 1.5 (koma sebagai pemisah desimal)
 * - "Rp 1.000" => 1000
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
 */
function formatCurrencyInput(input) {
    const str = String(input.value ?? '').trim();
    if (!str) return;

    const num = parseCurrencyInput(str);
    input.value = num ? Math.round(num).toLocaleString('id-ID') : '';
}

/**
 * Parsing nilai input desimal dengan dukungan koma sebagai pemisah desimal.
 */
function parseDecimalInput(inputElement) {
    const rawValue = String(inputElement?.value ?? '').trim();
    if (!rawValue) return 0;
    return parseFloat(rawValue.replace(',', '.')) || 0;
}

/**
 * Format input desimal: hanya menyisakan angka, titik, dan koma.
 * Pertahankan hanya pemisah desimal TERAKHIR agar nilai seperti
 * "1.000,5" tetap terbaca sebagai 1000,5.
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
 * Normalisasi semua field harga pada form menjadi string numerik murni.
 * Dipakai saat submit mode edit agar harga berformat "1.000" tidak terkirim
 * mentah ke server.
 */
function normalizeInvoicePriceFields(form) {
    form.querySelectorAll('input[name*="[harga]"]').forEach(input => {
        const value = parseCurrencyInput(input.value);
        input.value = value ? String(value) : '0';
    });
}

// Ekspos ke window untuk handler inline Blade
window.parseCurrencyInput = parseCurrencyInput;
window.formatCurrencyInput = formatCurrencyInput;
window.formatDecimalInput = formatDecimalInput;

// ==========================================
// FUNGSI PERHITUNGAN LIVE
// ==========================================

/**
 * Hitung total per baris item pada modal ADD (volume × harga).
 */
function calculateRowTotal(input) {
    const row = input.closest('.item-row');
    const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
    const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
    const total = volume * harga;

    const totalSpan = row.querySelector('.item-total');
    if (totalSpan) {
        totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    updateInvoiceTotal();
}

/**
 * Hitung total per baris item pada modal EDIT (volume × harga).
 */
function calculateEditRowTotal(input) {
    const row = input.closest('.item-row-edit');
    const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
    const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
    const total = volume * harga;

    const totalSpan = row.querySelector('.item-total');
    if (totalSpan) {
        totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    updateEditInvoiceTotal(input);
}

window.calculateRowTotal = calculateRowTotal;
window.calculateEditRowTotal = calculateEditRowTotal;

/**
 * Hitung grand total seluruh item pada modal ADD.
 * Menampilkan hasil pada #invoice-total-preview lalu memanggil
 * calculateDiscount() agar diskon ikut dihitung ulang.
 */
function updateInvoiceTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        grandTotal += (volume * harga);
    });

    const totalPreview = document.getElementById('invoice-total-preview');
    if (totalPreview) {
        totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    calculateDiscount();
    calculatePPN();
}

/**
 * Hitung grand total seluruh item pada modal EDIT.
 * Beroperasi per modal (id^="editModal-") dan memanggil calculateDiscountEdit().
 */
function updateEditInvoiceTotal(input) {
    const modal = input.closest('[id^="editModal-"]');
    if (!modal) return;

    const quotationId = modal.id.replace('editModal-', '');
    let grandTotal = 0;

    modal.querySelectorAll('.item-row-edit').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        grandTotal += (volume * harga);
    });

    const totalPreview = document.getElementById('invoice-total-preview-edit-' + quotationId);
    if (totalPreview) {
        totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    calculateDiscountEdit(quotationId);
    calculatePPNEdit(quotationId);
}

/**
 * Aktifkan / nonaktifkan section & field diskon pada modal ADD.
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
        ['discount-error', 'discount-amount-error', 'discount-summary', 'dp-error', 'dp-amount-error', 'ppn-error', 'ppn-summary'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
    }
}

/**
 * Aktifkan / nonaktifkan section & field diskon pada modal EDIT.
 */
function setEditDependentSections(quotationNumber, hasTotal) {
    ['discount-type-edit-', 'discount-value-edit-', 'dp-type-edit-', 'dp-value-edit-', 'ppn-value-edit-'].forEach(prefix => {
        const el = document.getElementById(prefix + quotationNumber);
        if (el) el.disabled = !hasTotal;
    });
    ['discount-section-edit-', 'dp-section-edit-', 'ppn-section-edit-'].forEach(prefix => {
        const el = document.getElementById(prefix + quotationNumber);
        if (el) el.classList.toggle('opacity-40', !hasTotal);
    });
    if (!hasTotal) {
        ['discount-error-edit-', 'discount-amount-error-edit-', 'discount-summary-edit-', 'dp-error-edit-', 'dp-amount-error-edit-', 'ppn-error-edit-', 'ppn-summary-edit-'].forEach(prefix => {
            const el = document.getElementById(prefix + quotationNumber);
            if (el) el.classList.add('hidden');
        });
    }
}

// ==========================================
// DISCOUNT CALCULATIONS
// ==========================================

/**
 * Hitung jumlah diskon dan total setelah diskon pada modal ADD.
 * Guard: percentage >= 100% -> warning + cap; amount >= total -> warning.
 */
function calculateDiscount() {
    const discountType = document.getElementById('discount-type')?.value;
    const discountValueInput = document.getElementById('discount-value');
    let discountValue = parseDecimalInput(discountValueInput);
    const discountError = document.getElementById('discount-error');

    if (discountValueInput) {
        if (!discountType) {
            discountValueInput.value = '';
            discountValue = 0;
        }
    }

    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        baseTotal += (volume * harga);
    });
    baseTotal = Math.round(baseTotal);

    setAddDependentSections(baseTotal > 0);

    const isOverLimitPercent = discountType === 'percentage' && discountValue >= 100;
    if (discountError) discountError.classList.toggle('hidden', !isOverLimitPercent);

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

    const discountAmountEl = document.getElementById('discount-amount');
    const totalAfterDiscountEl = document.getElementById('total-after-discount');
    if (discountAmountEl) discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
    if (totalAfterDiscountEl) totalAfterDiscountEl.textContent = 'Rp ' + totalAfterDiscount.toLocaleString('id-ID');

    const hasDiscount = discountType && discountValue > 0;
    const discountSummaryEl = document.getElementById('discount-summary');
    if (discountSummaryEl) discountSummaryEl.classList.toggle('hidden', !hasDiscount);

    calculateDP();
    calculatePPN();
}

window.calculateDiscount = calculateDiscount;

/**
 * Hitung jumlah DP / uang muka pada modal ADD.
 *
 * Alur:
 * - baseTotal = Σ(volume × harga) dari semua baris item.
 * - Hitung ulang discountAmount & totalAfterDiscount (identik dengan
 *   calculateDiscount) sebagai dasar perhitungan DP.
 * - calculationBase = totalAfterDiscount bila ada, else baseTotal.
 * - DP amount = percentage ? round(calculationBase × nilai / 100) : nilai,
 *   dengan guard: percentage ≥ 100% → warning + cap; amount ≥ base → warning.
 *
 * Catatan: DP dihitung dari total SETELAH diskon, bukan total kotor.
 *
 * Referensi backend: ProjectQuotation::getDpAmount().
 */
function calculateDP() {
    const dpType = document.getElementById('dp-type')?.value;
    const dpValueInput = document.getElementById('dp-value');
    let dpValue = parseDecimalInput(dpValueInput);
    const dpError = document.getElementById('dp-error');
    const dpAmountError = document.getElementById('dp-amount-error');

    if (dpValueInput) {
        if (!dpType) {
            dpValueInput.value = '';
            dpValue = 0;
        }
    }

    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        baseTotal += (volume * harga);
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
    const calculationBase = totalAfterDiscount > 0 ? totalAfterDiscount : baseTotal;

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

    const dpAmountEl = document.getElementById('dp-amount');
    if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
}

window.calculateDP = calculateDP;

/**
 * Hitung jumlah PPN dan total setelah PPN pada modal ADD.
 *
 * Alur:
 * - baseTotal = Σ(volume × harga) dari semua baris item.
 * - Hitung ulang discountAmount & totalAfterDiscount (identik dengan
 *   calculateDiscount) sebagai dasar pengenaan PPN.
 * - PPN amount = round(totalAfterDiscount × ppn / 100).
 * - Guard PPN ≥ 100% → tampilkan #ppn-error, nilai di-cap ke 100.
 * - Tampilkan summary PPN & total setelah PPN.
 *
 * Catatan: penawaran TIDAK memiliki DP — PPN dihitung langsung dari total
 * setelah diskon.
 *
 * Referensi backend: ProjectQuotation::getPpnAmount().
 */
function calculatePPN() {
    const ppnInput = document.getElementById('ppn-value');
    let ppn = parseDecimalInput(ppnInput);
    const ppnError = document.getElementById('ppn-error');

    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        baseTotal += (volume * harga);
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

window.calculatePPN = calculatePPN;

/**
 * Hitung jumlah diskon dan total setelah diskon pada modal EDIT.
 * Seluruh elemen memakai suffix "-{quotationNumber}".
 */
function calculateDiscountEdit(quotationNumber) {
    const typeEl = document.getElementById('discount-type-edit-' + quotationNumber);
    const valueEl = document.getElementById('discount-value-edit-' + quotationNumber);
    const discountType = typeEl?.value;
    let discountValue = parseDecimalInput(valueEl);

    if (valueEl) {
        if (!discountType) {
            valueEl.value = 0;
            discountValue = 0;
        }
    }

    const discountError = document.getElementById('discount-error-edit-' + quotationNumber);
    if (discountError) {
        const isOverLimit = discountType === 'percentage' && discountValue >= 100;
        discountError.classList.toggle('hidden', !isOverLimit);
    }

    const modal = document.getElementById('editModal-' + quotationNumber);
    let baseTotal = 0;
    if (modal) {
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            baseTotal += (volume * harga);
        });
    }
    baseTotal = Math.round(baseTotal);

    setEditDependentSections(quotationNumber, baseTotal > 0);

    const isOverLimitAmount = discountType === 'amount'
        && discountValue > 0
        && baseTotal > 0
        && discountValue >= baseTotal;
    const discountAmountError = document.getElementById('discount-amount-error-edit-' + quotationNumber);
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

    const discountAmountEl = document.getElementById('discount-amount-edit-' + quotationNumber);
    const totalAfterDiscountEl = document.getElementById('total-after-discount-edit-' + quotationNumber);
    if (discountAmountEl) discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
    if (totalAfterDiscountEl) totalAfterDiscountEl.textContent = 'Rp ' + totalAfterDiscount.toLocaleString('id-ID');

    const hasDiscount = discountType && discountValue > 0;
    const discountSummaryEl = document.getElementById('discount-summary-edit-' + quotationNumber);
    if (discountSummaryEl) discountSummaryEl.classList.toggle('hidden', !hasDiscount);

    calculateDPEdit(quotationNumber);
    calculatePPNEdit(quotationNumber);
}

window.calculateDiscountEdit = calculateDiscountEdit;

/**
 * Hitung jumlah DP / uang muka pada modal EDIT.
 *
 * Versi edit dari calculateDP() dengan suffix "-{quotationNumber}" pada
 * seluruh id elemen. DP dihitung dari total SETELAH diskon.
 *
 * @param {string} quotationNumber Nomor penawaran untuk identifikasi modal
 */
function calculateDPEdit(quotationNumber) {
    const dpType = document.getElementById('dp-type-edit-' + quotationNumber)?.value;
    const dpValueInput = document.getElementById('dp-value-edit-' + quotationNumber);
    let dpValue = parseDecimalInput(dpValueInput);
    const dpError = document.getElementById('dp-error-edit-' + quotationNumber);
    const dpAmountError = document.getElementById('dp-amount-error-edit-' + quotationNumber);

    if (dpValueInput) {
        if (!dpType) {
            dpValueInput.value = 0;
            dpValue = 0;
        }
    }

    const modal = document.getElementById('editModal-' + quotationNumber);
    let baseTotal = 0;
    if (modal) {
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            baseTotal += (volume * harga);
        });
    }
    baseTotal = Math.round(baseTotal);

    setEditDependentSections(quotationNumber, baseTotal > 0);

    const discountType = document.getElementById('discount-type-edit-' + quotationNumber)?.value;
    let discountValue = parseDecimalInput(document.getElementById('discount-value-edit-' + quotationNumber));
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

    const dpAmountEl = document.getElementById('dp-amount-edit-' + quotationNumber);
    if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
}

window.calculateDPEdit = calculateDPEdit;

/**
 * Hitung jumlah PPN dan total setelah PPN pada modal EDIT.
 *
 * Versi edit dari calculatePPN() dengan suffix "-{quotationNumber}" pada
 * seluruh id elemen. PPN dihitung dari total setelah diskon.
 *
 * @param {string} quotationNumber Nomor penawaran untuk identifikasi modal
 */
function calculatePPNEdit(quotationNumber) {
    const ppnInput = document.getElementById('ppn-value-edit-' + quotationNumber);
    let ppn = parseDecimalInput(ppnInput);
    const ppnError = document.getElementById('ppn-error-edit-' + quotationNumber);

    const modal = document.getElementById('editModal-' + quotationNumber);
    let baseTotal = 0;
    if (modal) {
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            baseTotal += (volume * harga);
        });
    }
    baseTotal = Math.round(baseTotal);

    setEditDependentSections(quotationNumber, baseTotal > 0);

    const discountType = document.getElementById('discount-type-edit-' + quotationNumber)?.value;
    let discountValue = parseDecimalInput(document.getElementById('discount-value-edit-' + quotationNumber));
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

    const ppnAmountEl = document.getElementById('ppn-amount-edit-' + quotationNumber);
    const totalAfterPpnEl = document.getElementById('total-after-ppn-edit-' + quotationNumber);
    if (ppnAmountEl) ppnAmountEl.textContent = 'Rp ' + ppnAmount.toLocaleString('id-ID');
    if (totalAfterPpnEl) totalAfterPpnEl.textContent = 'Rp ' + totalAfterPpn.toLocaleString('id-ID');

    const ppnSummaryEl = document.getElementById('ppn-summary-edit-' + quotationNumber);
    if (ppnSummaryEl) ppnSummaryEl.classList.toggle('hidden', !(ppn > 0));
}

window.calculatePPNEdit = calculatePPNEdit;

// ==========================================
// PAYMENT ACCOUNT VALIDATION
// ==========================================

/**
 * Validasi pemilihan rekening pembayaran pada modal ADD.
 * Nonaktifkan tombol submit bila tidak ada rekening yang dipilih.
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
 * Validasi pemilihan rekening pembayaran pada modal EDIT (per penawaran).
 */
function validatePaymentSelectionEdit(quotationNumber) {
    const modal = document.getElementById('editModal-' + quotationNumber);
    const checkboxes = modal?.querySelectorAll('.payment-account-checkbox-edit') ?? [];
    const submitBtn = document.getElementById('submit-btn-editModal-' + quotationNumber);

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
// BULK DELETE
// ==========================================

/**
 * Submit form hapus massal dengan indikator loading.
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
// AUTO-GENERATE NOMOR PENAWARAN
// ==========================================

/**
 * Memperbarui nomor penawaran otomatis di modal tambah.
 * Mengambil nomor berikutnya via AJAX dari controller.
 */
function fetchNextQuotationNumber() {
    const getNextNumberUrl = document.querySelector('meta[name="project-quotation-get-next-number"]')?.content;
    const displayEl = document.getElementById('addQuotationNumberDisplay');

    if (getNextNumberUrl && displayEl) {
        fetch(getNextNumberUrl)
            .then(r => r.json())
            .then(data => {
                if (data.quotation_number) {
                    displayEl.textContent = data.quotation_number;
                }
            });
    }
}

window.fetchNextQuotationNumber = fetchNextQuotationNumber;

// ==========================================
// DOM READY
// ==========================================

/**
 * Inisialisasi seluruh fungsionalitas halaman setelah DOM siap.
 */
document.addEventListener('DOMContentLoaded', function () {
    // SELECT ALL CHECKBOX
    const selectAllCheckbox = document.getElementById('selectAll');
    const quotationCheckboxes = document.querySelectorAll('input[name="ids[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(quotationCheckboxes).some(cb => cb.checked);
        if (deleteButton) deleteButton.disabled = !anyChecked;
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            quotationCheckboxes.forEach(checkbox => checkbox.checked = this.checked);
            updateDeleteButtonState();
        });
    }

    quotationCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                selectAllCheckbox.checked = Array.from(quotationCheckboxes).every(cb => cb.checked);
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // AUTO-GENERATE NOMOR PENAWARAN saat addModal dibuka
    const addModal = document.getElementById('addModal');
    if (addModal) {
        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                if (m.target.id === 'addModal' && !m.target.classList.contains('hidden')) {
                    fetchNextQuotationNumber();
                }
            });
        });
        observer.observe(addModal, { attributes: true, attributeFilter: ['class'] });
    }

    // ADD MODAL - ADD ITEM
    const addItemBtn = document.getElementById('add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const itemsError = document.getElementById('items-error');
            if (itemsError) itemsError.classList.add('hidden');
            const itemsContainer = document.getElementById('items-list');
            const newItem = document.createElement('div');
            newItem.className = 'item-row mb-3 p-3 border rounded bg-gray-50';
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                        oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" class="item-volume border rounded p-2 w-full" placeholder="Volume *" required oninput="calculateRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" class="item-satuan border rounded p-2 w-full" placeholder="Satuan (m3, unit) *" required
                        oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" min="0" class="item-harga border rounded p-2 w-full" placeholder="Harga *" required oninput="formatCurrencyInput(this); calculateRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <button type="button" class="remove-item bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            itemsContainer.appendChild(newItem);
            attachRemoveListener();
            updateInvoiceTotal();
        });
    }

    function attachRemoveListener() {
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.removeEventListener('click', removeItemClickHandler);
            btn.addEventListener('click', removeItemClickHandler);
        });
    }

    function removeItemClickHandler(e) {
        e.preventDefault();
        this.closest('.item-row').remove();
        updateInvoiceTotal();
    }

    attachRemoveListener();

    // Format existing harga inputs in edit modals
    document.querySelectorAll('[id^="editModal-"] .item-harga').forEach(input => {
        if (input.value) formatCurrencyInput(input);
    });

    // EDIT MODAL - ADD ITEM
    document.querySelectorAll('.add-item-edit').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const container = this.closest('[id^="items-container-edit-"]');
            const errorDiv = container ? container.querySelector('.items-error-edit') : null;
            if (errorDiv) errorDiv.classList.add('hidden');
            const quotationId = this.getAttribute('data-quotation-id');
            const itemsContainer = document.getElementById('items-list-edit-' + quotationId);
            const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
            const newIndex = currentItems.length;

            const newItem = document.createElement('div');
            newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-gray-50';
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" name="items[${newIndex}][keterangan]"
                        class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                        oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" name="items[${newIndex}][volume]"
                        class="item-volume border rounded p-2 w-full" placeholder="Volume *" required oninput="calculateEditRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" name="items[${newIndex}][satuan]"
                        class="item-satuan border rounded p-2 w-full" placeholder="Satuan *" required
                        oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" min="0" name="items[${newIndex}][harga]"
                        class="item-harga border rounded p-2 w-full" placeholder="Harga *" required oninput="formatCurrencyInput(this); calculateEditRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <button type="button" class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            itemsContainer.appendChild(newItem);
            attachRemoveListenerEdit();
        });
    });

    function attachRemoveListenerEdit() {
        document.querySelectorAll('.remove-item-edit').forEach(btn => {
            btn.removeEventListener('click', removeItemEditClickHandler);
            btn.addEventListener('click', removeItemEditClickHandler);
        });
    }

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

        // Re-index items
        itemsContainer.querySelectorAll('.item-row-edit').forEach((row, index) => {
            row.querySelectorAll('input[name^="items"]').forEach(input => {
                const fieldName = input.name.match(/\[(\w+)\]$/)[1];
                input.name = `items[${index}][${fieldName}]`;
            });
        });

        const firstInput = itemsContainer.querySelector('.item-volume');
        if (firstInput) updateEditInvoiceTotal(firstInput);
    }

    attachRemoveListenerEdit();

    // FORM SUBMIT - ADD MODAL
    const addModalElement = document.getElementById('addModal');
    if (addModalElement) {
        const addForm = addModalElement.querySelector('form');
        if (addForm) {
            addForm.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');

                const items = [];
                const itemRows = this.querySelectorAll('.item-row');

                itemRows.forEach(row => {
                    const keterangan = row.querySelector('.item-keterangan')?.value || '';
                    const volumeInput = row.querySelector('.item-volume');
                    const satuanInput = row.querySelector('.item-satuan');
                    const hargaInput = row.querySelector('.item-harga');

                    const volume = volumeInput ? parseFloat(volumeInput.value) : 0;
                    const satuan = satuanInput ? satuanInput.value : '';
                    const harga = hargaInput ? parseCurrencyInput(hargaInput.value) : 0;

                    if (keterangan && !isNaN(volume) && volume > 0 && satuan && !isNaN(harga) && harga > 0) {
                        items.push({ keterangan, volume, satuan, harga });
                    }
                });

                if (items.length === 0) {
                    e.preventDefault();
                    const itemsError = this.querySelector('#items-error');
                    if (itemsError) itemsError.classList.remove('hidden');
                    return false;
                }

                const itemsJsonField = this.querySelector('#items-json');
                if (!itemsJsonField) {
                    e.preventDefault();
                    alert('Error: Field items tidak ditemukan');
                    return false;
                }

                itemsJsonField.value = JSON.stringify(items);

                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }

                return true;
            });
        }
    }

    // FORM SUBMIT - EDIT MODALS
    document.querySelectorAll('form[action*="project-quotation"]').forEach(form => {
        if (form.querySelector('[name="_method"][value="PUT"]')) {
            form.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');

                const editItems = this.querySelectorAll('.item-row-edit');
                if (editItems.length === 0) {
                    e.preventDefault();
                    const errorDiv = this.querySelector('.items-error-edit');
                    if (errorDiv) errorDiv.classList.remove('hidden');
                    return false;
                }

                normalizeInvoicePriceFields(this);

                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });

    // INITIALIZE TOTALS
    updateInvoiceTotal();

    document.querySelectorAll('[id^="discount-type-edit-"]').forEach(el => {
        const quotationNumber = el.id.replace('discount-type-edit-', '');
        calculateDiscountEdit(quotationNumber);
    });

    // PAYMENT ACCOUNT BUTTON STATES
    validatePaymentSelection();

    document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
        const quotationNumber = modal.id.replace('editModal-', '');
        validatePaymentSelectionEdit(quotationNumber);

        modal.querySelectorAll('.payment-account-checkbox-edit').forEach(cb => {
            cb.addEventListener('change', () => validatePaymentSelectionEdit(quotationNumber));
        });
    });

    // RESET SUBMIT STATE ON PAGE SHOW
    window.addEventListener('pageshow', () => resetFormSubmitState());
});
