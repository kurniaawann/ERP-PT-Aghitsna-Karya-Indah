/**
 * Modul Bukti Pembayaran - Logika Frontend
 *
 * Menangani interaksi UI untuk modul Bukti Pembayaran:
 * - Lazy loading opsi invoice (berbasis chunk)
 * - Bagian nominal dinamis & perhitungan tahap pembayaran
 * - Validasi nominal (tidak melebihi sisa tagihan)
 * - Binding form untuk modal create & edit
 * - Checkbox pilih semua / hapus massal
 *
 * Dependensi (dari modul shared):
 * - handleFormSubmit, resetFormSubmitState (form-submit.js)
 * - parseCurrencyInput, formatRupiah (currency.js)
 * - openModal, closeModal (layout/app.blade.php global)
 */
/* global handleFormSubmit, parseCurrencyInput, formatRupiah */

const PAYMENT_PROOF_INVOICE_CHUNK_SIZE = 10;

// ─── Helper Config ──────────────────────────────────────────────────────

/**
 * Mengambil referensi DOM elements berdasarkan prefix (create atau edit-{id}).
 *
 * @param  {string} prefix  'create' atau 'edit-{id}'
 * @return {Object}  Config object berisi DOM element references
 */
function getPaymentProofConfig(prefix) {
    return {
        module: document.getElementById(`payment-proof-module-${prefix}`),
        invoiceType: document.getElementById(`payment-proof-invoice-type-${prefix}`),
        invoiceNumber: document.getElementById(`payment-proof-invoice-number-${prefix}`),
        stageText: document.getElementById(`payment-proof-stage-${prefix}`),
        stageInput: document.getElementById(`payment-proof-stage-input-${prefix}`),
        stageWrap: document.getElementById(`payment-proof-stage-wrap-${prefix}`),
        amountWrap: document.getElementById(`payment-proof-amount-wrap-${prefix}`),
        amountInput: document.getElementById(`payment-proof-amount-${prefix}`),
        amountHelp: document.getElementById(`payment-proof-amount-help-${prefix}`),
        amountWarning: document.getElementById(`payment-proof-amount-warning-${prefix}`),
    };
}

/**
 * Mengambil data invoice dari window.paymentProofInvoiceData berdasarkan module & type.
 *
 * @param  {string} prefix
 * @return {Array}  Array of invoice option objects
 */
function getPaymentProofInvoiceData(prefix) {
    const config = getPaymentProofConfig(prefix);
    if (!config.module || !config.invoiceType || !config.invoiceNumber) return [];

    const moduleValue = config.module.value;
    const invoiceTypeValue = config.invoiceType.value;

    return window.paymentProofInvoiceData?.[moduleValue]?.[invoiceTypeValue] ?? [];
}

// ─── Pemuatan Opsi Invoice ──────────────────────────────────────────────

/**
 * Menambahkan opsi invoice ke dropdown secara bertahap (lazy loading).
 *
 * @param  {string}  prefix
 * @param  {number}  count  Jumlah item per chunk (default: 10)
 * @return {void}
 */
function appendPaymentProofInvoiceOptions(prefix, count = PAYMENT_PROOF_INVOICE_CHUNK_SIZE) {
    const config = getPaymentProofConfig(prefix);
    const invoiceData = getPaymentProofInvoiceData(prefix);

    if (!config.invoiceNumber || invoiceData.length === 0) {
        return;
    }

    const loadedCount = Number(config.invoiceNumber.dataset.loadedCount || 0);
    const nextItems = invoiceData.slice(loadedCount, loadedCount + count);

    nextItems.forEach(item => {
        const option = document.createElement('option');
        option.value = item.value;

        const optionSuffix = item.is_fully_paid ? ' (Lunas)' : '';
        option.textContent = `${item.label}${optionSuffix}`;
        option.dataset.nextStage = item.next_stage || '';
        option.dataset.remainingAmount = item.remaining_amount || 0;
        option.dataset.netAmount = item.net_amount || 0;
        option.dataset.paidAmount = item.paid_amount || 0;
        option.dataset.isFullyPaid = item.is_fully_paid ? '1' : '0';

        if (item.is_fully_paid) {
            option.disabled = true;
        }

        config.invoiceNumber.appendChild(option);
    });

    config.invoiceNumber.dataset.loadedCount = String(loadedCount + nextItems.length);
}

/**
 * Memuat opsi invoice ke dropdown dan mengikat event scroll untuk lazy loading.
 *
 * @param  {string}      prefix
 * @param  {string|null} selectedInvoiceNumber  Invoice yang sudah dipilih (untuk edit mode)
 * @return {void}
 */
function loadPaymentProofInvoices(prefix, selectedInvoiceNumber = null) {
    const config = getPaymentProofConfig(prefix);
    const invoiceData = getPaymentProofInvoiceData(prefix);

    if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

    config.invoiceNumber.innerHTML = '<option value="">Pilih invoice</option>';
    config.invoiceNumber.disabled = invoiceData.length === 0;
    config.invoiceNumber.dataset.loadedCount = '0';

    appendPaymentProofInvoiceOptions(prefix);

    if (selectedInvoiceNumber) {
        while (
            Number(config.invoiceNumber.dataset.loadedCount || 0) < invoiceData.length &&
            !Array.from(config.invoiceNumber.options).some(option => option.value === selectedInvoiceNumber)
        ) {
            appendPaymentProofInvoiceOptions(prefix);
        }

        config.invoiceNumber.value = selectedInvoiceNumber;
    }

    if (config.stageWrap) {
        config.stageWrap.classList.toggle('hidden', config.invoiceType.value !== 'proyek');
    }

    updatePaymentProofStage(prefix);

    if (config.invoiceNumber.__paymentProofScrollBound !== true) {
        config.invoiceNumber.addEventListener('scroll', () => {
            const currentInvoiceData = getPaymentProofInvoiceData(prefix);
            const remainingSpace = config.invoiceNumber.scrollHeight - config.invoiceNumber.scrollTop -
                config.invoiceNumber.clientHeight;

            if (remainingSpace <= 4) {
                const loadedCount = Number(config.invoiceNumber.dataset.loadedCount || 0);

                if (loadedCount < currentInvoiceData.length) {
                    appendPaymentProofInvoiceOptions(prefix);
                }
            }
        });

        config.invoiceNumber.__paymentProofScrollBound = true;
    }
}

/**
 * Wrapper untuk loadPaymentProofInvoices (untuk dipanggil saat module/type berubah).
 *
 * @param  {string}      prefix
 * @param  {string|null} selectedInvoiceNumber
 * @return {void}
 */
function updatePaymentProofInvoices(prefix, selectedInvoiceNumber = null) {
    const config = getPaymentProofConfig(prefix);
    if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

    loadPaymentProofInvoices(prefix, selectedInvoiceNumber);
}

// ─── Perhitungan Tahap & Nominal ──────────────────────────────────────────

/**
 * Memperbarui tampilan tahap pembayaran berdasarkan invoice yang dipilih.
 *
 * @param  {string} prefix
 * @return {void}
 */
function updatePaymentProofStage(prefix) {
    const config = getPaymentProofConfig(prefix);
    if (!config.invoiceNumber || !config.stageText || !config.stageInput) return;

    const selectedOption = config.invoiceNumber.options[config.invoiceNumber.selectedIndex];
    const nextStage = selectedOption?.dataset?.nextStage;

    if (config.stageWrap) {
        config.stageWrap.classList.toggle('hidden', config.invoiceType.value !== 'proyek');
    }

    if (config.invoiceType.value !== 'proyek') {
        config.stageText.textContent = 'Tidak ada tahap pembayaran';
        config.stageInput.value = '';
    } else if (nextStage) {
        config.stageText.textContent = `Pembayaran ke ${nextStage}`;
        config.stageInput.value = nextStage;
    } else {
        config.stageText.textContent = '-';
        config.stageInput.value = '';
    }

    updatePaymentProofAmountSection(prefix);
}

/**
 * Memperbarui tampilan section nominal pembayaran.
 *
 * Untuk invoice proyek: nominal bisa diubah manual.
 * Untuk tipe lain: nominal mengikuti sisa tagihan (readonly).
 *
 * @param  {string} prefix
 * @return {void}
 */
function updatePaymentProofAmountSection(prefix) {
    const config = getPaymentProofConfig(prefix);

    if (!config.invoiceNumber || !config.amountInput || !config.amountHelp || !config.amountWrap) {
        return;
    }

    const selectedOption = config.invoiceNumber.options[config.invoiceNumber.selectedIndex];
    const remainingAmount = Number(selectedOption?.dataset?.remainingAmount || 0);

    if (config.invoiceType.value !== 'proyek') {
        config.amountWrap.classList.add('hidden');
        config.amountInput.disabled = true;
        config.amountInput.required = false;
        config.amountInput.value = selectedOption?.value ? formatRupiah(remainingAmount) : '';
        config.amountHelp.textContent = selectedOption?.value ?
            `Nominal mengikuti sisa tagihan ${formatRupiah(remainingAmount)}.` :
            'Pilih invoice terlebih dahulu agar nominal otomatis terisi.';
        return;
    }

    config.amountWrap.classList.remove('hidden');
    config.amountInput.disabled = false;
    config.amountInput.required = true;

    if (!selectedOption || !selectedOption.value) {
        config.amountHelp.textContent = 'Pilih invoice terlebih dahulu agar sisa tagihan tampil di sini.';
        return;
    }

    config.amountHelp.textContent = remainingAmount > 0
        ? `Sisa tagihan invoice ini ${formatRupiah(remainingAmount)}.`
        : 'Invoice ini sudah lunas.';
}

/**
 * Memvalidasi nominal pembayaran apakah melebihi sisa tagihan.
 *
 * @param  {string}  prefix
 * @return {boolean} true jika valid, false jika melebihi
 */
function validatePaymentProofAmount(prefix) {
    const config = getPaymentProofConfig(prefix);
    if (!config.amountInput || !config.amountWarning) return true;

    const selectedOption = config.invoiceNumber?.options[config.invoiceNumber.selectedIndex];
    const remainingAmount = Number(selectedOption?.dataset?.remainingAmount || 0);

    if (config.invoiceType?.value !== 'proyek' || !selectedOption?.value) {
        config.amountWarning.classList.add('hidden');
        return true;
    }

    const amountValue = parseCurrencyInput(config.amountInput.value);

    if (amountValue > remainingAmount && remainingAmount > 0) {
        config.amountWarning.innerHTML = '<span class="font-semibold">Peringatan:</span> Nominal pembayaran tidak boleh melebihi sisa tagihan sebesar ' + formatRupiah(remainingAmount) + '!';
        config.amountWarning.classList.remove('hidden');
        return false;
    } else {
        config.amountWarning.classList.add('hidden');
        return true;
    }
}

// ─── Binding Form ────────────────────────────────────────────────────────

/**
 * Mengikat event listener dan menginisialisasi form bukti pembayaran.
 *
 * @param  {string} prefix   'create' atau 'edit-{id}'
 * @param  {Object} defaults Nilai default untuk edit mode
 * @return {void}
 */
function bindPaymentProofForm(prefix, defaults = {}) {
    const config = getPaymentProofConfig(prefix);
    if (!config.module || !config.invoiceType || !config.invoiceNumber) return;

    if (defaults.moduleType) config.module.value = defaults.moduleType;
    if (defaults.invoiceType) config.invoiceType.value = defaults.invoiceType;

    updatePaymentProofInvoices(prefix, defaults.invoiceNumber ?? null);

    if (defaults.amount && config.amountInput) {
        config.amountInput.value = formatRupiah(defaults.amount);
    }

    config.module.addEventListener('change', () => updatePaymentProofInvoices(prefix));
    config.invoiceType.addEventListener('change', () => updatePaymentProofInvoices(prefix));
    config.invoiceNumber.addEventListener('change', () => updatePaymentProofStage(prefix));

    if (config.amountInput) {
        config.amountInput.addEventListener('input', function () {
            this.value = formatRupiah(parseCurrencyInput(this.value));
            updatePaymentProofAmountSection(prefix);
            validatePaymentProofAmount(prefix);
        });
    }

    updatePaymentProofStage(prefix);
    updatePaymentProofAmountSection(prefix);
    validatePaymentProofAmount(prefix);
}

// ─── Hapus Massal ─────────────────────────────────────────────────────────

/**
 * Submit form hapus dengan loading state.
 *
 * @return {void}
 */
function submitDeleteForm() {
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById('deleteForm');
    if (form) {
        form.submit();
    }
}

// ─── Checkbox Pilih Semua ────────────────────────────────────────────────

/**
 * Menginisialisasi checkbox select all dan update delete button state.
 *
 * @return {void}
 */
function initSelectAllCheckbox() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();
}

// ─── Inisialisasi ─────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    bindPaymentProofForm('create');

    // State loading submit form - Modal Tambah
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            if (!validatePaymentProofAmount('create')) {
                e.preventDefault();
                const warning = document.getElementById('payment-proof-amount-warning-create');
                if (warning) warning.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Checkbox pilih semua
    initSelectAllCheckbox();
});

// Expose ke global scope untuk akses dari onclick di Blade
window.submitDeleteForm = submitDeleteForm;
