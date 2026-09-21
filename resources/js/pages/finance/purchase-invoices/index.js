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
 * Hitung PPN untuk satu kartu faktur di modal Tambah (ADD).
 *
 * Menggunakan class elemen di dalam kartu:
 * - .pi-selling-price  : input harga jual (format Rupiah)
 * - .pi-ppn-percentage : input persentase PPN (dukung koma desimal)
 * - .pi-ppn-tax        : input readonly hasil PPN
 *
 * @param  {HTMLElement} card  Elemen .purchase-invoice-card
 */
function calculatePpnForCard(card) {
    if (!card) return;

    const sellingPriceInput = card.querySelector('.pi-selling-price');
    const ppnPercentageInput = card.querySelector('.pi-ppn-percentage');
    const ppnTaxInput = card.querySelector('.pi-ppn-tax');

    if (!sellingPriceInput || !ppnPercentageInput || !ppnTaxInput) return;

    const sellingPrice = parseCurrencyInput(sellingPriceInput.value);
    const ppnPercentage = parseDecimalInput(ppnPercentageInput.value);
    const ppnTax = Math.round((sellingPrice * ppnPercentage) / 100);

    ppnTaxInput.value = formatRupiah(ppnTax);
}

// ==========================================
// KARTU FAKTUR DINAMIS (MULTIPLE RECORD)
// ==========================================

/**
 * Ambil container kartu faktur di modal Tambah.
 *
 * @return {HTMLElement|null}
 */
function getPurchaseInvoiceContainer() {
    return document.getElementById('purchaseInvoicesContainer');
}

/**
 * Ambil semua kartu faktur yang sedang dirender di container.
 *
 * @param  {HTMLElement} container  Container kartu faktur
 * @return {HTMLElement[]}
 */
function getPurchaseInvoiceCards(container) {
    return container ? Array.from(container.querySelectorAll('.purchase-invoice-card')) : [];
}

/**
 * Perbarui tampilan seluruh kartu: nomor urut, visibilitas checkbox
 * "Sama dengan Faktur ke-1", dan visibilitas tombol hapus.
 *
 * Alur:
 * 1. Loop semua .purchase-invoice-card, set nomor faktur (index + 1).
 * 2. Checkbox "Sama dengan Faktur ke-1" hanya tampil untuk kartu != pertama.
 * 3. Tombol hapus hanya tampil jika ada lebih dari 1 kartu.
 *
 * @param  {HTMLElement} container  Container kartu faktur
 */
function updateInvoiceCardStates(container) {
    if (!container) return;

    const cards = getPurchaseInvoiceCards(container);

    cards.forEach(function (card, index) {
        const numberEl = card.querySelector('.card-number');
        if (numberEl) numberEl.textContent = index + 1;

        const copyWrapper = card.querySelector('.pi-copy-wrapper');
        if (copyWrapper) {
            copyWrapper.classList.toggle('hidden', index === 0);
            copyWrapper.classList.toggle('flex', index !== 0);
        }

        const copyCheckbox = card.querySelector('.pi-copy-from-first');
        if (copyCheckbox) copyCheckbox.checked = false;
    });

    const showRemove = cards.length > 1;
    cards.forEach(function (card) {
        card.querySelectorAll('.purchase-invoice-remove').forEach(function (btn) {
            btn.style.display = showRemove ? 'flex' : 'none';
        });
    });
}

/**
 * Menambahkan kartu faktur baru ke dalam modal Tambah.
 *
 * Alur:
 * 1. Ambil template #purchaseInvoiceRowTemplate.
 * 2. Ganti placeholder '__INDEX__' dengan nomor urut kartu berikutnya.
 * 3. Insert kartu ke container lalu perbarui status semua kartu.
 * 4. Pastikan kartu baru dalam keadaan terbuka (expanded) lalu fokus ke tanggal.
 */
function addInvoiceCard() {
    const container = getPurchaseInvoiceContainer();
    const template = document.getElementById('purchaseInvoiceRowTemplate');
    if (!container || !template) return;

    const nextIndex = getPurchaseInvoiceCards(container).length;
    const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));

    const wrapper = document.createElement('div');
    wrapper.innerHTML = html;

    const card = wrapper.firstElementChild;
    if (!card) return;

    container.appendChild(card);
    updateInvoiceCardStates(container);

    const body = card.querySelector('.card-body');
    const chevron = card.querySelector('.card-chevron');
    if (body) body.classList.remove('hidden');
    if (chevron) chevron.classList.remove('rotate-180');

    const dateInput = card.querySelector('.pi-date');
    if (dateInput) dateInput.focus();
}

/**
 * Tutup/buka (collapse) kartu faktur ketika header diklik.
 *
 * Alur:
 * 1. Cari .purchase-invoice-card terdekat dari header yang diklik.
 * 2. Toggle body kartu (hidden) dan putar ikon chevron 180 derajat.
 *
 * @param  {HTMLElement} header  Elemen .card-header yang diklik
 */
function toggleInvoiceCard(header) {
    const card = header && header.closest('.purchase-invoice-card');
    if (!card) return;

    const body = card.querySelector('.card-body');
    const chevron = card.querySelector('.card-chevron');

    if (body) body.classList.toggle('hidden');
    if (chevron) chevron.classList.toggle('rotate-180');
}

/**
 * Menghapus satu kartu faktur dari container.
 *
 * Alur:
 * 1. Hentikan propagasi klik agar header tidak ikut toggle (karena tombol
 *    hapus berada di dalam header yang bisa diklik).
 * 2. Cari .purchase-invoice-card terdekat; jika total kartu <= 1, batalkan.
 * 3. Hapus kartu lalu perbarui status semua kartu.
 *
 * @param  {Event}      event   Objek event (klik)
 * @param  {HTMLElement} button  Tombol hapus yang diklik
 */
function removeInvoiceCard(event, button) {
    if (event) event.stopPropagation();

    const card = button.closest('.purchase-invoice-card');
    if (!card) return;

    const container = getPurchaseInvoiceContainer();
    if (getPurchaseInvoiceCards(container).length <= 1) return;

    card.remove();
    updateInvoiceCardStates(container);
}

/**
 * Menyalin seluruh kolom dari faktur pertama ke kartu yang mencentang
 * checkbox "Sama dengan Faktur ke-1".
 *
 * Alur:
 * 1. Ambil kartu target (kartu yang checkbox-nya dicentang) dan kartu pertama.
 * 2. Salin nilai date, material, npwp, tax code, item, selling price,
 *    ppn percentage, dan notes dari kartu pertama ke kartu target.
 * 3. Hitung ulang PPN kartu target.
 *
 * @param  {HTMLInputElement} checkbox  Checkbox .pi-copy-from-first
 */
function handleCopyFromFirst(checkbox) {
    const targetCard = checkbox.closest('.purchase-invoice-card');
    if (!targetCard || !checkbox.checked) return;

    const container = getPurchaseInvoiceContainer();
    const cards = getPurchaseInvoiceCards(container);
    const firstCard = cards[0];

    if (!firstCard || firstCard === targetCard) return;

    const selectors = [
        '.pi-date',
        '.pi-material',
        '.pi-npwp',
        '.pi-tax-code',
        '.pi-item',
        '.pi-selling-price',
        '.pi-ppn-percentage',
        '.pi-notes',
    ];

    selectors.forEach(function (selector) {
        const source = firstCard.querySelector(selector);
        const target = targetCard.querySelector(selector);
        if (source && target) target.value = source.value;
    });

    calculatePpnForCard(targetCard);
}

/**
 * Inisialisasi event listener kalkulasi PPN untuk modal Tambah (ADD).
 *
 * Menggunakan event delegation pada container sehingga berlaku juga untuk
 * kartu yang ditambahkan secara dinamis.
 *
 * Alur:
 * 1. Pasang listener 'input' pada container #purchaseInvoicesContainer.
 * 2. Jika input adalah .pi-selling-price: format ke Rupiah lalu hitung PPN.
 * 3. Jika input adalah .pi-ppn-percentage: hitung ulang PPN.
 * 4. Hitung nilai PPN awal untuk setiap kartu yang ada.
 */
function initAddModalPpnCalculation() {
    const container = getPurchaseInvoiceContainer();
    if (!container) return;

    container.addEventListener('input', function (event) {
        const target = event.target;
        if (!target || !target.classList) return;

        const card = target.closest('.purchase-invoice-card');
        if (!card) return;

        if (target.classList.contains('pi-selling-price')) {
            target.value = formatRupiah(parseCurrencyInput(target.value));
            calculatePpnForCard(card);
        } else if (target.classList.contains('pi-ppn-percentage')) {
            calculatePpnForCard(card);
        }
    });

    // Hitung nilai awal untuk semua kartu yang ada
    getPurchaseInvoiceCards(container).forEach(calculatePpnForCard);
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

window.addInvoiceCard = addInvoiceCard;
window.toggleInvoiceCard = toggleInvoiceCard;
window.removeInvoiceCard = removeInvoiceCard;
window.handleCopyFromFirst = handleCopyFromFirst;

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
    // INITIALIZE KARTU FAKTUR (MULTIPLE RECORD)
    // ==========================================
    updateInvoiceCardStates(getPurchaseInvoiceContainer());

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
