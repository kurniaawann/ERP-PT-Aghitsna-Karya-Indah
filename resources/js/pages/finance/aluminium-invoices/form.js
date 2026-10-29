/**
 * Invoice Alumunium - Halaman Form Tambah/Edit JavaScript
 *
 * Pengganti logika modal tambah/edit di index.js lama. Perhitungan (total
 * baris, total invoice, diskon, DP) disalin apa adanya dari modal tambah lama
 * sehingga hasilnya sama persis; yang baru hanya pemasangan event di halaman.
 *
 * Modul ini menangani:
 * - Parsing & format input mata uang / desimal berformat Indonesia
 * - Baris item dinamis (tambah dari <template id="item-row-template">, hapus;
 *   minimal 1 baris)
 * - Total per baris (volume × harga), total invoice, diskon & DP live dengan
 *   validasi batas (warning) — ditampilkan di kartu Ringkasan
 * - Validasi rekening pembayaran (minimal 1)
 * - Serialisasi item saat submit (input baris tidak ber-name):
 *   tambah → JSON di #items-json (hanya baris lengkap, seperti dulu),
 *   edit → input hidden items[i][field] (harga dinormalisasi jadi angka)
 * - Loading state saat submit (anti double-submit)
 *
 * Referensi backend: app/Services/Finance/AlumuniumInvoiceService.php
 */

/* global handleFormSubmit, resetFormSubmitState */

// Nilai field diskon/DP saat tipenya dikosongkan: '' (tambah) / 0 (edit), sama seperti modal lama
let emptyTypeValue = '';

// ==========================================
// PARSER KHUSUS ALUMUNIUM
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
 *
 * @param  {HTMLInputElement} inputElement  Element input yang akan dibaca
 * @return {number} Nilai desimal hasil parsing (0 bila kosong / tidak valid)
 */
function parseDecimalInput(inputElement) {
    const rawValue = String(inputElement?.value ?? '').trim();
    if (!rawValue) return 0;
    return parseFloat(rawValue.replace(',', '.')) || 0;
}

/**
 * Format input desimal: hanya menyisakan angka, titik, dan koma (pemisah
 * desimal terakhir dipertahankan, "1.000,5" tetap terbaca 1000,5).
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
// PERHITUNGAN LIVE (sama seperti modal tambah lama)
// ==========================================

/**
 * Hitung total per baris item (volume × harga) lalu grand total.
 *
 * @param  {HTMLInputElement} input  Element input volume/harga yang berubah
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
 * Hitung grand total seluruh item, tampilkan di #invoice-total-preview, lalu
 * hitung ulang diskon & DP (DP selalu dihitung dari sisa setelah diskon).
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
    updateSummaryExtras();
}

/**
 * Aktifkan / nonaktifkan field diskon & DP (aktif hanya bila total item > 0).
 *
 * @param  {boolean} hasTotal  true bila total item > 0
 */
function setAddDependentSections(hasTotal) {
    ['discount-type', 'discount-value', 'dp-type', 'dp-value'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !hasTotal;
    });
    ['discount-section', 'dp-section'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('opacity-40', !hasTotal);
    });
    if (!hasTotal) {
        ['discount-error', 'discount-amount-error', 'dp-error', 'dp-amount-error', 'discount-summary'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        });
    }
}

/**
 * Hitung jumlah diskon dan total setelah diskon.
 *
 * - percentage ≥ 100% → #discount-error, nilai di-cap ke 100.
 * - amount ≥ total → #discount-amount-error, diskon di-cap ke total.
 * - DP dihitung dari SISA SETELAH DISKON → calculateDP().
 *
 * Referensi backend: InvoiceCalculatorService::calculateDiscountAmount().
 */
function calculateDiscount() {
    const discountType = document.getElementById('discount-type')?.value;
    const discountValueInput = document.getElementById('discount-value');
    let discountValue = parseDecimalInput(discountValueInput);
    const discountError = document.getElementById('discount-error');

    if (discountValueInput) {
        if (!discountType) {
            discountValueInput.value = emptyTypeValue;
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
}

/**
 * Hitung jumlah DP dari total SETELAH diskon (bukan total kotor).
 *
 * - percentage ≥ 100% → #dp-error, nilai di-cap ke 100.
 * - amount ≥ dasar perhitungan → #dp-amount-error, DP di-cap.
 *
 * Referensi backend: InvoiceCalculatorService::calculateDpAmount().
 */
function calculateDP() {
    const dpType = document.getElementById('dp-type')?.value;
    const dpValueInput = document.getElementById('dp-value');
    let dpValue = parseDecimalInput(dpValueInput);
    const dpError = document.getElementById('dp-error');
    const dpAmountError = document.getElementById('dp-amount-error');

    if (dpValueInput) {
        if (!dpType) {
            dpValueInput.value = emptyTypeValue;
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
// DOM READY
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('aluminium-invoice-form');
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
            tbody.lastElementChild?.querySelector('.item-keterangan')?.focus();
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
        } else if (input.classList.contains('item-volume')) {
            calculateRowTotal(input);
        }
    });

    // ---------- Diskon & DP ----------

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

    form.addEventListener('submit', function (e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const rows = Array.from(tbody.querySelectorAll('.item-row'));

        if (!isEdit) {
            // Tambah: hanya baris lengkap yang dikirim sebagai JSON (seperti modal lama)
            const items = [];
            rows.forEach(row => {
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
                itemsError?.classList.remove('hidden');
                itemsError?.scrollIntoView({ block: 'center', behavior: 'smooth' });
                return false;
            }

            document.getElementById('items-json').value = JSON.stringify(items);
        } else {
            // Edit: semua baris dikirim sebagai items[i][field], harga dinormalisasi
            // jadi angka murni (normalizeInvoicePriceFields modal lama)
            if (rows.length === 0) {
                e.preventDefault();
                itemsError?.classList.remove('hidden');
                return false;
            }

            payload.textContent = '';
            rows.forEach((row, index) => {
                const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
                const fields = {
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

    tbody.querySelectorAll('.item-row .item-volume').forEach(input => calculateRowTotal(input));
    updateInvoiceTotal();
});
