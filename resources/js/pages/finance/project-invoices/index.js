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
 * Hitung total per baris item (EDIT modal).
 *
 * @param  {HTMLInputElement} input  Element input yang berubah
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function calculateRowTotalEdit(input, invoiceNumber) {
    const row = input.closest('.item-row-edit');
    const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
    const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
    const total = volume * harga;

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
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        grandTotal += (volume * harga);
    });

    const totalPreview = document.getElementById('invoice-total-preview');
    if (totalPreview) {
        totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    // Update terbilang
    const wordsElement = document.getElementById('invoice-total-words');
    if (wordsElement && grandTotal > 0) {
        wordsElement.textContent = 'Terbilang: ' + numberToWords(grandTotal) + ' rupiah';
    } else if (wordsElement) {
        wordsElement.textContent = '';
    }

    // Hitung ulang discount dan DP saat total berubah
    calculateDiscount();
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
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        grandTotal += (volume * harga);
    });

    const totalPreview = document.getElementById('invoice-total-preview-edit-' + invoiceNumber);
    if (totalPreview) {
        totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    calculateDiscountEdit(invoiceNumber);
}

// ==========================================
// PERHITUNGAN DISCOUNT & DP
// ==========================================

/**
 * Hitung discount untuk ADD modal.
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

    // Validasi: jika percentage, batasi maksimal 100
    if (discountError) {
        const isOverLimit = discountType === 'percentage' && discountValue > 100;
        discountError.classList.toggle('hidden', !isOverLimit);
    }

    if (discountType === 'percentage' && discountValue > 100) {
        discountValue = 100;
    }

    // Ambil total dasar
    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        baseTotal += (volume * harga);
    });
    baseTotal = Math.round(baseTotal);

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
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
}

/**
 * Hitung DP untuk ADD modal.
 */
function calculateDP() {
    const dpType = document.getElementById('dp-type')?.value;
    const dpValueInput = document.getElementById('dp-value');
    let dpValue = parseDecimalInput(dpValueInput);
    const dpError = document.getElementById('dp-error');

    // Aktifkan/nonaktifkan berdasarkan tipe
    if (dpValueInput) {
        if (!dpType) {
            dpValueInput.value = '';
            dpValue = 0;
        }
    }

    // Validasi: jika percentage, batasi maksimal 100
    if (dpType === 'percentage' && dpValue > 100) {
        dpValue = 100;
        if (dpValueInput) dpValueInput.value = 100;

        if (dpError) {
            dpError.classList.remove('hidden');
            setTimeout(() => dpError.classList.add('hidden'), 3000);
        }
    } else {
        if (dpError) dpError.classList.add('hidden');
    }

    // Ambil total dasar
    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        baseTotal += (volume * harga);
    });
    baseTotal = Math.round(baseTotal);

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

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);
    const calculationBase = totalAfterDiscount > 0 ? totalAfterDiscount : baseTotal;

    let dpAmount = 0;
    if (dpType && dpValue > 0) {
        dpAmount = dpType === 'percentage'
            ? Math.round((calculationBase * dpValue) / 100)
            : Math.round(dpValue);
    }

    // Perbarui UI
    const dpAmountEl = document.getElementById('dp-amount');
    if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
}

/**
 * Hitung discount untuk EDIT modal.
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

    if (discountType === 'percentage' && discountValue > 100) {
        discountValue = 100;
    }

    const modal = document.getElementById('editModal-' + invoiceNumber);
    let baseTotal = 0;
    if (modal) {
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            baseTotal += (volume * harga);
        });
    }
    baseTotal = Math.round(baseTotal);

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);

    const discountAmountEl = document.getElementById('discount-amount-edit-' + invoiceNumber);
    const totalAfterDiscountEl = document.getElementById('total-after-discount-edit-' + invoiceNumber);

    if (discountAmountEl) discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
    if (totalAfterDiscountEl) totalAfterDiscountEl.textContent = 'Rp ' + totalAfterDiscount.toLocaleString('id-ID');

    const discountError = document.getElementById('discount-error-edit-' + invoiceNumber);
    if (discountError) {
        const isOverLimit = discountType === 'percentage' && discountValue > 100;
        discountError.classList.toggle('hidden', !isOverLimit);
    }

    const hasDiscount = discountType && discountValue > 0;
    const discountSummaryEl = document.getElementById('discount-summary-edit-' + invoiceNumber);
    if (discountSummaryEl) discountSummaryEl.classList.toggle('hidden', !hasDiscount);

    calculateDPEdit(invoiceNumber);
}

/**
 * Hitung DP untuk EDIT modal.
 *
 * @param  {string} invoiceNumber  Nomor invoice untuk identifikasi modal
 */
function calculateDPEdit(invoiceNumber) {
    const dpType = document.getElementById('dp-type-edit-' + invoiceNumber)?.value;
    const dpValueInput = document.getElementById('dp-value-edit-' + invoiceNumber);
    let dpValue = parseDecimalInput(dpValueInput);

    // Aktifkan/nonaktifkan berdasarkan tipe
    if (dpValueInput) {
        if (!dpType) {
            dpValueInput.value = 0;
            dpValue = 0;
        }
    }

    if (dpType === 'percentage' && dpValue > 100) {
        dpValue = 100;
        if (dpValueInput) dpValueInput.value = 100;
    }

    const modal = document.getElementById('editModal-' + invoiceNumber);
    let baseTotal = 0;
    if (modal) {
        modal.querySelectorAll('.item-row-edit').forEach(row => {
            const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
            const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
            baseTotal += (volume * harga);
        });
    }
    baseTotal = Math.round(baseTotal);

    const discountType = document.getElementById('discount-type-edit-' + invoiceNumber)?.value;
    let discountValue = parseDecimalInput(document.getElementById('discount-value-edit-' + invoiceNumber));
    if (discountType === 'percentage') discountValue = Math.min(discountValue, 100);

    let discountAmount = 0;
    if (discountType && discountValue > 0) {
        discountAmount = discountType === 'percentage'
            ? Math.round((baseTotal * discountValue) / 100)
            : Math.round(discountValue);
    }

    const totalAfterDiscount = Math.round(baseTotal - discountAmount);
    const calculationBase = totalAfterDiscount > 0 ? totalAfterDiscount : baseTotal;

    let dpAmount = 0;
    if (dpType && dpValue > 0) {
        dpAmount = dpType === 'percentage'
            ? Math.round((calculationBase * dpValue) / 100)
            : Math.round(dpValue);
    }

    const dpAmountEl = document.getElementById('dp-amount-edit-' + invoiceNumber);
    if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
}

// Ekspos ke window untuk handler inline Blade
window.calculateRowTotal = calculateRowTotal;
window.calculateRowTotalEdit = calculateRowTotalEdit;
window.calculateDiscount = calculateDiscount;
window.calculateDP = calculateDP;
window.calculateDiscountEdit = calculateDiscountEdit;
window.calculateDPEdit = calculateDPEdit;

// ==========================================
// ANGKA KE TEKS (Terbilang)
// ==========================================

/**
 * Konversi angka ke teks Bahasa Indonesia (terbilang).
 *
 * @param  {number} num  Angka yang akan dikonversi
 * @return {string} Teks terbilang
 */
function numberToWords(num) {
    if (num === 0) return 'nol';

    const units = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
    const teens = ['sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
        'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'
    ];
    const tens = ['', '', 'dua puluh', 'tiga puluh', 'empat puluh', 'lima puluh',
        'enam puluh', 'tujuh puluh', 'delapan puluh', 'sembilan puluh'
    ];

    if (num >= 1000000000) {
        return Math.floor(num / 1000000000) + ' miliar ' + numberToWords(num % 1000000000);
    }
    if (num >= 1000000) {
        return Math.floor(num / 1000000) + ' juta ' + numberToWords(num % 1000000);
    }
    if (num >= 1000) {
        const thousands = Math.floor(num / 1000);
        const remainder = num % 1000;
        if (thousands === 1) return 'seribu ' + numberToWords(remainder);
        return numberToWords(thousands) + ' ribu ' + numberToWords(remainder);
    }
    if (num >= 100) {
        const hundreds = Math.floor(num / 100);
        const remainder = num % 100;
        if (hundreds === 1) return 'seratus ' + numberToWords(remainder);
        return units[hundreds] + ' ratus ' + numberToWords(remainder);
    }
    if (num >= 20) {
        return tens[Math.floor(num / 10)] + ' ' + units[num % 10];
    }
    if (num >= 10) {
        return teens[num - 10];
    }
    return units[num];
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

document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // FUNGSIONALITAS CHECKBOX PILIH SEMUA
    // ==========================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    const deleteButton = document.getElementById('delete-button');

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

    const addItemBtn = document.getElementById('add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const itemsContainer = document.getElementById('items-list');
            const newItem = document.createElement('div');
            newItem.className = 'item-row mb-3 p-3 border rounded bg-surface-secondary';
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                        oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
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
                    <input type="text" inputmode="numeric" class="item-harga border rounded p-2 w-full"
                        placeholder="Rp 0" required oninput="formatCurrencyInput(this); calculateRowTotal(this)"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); calculateRowTotal(this); this.setCustomValidity('')">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <button type="button"
                        class="remove-item bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
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

    // ==========================================
    // FUNGSIONALITAS TAMBAH ITEM - MODAL EDIT
    // ==========================================

    document.querySelectorAll('[id^="add-item-edit-"]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const invoiceNumber = this.id.replace('add-item-edit-', '');
            const itemsContainer = document.getElementById('items-list-edit-' + invoiceNumber);
            const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
            const newIndex = currentItems.length;

            const newItem = document.createElement('div');
            newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-surface-secondary';
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" name="items[${newIndex}][keterangan]"
                        class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                        oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" name="items[${newIndex}][volume]"
                        class="item-volume border rounded p-2 w-full" placeholder="Volume *" required
                        oninput="calculateRowTotalEdit(this, '${invoiceNumber}')"
                        oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" name="items[${newIndex}][satuan]"
                        class="item-satuan border rounded p-2 w-full" placeholder="Satuan *" required
                        oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" name="items[${newIndex}][harga]"
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
            alert('Minimal harus ada 1 item dalam invoice');
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

    const addModalElement = document.getElementById('addModal');
    if (addModalElement) {
        const addForm = addModalElement.querySelector('form');
        if (addForm) {
            addForm.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');

                // Serialisasi items
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
                    alert('Minimal harus ada 1 item dalam invoice dengan data lengkap');
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

    document.querySelectorAll('form[action*="proyek-invoice"]').forEach(form => {
        if (form.querySelector('[name="_method"][value="PUT"]')) {
            form.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');

                const editItems = this.querySelectorAll('.item-row-edit');
                if (editItems.length === 0) {
                    e.preventDefault();
                    alert('Minimal harus ada 1 item dalam invoice');
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

    // Inisialisasi tampilan discount & DP untuk semua modal edit
    document.querySelectorAll('[id^="discount-type-edit-"]').forEach(el => {
        const invoiceNumber = el.id.replace('discount-type-edit-', '');
        calculateDiscountEdit(invoiceNumber);
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
