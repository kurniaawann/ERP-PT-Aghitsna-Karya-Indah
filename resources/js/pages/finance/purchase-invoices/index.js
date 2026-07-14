/* global parseCurrencyInput, formatRupiah, handleFormSubmit, resetFormSubmitState */

// ==========================================
// CURRENCY PARSERS
// ==========================================

/**
 * Parse input desimal (mendukung koma sebagai pemisah desimal).
 *
 * @param  {string|number} value  Nilai input
 * @return {number} Nilai desimal
 */
function parseDecimalInput(value) {
    const rawValue = String(value ?? '').trim();
    if (!rawValue) return 0;
    return parseFloat(rawValue.replace(',', '.')) || 0;
}

// ==========================================
// PPN CALCULATION
// ==========================================

/**
 * Hitung PPN dari harga jual dan persentase PPN.
 *
 * @param  {string} sellingPriceId    ID input harga jual
 * @param  {string} ppnPercentageId   ID input persentase PPN
 * @param  {string} ppnTaxId          ID input PPN pajak (readonly)
 */
function calculatePpnTax(sellingPriceId, ppnPercentageId, ppnTaxId) {
    const sellingPriceInput = document.getElementById(sellingPriceId);
    const ppnPercentageInput = document.getElementById(ppnPercentageId);
    const ppnTaxInput = document.getElementById(ppnTaxId);

    if (!sellingPriceInput || !ppnPercentageInput || !ppnTaxInput) return;

    const sellingPrice = parseCurrencyInput(sellingPriceInput.value);
    const ppnPercentage = parseDecimalInput(ppnPercentageInput.value);
    const ppnTax = Math.round((sellingPrice * ppnPercentage) / 100);

    ppnTaxInput.value = formatRupiah(ppnTax);
}

/**
 * Inisialisasi event listener kalkulasi PPN untuk ADD modal.
 */
function initAddModalPpnCalculation() {
    const addSellingPrice = document.getElementById('addSellingPrice');
    const addPpnPercentage = document.getElementById('addPpnPercentage');

    if (!addSellingPrice || !addPpnPercentage) return;

    addSellingPrice.addEventListener('input', () => {
        addSellingPrice.value = formatRupiah(parseCurrencyInput(addSellingPrice.value));
        calculatePpnTax('addSellingPrice', 'addPpnPercentage', 'addPpnTax');
    });

    addPpnPercentage.addEventListener('input', () => {
        calculatePpnTax('addSellingPrice', 'addPpnPercentage', 'addPpnTax');
    });

    // Hitung nilai awal
    calculatePpnTax('addSellingPrice', 'addPpnPercentage', 'addPpnTax');
}

/**
 * Inisialisasi event listener kalkulasi PPN untuk semua EDIT modal.
 */
function initEditModalsPpnCalculation() {
    document.querySelectorAll('[id^="editSellingPrice-"]').forEach(sellingPriceInput => {
        const invoiceId = sellingPriceInput.id.replace('editSellingPrice-', '');
        const ppnPercentageInput = document.getElementById(`editPpnPercentage-${invoiceId}`);

        if (!ppnPercentageInput) return;

        sellingPriceInput.addEventListener('input', () => {
            sellingPriceInput.value = formatRupiah(parseCurrencyInput(sellingPriceInput.value));
            calculatePpnTax(`editSellingPrice-${invoiceId}`, `editPpnPercentage-${invoiceId}`, `editPpnTax-${invoiceId}`);
        });

        ppnPercentageInput.addEventListener('input', () => {
            calculatePpnTax(`editSellingPrice-${invoiceId}`, `editPpnPercentage-${invoiceId}`, `editPpnTax-${invoiceId}`);
        });

        // Hitung nilai awal
        calculatePpnTax(`editSellingPrice-${invoiceId}`, `editPpnPercentage-${invoiceId}`, `editPpnTax-${invoiceId}`);
    });
}

// ==========================================
// BULK DELETE
// ==========================================

/**
 * Submit form bulk delete.
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
// DOM READY
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    // ==========================================
    // INITIALIZE PPN CALCULATION
    // ==========================================
    initAddModalPpnCalculation();
    initEditModalsPpnCalculation();

    // ==========================================
    // SELECT ALL CHECKBOX FUNCTIONALITY
    // ==========================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(invoiceCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
            deleteButton.classList.toggle('opacity-50', !anyChecked);
            deleteButton.classList.toggle('cursor-not-allowed', !anyChecked);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            invoiceCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    invoiceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateDeleteButtonState();
            const allChecked = Array.from(invoiceCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(invoiceCheckboxes).some(cb => cb.checked);
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });

    updateDeleteButtonState();

    // ==========================================
    // FORM SUBMIT HANDLING — ADD MODAL
    // ==========================================

    const addModal = document.getElementById('addModal');
    const addForm = addModal ? addModal.querySelector('form') : null;
    const addSubmitBtn = addForm ? addForm.querySelector('button[type="submit"]') : null;

    if (addForm && addSubmitBtn) {
        addForm.addEventListener('submit', function () {
            handleFormSubmit(addSubmitBtn, null, 'Menyimpan...');
        });
    }

    // ==========================================
    // FORM SUBMIT HANDLING — EDIT MODALS
    // ==========================================

    document.querySelectorAll('[id^="editModal-"]').forEach(editModal => {
        const editForm = editModal.querySelector('form');
        const editButton = editModal.querySelector('form button[type="submit"]');
        if (editForm && editButton) {
            editForm.addEventListener('submit', function () {
                handleFormSubmit(editButton, null, 'Menyimpan...');
            });
        }
    });

    // ==========================================
    // AUTO-DISMISS ALERT MESSAGES
    // ==========================================

    function autoDismissAlert(alertId, delay = 5000) {
        const alert = document.getElementById(alertId);
        if (alert) {
            setTimeout(() => alert.classList.add('hidden'), delay);
        }
    }

    autoDismissAlert('errorAlert');
    autoDismissAlert('successAlert');

    // ==========================================
    // AUTO-SCROLL TO ERROR ALERTS
    // ==========================================

    const addErrorAlert = document.getElementById('addErrorAlert');
    if (addErrorAlert) {
        setTimeout(() => {
            addErrorAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }

    document.querySelectorAll('[id$="ErrorAlert"]').forEach(alert => {
        if (alert.id !== 'addErrorAlert') {
            setTimeout(() => {
                alert.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    });

    // ==========================================
    // FILTER BY MONTH, YEAR — AUTO SUBMIT
    // ==========================================

    const monthFilter = document.querySelector('select[name="month"]') || document.getElementById('month-select');
    const yearFilter = document.querySelector('select[name="year"]') || document.getElementById('year-select');

    [monthFilter, yearFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', function () {
                const form = this.closest('form');
                if (form) form.submit();
            });
        }
    });

    // ==========================================
    // RESET SUBMIT STATE ON PAGE SHOW
    // ==========================================

    window.addEventListener('pageshow', () => resetFormSubmitState());
});
