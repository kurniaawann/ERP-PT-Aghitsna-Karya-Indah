/**
 * Pengembalian Barang (Item Return) — Page Module
 *
 * Mengelola semua interaksi halaman Pengembalian Barang:
 * - Dynamic return type → item population
 * - Real-time quantity validation
 * - Form submission dengan loading state
 * - Select all / bulk delete
 * - Print dropdown
 * - Filter auto-submit
 */

// ==========================================
// DATA DARI SERVER
// ==========================================

const stockInsData = window.ITEM_RETURN_DATA?.stockIns || [];
const stockOutsData = window.ITEM_RETURN_DATA?.stockOuts || [];
const itemsData = window.ITEM_RETURN_DATA?.items || [];

// ==========================================
// HELPER: LOADING STATE
// ==========================================

/**
 * Menampilkan spinner pada button dan menonaktifkannya.
 *
 * @param {HTMLElement} button
 * @param {string} text Teks loading yang ditampilkan
 */
function showSpinner(button, text = 'Processing...') {
    if (!button) return;
    button.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${text}`;
    button.disabled = true;
    button.classList.add('opacity-70', 'cursor-not-allowed');
}

// ==========================================
// RETURN TYPE → ITEM POPULATION
// ==========================================

const ITEMS_PER_PAGE = 10;

const addDropdownState = {
    items: [],
    filteredItems: [],
    displayedCount: 0,
};

/**
 * Membangun isi dropdown pilih barang berdasarkan tipe retur.
 *
 * Alur pilih tipe retur (stock in vs stock out):
 * 1. Pilih sumber data: stockInsData untuk tipe 'masuk',
 *    stockOutsData untuk tipe 'keluar'.
 * 2. Agregasi record per id_item (unik) dengan nama dari itemsData,
 *    quantity stok, dan id sumber (stock-in / stock-out).
 * 3. Simpan ke addDropdownState, reset state filter & pagination,
 *    lalu render batch pertama (10 item).
 * 4. Search input direset, hidden input id/stock dihapus, dan
 *    dropdown/load-more/no-results dikembalikan ke kondisi awal.
 *
 * @param {string} returnType  Tipe retur: 'masuk' (stock in) atau 'keluar' (stock out)
 * @returns {void}
 */
function buildAddItemDropdown(returnType) {
    const searchInput = document.getElementById('addItemSearch');
    const filterInput = document.getElementById('addItemFilter');
    const dropdown = document.getElementById('add-item-dropdown');
    const optionsContainer = document.getElementById('add-item-options');
    const loadMoreBtn = document.getElementById('add-item-load-more');
    const noResults = document.getElementById('add-item-no-results');
    const itemIdInput = document.getElementById('addItemId');
    const stockInIdInput = document.getElementById('addStockInId');
    const stockOutIdInput = document.getElementById('addStockOutId');

    if (!searchInput || !dropdown) return;

    searchInput.value = '';
    searchInput.placeholder = '-- Pilih Barang --';
    itemIdInput.value = '';
    if (stockInIdInput) stockInIdInput.value = '';
    if (stockOutIdInput) stockOutIdInput.value = '';
    if (filterInput) filterInput.value = '';
    optionsContainer.innerHTML = '';
    loadMoreBtn.classList.add('hidden');
    noResults.classList.add('hidden');

    const sourceData = returnType === 'masuk' ? stockInsData : stockOutsData;
    const uniqueItems = {};

    sourceData.forEach(record => {
        if (!uniqueItems[record.id_item]) {
            uniqueItems[record.id_item] = {
                id_item: record.id_item,
                name: itemsData.find(i => i.id_item === record.id_item)?.name_item || 'Item',
                quantity: record.quantity,
                stockInId: returnType === 'masuk' ? record.id_stock_in : null,
                stockOutId: returnType === 'keluar' ? record.id_stock_out : null,
            };
        }
    });

    addDropdownState.items = Object.values(uniqueItems);
    addDropdownState.filteredItems = [...addDropdownState.items];
    addDropdownState.displayedCount = 0;

    renderAddDropdownItems();
}

/**
 * Merender batch item berikutnya pada dropdown berpaginasi (10 item per batch).
 *
 * Alur:
 * 1. Ambil batch berikutnya dari filteredItems berdasarkan displayedCount.
 * 2. Render tiap item sebagai opsi; klik memanggil selectAddItem().
 * 3. Tambah displayedCount; tampilkan/sembunyikan tombol "load more"
 *    sesuai sisa data, dan area "no results" bila tidak ada item.
 *
 * @returns {void}
 */
function renderAddDropdownItems() {
    const optionsContainer = document.getElementById('add-item-options');
    const loadMoreBtn = document.getElementById('add-item-load-more');
    const noResults = document.getElementById('add-item-no-results');

    if (!optionsContainer) return;

    const { filteredItems, displayedCount } = addDropdownState;
    const nextBatch = filteredItems.slice(displayedCount, displayedCount + ITEMS_PER_PAGE);

    nextBatch.forEach(item => {
        const div = document.createElement('div');
        div.className = 'px-3 py-2 hover:bg-primary-light cursor-pointer text-sm transition-colors';
        div.dataset.value = item.id_item;
        div.dataset.stockInId = item.stockInId || '';
        div.dataset.stockOutId = item.stockOutId || '';
        div.dataset.quantity = item.quantity;
        div.dataset.search = `${item.id_item} ${item.name}`.toLowerCase();
        div.innerHTML = `<span class="font-medium">${item.id_item} - ${item.name}</span> <span class="text-text-secondary text-xs">(Stok: ${item.quantity})</span>`;
        div.addEventListener('click', function () {
            selectAddItem(this);
        });
        optionsContainer.appendChild(div);
    });

    addDropdownState.displayedCount += nextBatch.length;

    if (addDropdownState.displayedCount < filteredItems.length) {
        loadMoreBtn.classList.remove('hidden');
    } else {
        loadMoreBtn.classList.add('hidden');
    }

    if (filteredItems.length === 0) {
        noResults.classList.remove('hidden');
    } else {
        noResults.classList.add('hidden');
    }
}

/**
 * Mengisi nilai item yang dipilih ke search input dan hidden input.
 *
 * Alur:
 * 1. Tampilkan nama item terpilih pada search input dan set readOnly.
 * 2. Simpan id_item, id stock-in/stock-out ke hidden input.
 * 3. Tutup dropdown lalu jalankan validateAddQuantity() agar stok
 *    tersedia langsung divalidasi.
 *
 * @param {HTMLElement} optionEl  Elemen opsi yang diklik
 * @returns {void}
 */
function selectAddItem(optionEl) {
    const searchInput = document.getElementById('addItemSearch');
    const dropdown = document.getElementById('add-item-dropdown');
    const itemIdInput = document.getElementById('addItemId');
    const stockInIdInput = document.getElementById('addStockInId');
    const stockOutIdInput = document.getElementById('addStockOutId');

    searchInput.value = optionEl.querySelector('.font-medium').textContent.trim();
    searchInput.readOnly = true;
    itemIdInput.value = optionEl.dataset.value;
    if (stockInIdInput) stockInIdInput.value = optionEl.dataset.stockInId;
    if (stockOutIdInput) stockOutIdInput.value = optionEl.dataset.stockOutId;

    dropdown.classList.add('hidden');
    validateAddQuantity();
}

/**
 * Memfilter daftar item berdasarkan kata kunci pencarian.
 *
 * Alur:
 * 1. Filter addDropdownState.items berdasarkan teks "id_item nama"
 *    yang mengandung kata kunci (case-insensitive).
 * 2. Reset pagination (displayedCount = 0), kosongkan container,
 *    lalu render ulang batch pertama.
 *
 * @param {string} searchTerm  Kata kunci pencarian
 * @returns {void}
 */
function filterAddDropdown(searchTerm) {
    const optionsContainer = document.getElementById('add-item-options');
    const loadMoreBtn = document.getElementById('add-item-load-more');
    const noResults = document.getElementById('add-item-no-results');

    const term = searchTerm.toLowerCase();
    addDropdownState.filteredItems = addDropdownState.items.filter(item => {
        const searchText = `${item.id_item} ${item.name}`.toLowerCase();
        return searchText.includes(term);
    });
    addDropdownState.displayedCount = 0;

    optionsContainer.innerHTML = '';
    renderAddDropdownItems();
}

/**
 * Menangani perubahan tipe retur pada modal.
 *
 * Alur pilih tipe retur:
 * 1. Untuk modal 'add', baca nilai select tipe retur
 *    (masuk = stock in, keluar = stock out).
 * 2. Panggil buildAddItemDropdown() untuk mengisi ulang daftar item
 *    dari sumber data yang sesuai (stock in / stock out).
 *
 * @param {string} modalPrefix  Prefix ID modal ('add')
 * @returns {void}
 */
function handleReturnTypeChange(modalPrefix) {
    if (modalPrefix === 'add') {
        const returnTypeSelect = document.getElementById(modalPrefix + 'ReturnType');
        if (!returnTypeSelect) return;
        buildAddItemDropdown(returnTypeSelect.value);
    }
}

// ==========================================
// QUANTITY VALIDATION
// ==========================================

/**
 * Memvalidasi input quantity tidak melebihi stok sumber.
 *
 * Alur:
 * 1. Parse quantity dari input (0 bila tidak valid).
 * 2. Tandai isExceeded bila quantity > stok sumber (maxQuantity > 0).
 * 3. Jika melebihi: tampilkan warning, nonaktifkan tombol submit dengan
 *    gaya visual (opacity + cursor-not-allowed), kembalikan false.
 * 4. Jika valid: sembunyikan warning, aktifkan kembali submit, true.
 *
 * @param {HTMLInputElement} quantityInput  Input quantity yang divalidasi
 * @param {HTMLElement} warningEl           Elemen warning yang ditampilkan/disembunyikan
 * @param {HTMLButtonElement} submitBtn     Tombol submit yang dinonaktifkan
 * @param {number} maxQuantity              Stok sumber maksimum
 * @returns {boolean} true jika quantity valid
 */
function validateQuantity(quantityInput, warningEl, submitBtn, maxQuantity) {
    if (!quantityInput) return true;

    const inputQuantity = parseInt(quantityInput.value) || 0;
    const isExceeded = inputQuantity > maxQuantity && maxQuantity > 0;

    if (isExceeded) {
        if (warningEl) warningEl.classList.remove('hidden');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
        return false;
    } else {
        if (warningEl) warningEl.classList.add('hidden');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
        return true;
    }
}

// ==========================================
// GLOBAL: SUBMIT DELETE FORM
// Dipanggil oleh x-modal component via onConfirm="submitDeleteForm()"
// ==========================================

/**
 * Submit form hapus (bulk delete) dengan loading state pada tombol konfirmasi.
 *
 * Dipanggil oleh x-modal component via onConfirm="submitDeleteForm()".
 * Menampilkan spinner "Menghapus...", menonaktifkan tombol, lalu submit
 * form deleteForm.
 *
 * @returns {void}
 */
window.submitDeleteForm = function () {
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
};

// ==========================================
// INITIALIZATION
// ==========================================

/**
 * Inisialisasi halaman Pengembalian Barang (Item Return).
 *
 * Alur:
 * 1. Modal tambah: perubahan tipe retur memicu handleReturnTypeChange();
 *    focus search memuat ulang dropdown; filter input memfilter item;
 *    tombol load more menambah batch 10 item; klik di luar menutup dropdown.
 * 2. Submit modal tambah memastikan item terpilih, memvalidasi quantity,
 *    scroll ke warning bila tidak valid, lalu showSpinner() saat valid.
 * 3. Modal edit: quantity divalidasi real-time dan saat submit.
 * 4. Form hapus menampilkan spinner saat submit.
 * 5. Filter bulan/tahun/tipe auto-submit saat berubah.
 * 6. Checkbox select all & per-item mengontrol tombol hapus bulk delete.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // ADD MODAL SETUP
    // ==========================================

    const addModal = document.getElementById('addModal');
    const addFormElement = addModal ? addModal.querySelector('form') : null;
    const addButton = addFormElement ? addFormElement.querySelector('button[type="submit"]') : null;
    const addReturnType = addModal ? addModal.querySelector('#addReturnType') : null;
    const addItemSearch = addModal ? addModal.querySelector('#addItemSearch') : null;
    const addItemFilter = addModal ? addModal.querySelector('#addItemFilter') : null;
    const addItemDropdown = addModal ? addModal.querySelector('#add-item-dropdown') : null;
    const addItemLoadMore = addModal ? addModal.querySelector('#add-item-load-more') : null;
    const addQuantityInput = addModal ? addModal.querySelector('#addQuantity') : null;
    const addQuantityWarning = addModal ? addModal.querySelector('#addQuantityWarning') : null;
    const addAvailableStock = addModal ? addModal.querySelector('#addAvailableStock') : null;

    if (addReturnType) {
        addReturnType.addEventListener('change', function () {
            handleReturnTypeChange('add');
        });
    }

    if (addItemSearch) {
        addItemSearch.addEventListener('focus', function () {
            if (addItemDropdown) {
                addItemDropdown.classList.remove('hidden');
                if (addItemFilter) {
                    addItemFilter.value = '';
                    addItemFilter.focus();
                    filterAddDropdown('');
                }
            }
        });
    }

    if (addItemFilter) {
        addItemFilter.addEventListener('input', function () {
            filterAddDropdown(this.value);
        });
    }

    if (addItemLoadMore) {
        addItemLoadMore.querySelector('button').addEventListener('click', function (e) {
            e.preventDefault();
            renderAddDropdownItems();
        });
    }

    document.addEventListener('click', function (e) {
        if (addItemSearch && addItemDropdown &&
            !addItemSearch.contains(e.target) && !addItemDropdown.contains(e.target)) {
            addItemDropdown.classList.add('hidden');
        }
    });

    /**
     * Memvalidasi quantity pada modal tambah terhadap stok sumber terpilih.
     *
     * Alur:
     * 1. Cari maxQuantity dari addDropdownState berdasarkan id_item yang
     *    tersimpan di hidden input addItemId.
     * 2. Panggil validateQuantity() yang menampilkan warning dan
     *    menonaktifkan tombol submit bila quantity melebihi stok.
     * 3. Tampilkan teks "Stok tersedia: N" bila ada stok, atau kosongkan.
     *
     * @returns {void}
     */
    function validateAddQuantity() {
        if (!addQuantityInput) return;

        const itemIdInput = document.getElementById('addItemId');
        const maxQuantity = addDropdownState.items.find(
            i => i.id_item === (itemIdInput?.value || '')
        )?.quantity || 0;

        validateQuantity(addQuantityInput, addQuantityWarning, addButton, maxQuantity);

        if (addAvailableStock && maxQuantity > 0) {
            addAvailableStock.textContent = `Stok tersedia: ${maxQuantity}`;
        } else if (addAvailableStock) {
            addAvailableStock.textContent = '';
        }
    }

    if (addQuantityInput) {
        addQuantityInput.addEventListener('input', validateAddQuantity);
    }

    if (addFormElement && addButton) {
        addFormElement.addEventListener('submit', function (e) {
            const itemIdInput = document.getElementById('addItemId');
            if (!itemIdInput || !itemIdInput.value) {
                e.preventDefault();
                if (addItemSearch) {
                    addItemSearch.focus();
                    addItemDropdown.classList.remove('hidden');
                    if (addItemFilter) addItemFilter.focus();
                }
                return false;
            }
            validateAddQuantity();
            if (addButton.disabled) {
                e.preventDefault();
                addQuantityWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            showSpinner(addButton);
        });
    }

    // ==========================================
    // EDIT MODALS — QUANTITY VALIDATION
    // ==========================================

    document.querySelectorAll('[id^="editModal-"]').forEach(function (editModal) {
        const editForm = editModal.querySelector('form');
        const editButton = editForm ? editForm.querySelector('button[type="submit"]') : null;
        const returnId = editModal.id.replace('editModal-', '');
        const quantityInput = document.getElementById(`editQuantity-${returnId}`);
        const quantityWarning = document.getElementById(`editQuantityWarning-${returnId}`);
        const availableStock = document.getElementById(`editAvailableStock-${returnId}`);

        if (!editForm || !editButton || !quantityInput) return;

        const maxQuantity = parseInt(quantityInput.dataset.maxQuantity) || 0;

        /**
         * Memvalidasi quantity pada modal edit terhadap stok sumber.
         *
         * maxQuantity diambil dari dataset attribute pada input quantity,
         * lalu diteruskan ke validateQuantity() untuk menampilkan warning
         * dan mengontrol tombol submit.
         *
         * @returns {void}
         */
        function validateEditQuantity() {
            validateQuantity(quantityInput, quantityWarning, editButton, maxQuantity);
        }

        quantityInput.addEventListener('input', validateEditQuantity);
        validateEditQuantity();

        editForm.addEventListener('submit', function (e) {
            validateEditQuantity();
            if (editButton.disabled) {
                e.preventDefault();
                quantityWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            showSpinner(editButton);
        });
    });

    // ==========================================
    // DELETE FORM
    // ==========================================

    const deleteForm = document.getElementById('deleteForm');
    const deleteButton = document.getElementById('confirm-btn-deleteModal');

    if (deleteForm && deleteButton) {
        deleteForm.addEventListener('submit', function () {
            showSpinner(deleteButton, 'Menghapus...');
        });
    }

    // ==========================================
    // FILTER AUTO-SUBMIT
    // ==========================================

    const monthFilter = document.getElementById('month-select');
    const yearFilter = document.getElementById('year-select');
    const typeFilter = document.getElementById('return_type');

    [monthFilter, yearFilter, typeFilter].forEach(function (filter) {
        if (filter) {
            filter.addEventListener('change', function () {
                const form = this.closest('form');
                if (form) form.submit();
            });
        }
    });

    // ==========================================
    // SELECT ALL / BULK DELETE
    // ==========================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_returns[]"]');
    const bulkDeleteButton = document.getElementById('delete-button');

    /**
     * Mengupdate status tombol hapus bulk berdasarkan checkbox terpilih.
     *
     * Tombol hapus dinonaktifkan (dan diberi gaya opacity/cursor-not-allowed)
     * saat tidak ada checkbox yang dicentang.
     *
     * @returns {void}
     */
    function updateBulkDeleteButtonState() {
        const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
        if (bulkDeleteButton) {
            bulkDeleteButton.disabled = !anyChecked;
            bulkDeleteButton.classList.toggle('opacity-50', !anyChecked);
            bulkDeleteButton.classList.toggle('cursor-not-allowed', !anyChecked);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            itemCheckboxes.forEach(cb => (cb.checked = this.checked));
            updateBulkDeleteButtonState();
        });
    }

    itemCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            updateBulkDeleteButtonState();
            const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allChecked;
                selectAllCheckbox.indeterminate = someChecked && !allChecked;
            }
        });
    });

    updateBulkDeleteButtonState();
});
