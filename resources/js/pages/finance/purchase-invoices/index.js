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
 * Alur kalkulasi PPN:
 * 1. Ambil elemen input harga jual, persentase PPN, dan field hasil PPN.
 * 2. Parse harga jual dengan parseCurrencyInput (format Rupiah).
 * 3. Parse persentase PPN dengan parseDecimalInput (dukung koma desimal).
 * 4. Hitung PPN = round((harga jual * persentase) / 100).
 * 5. Tulis hasil ke input PPN dalam format Rupiah (formatRupiah).
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
 *
 * Alur:
 * 1. Cari input harga jual (#addSellingPrice) dan persentase PPN (#addPpnPercentage).
 * 2. Saat harga jual di-input: format ke Rupiah lalu hitung ulang PPN.
 * 3. Saat persentase PPN di-input: hitung ulang PPN.
 * 4. Hitung nilai PPN awal saat modal pertama kali dibuka.
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
 *
 * Alur:
 * 1. Loop semua input harga jual edit (#editSellingPrice-{id}).
 * 2. Ekstrak invoiceId dari id input dan cari input persentase PPN terkait.
 * 3. Saat harga jual di-input: format ke Rupiah lalu hitung ulang PPN.
 * 4. Saat persentase PPN di-input: hitung ulang PPN.
 * 5. Hitung nilai PPN awal untuk setiap modal edit.
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

/**
 * Inisialisasi logika halaman saat DOM siap.
 *
 * Alur:
 * 1. Inisialisasi kalkulasi PPN untuk modal Tambah dan semua modal Edit.
 * 2. Ikat checkbox pilih semua + update status tombol hapus massal.
 * 3. Ikat submit form modal Tambah & Edit (loading state via handleFormSubmit).
 * 4. Auto-dismiss alert error/success.
 * 5. Auto-scroll ke alert error jika ada.
 * 6. Ikat auto-submit form filter bulan & tahun.
 * 7. Reset status submit saat halaman dimuat ulang (pageshow).
 */
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

    /**
     * Update status tombol hapus berdasarkan checkbox invoice yang dipilih.
     * Aktif bila minimal ada 1 checkbox dicentang; nonaktif + opacity bila 0.
     */
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

    /**
     * Menyembunyikan alert setelah durasi tertentu (auto-dismiss).
     *
     * @param  {string} alertId  ID elemen alert yang akan disembunyikan
     * @param  {number} delay    Waktu tunggu dalam ms (default: 5000)
     */
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
