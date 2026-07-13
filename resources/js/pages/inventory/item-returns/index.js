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
 * Memvalidasi input quantity tidak melebihi stok tersedia.
 *
 * @param {HTMLInputElement} quantityInput
 * @param {HTMLElement} warningEl
 * @param {HTMLButtonElement} submitBtn
 * @param {number} maxQuantity
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

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // PRINT DROPDOWN
    // ==========================================

    const printDropdownButton = document.getElementById('printDropdownButton');
    const printDropdownMenu = document.getElementById('printDropdownMenu');

    if (printDropdownButton && printDropdownMenu) {
        printDropdownButton.addEventListener('click', function (e) {
            e.stopPropagation();
            printDropdownMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                printDropdownMenu.classList.add('hidden');
            }
        });

        printDropdownMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

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
