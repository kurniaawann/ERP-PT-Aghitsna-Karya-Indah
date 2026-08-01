/**
 * Barang Masuk (Stock In) - Page Module
 *
 * Mengelola semua interaksi halaman Barang Masuk:
 * - Dynamic items form (add/edit modals)
 * - Searchable dropdown barang
 * - Select all / bulk delete
 * - Form submission dengan double-submit prevention
 * - Currency formatting
 */

// ==========================================
// CURRENCY HELPERS (module-scoped)
// ==========================================

function parseCurrencyValue(value) {
    const rawValue = String(value ?? '').replace(/[^0-9]/g, '');
    return rawValue ? parseInt(rawValue, 10) || 0 : 0;
}

function formatCurrencyInput(input) {
    const rawValue = String(input.value ?? '').replace(/[^0-9]/g, '');
    input.value = rawValue ? parseInt(rawValue, 10).toLocaleString('id-ID') : '';
}

window.formatCurrencyInput = formatCurrencyInput;

// ==========================================
// BULK DELETE
// ==========================================

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

window.submitDeleteForm = submitDeleteForm;

// ==========================================
// DYNAMIC ITEMS FORM
// ==========================================

function serializeItemsData(container) {
    const itemRows = container.querySelectorAll('.item-row');
    const items = [];

    itemRows.forEach(row => {
        const fromStock = row.querySelector('.item-from-stock, .item-from-stock-edit');
        const nameInput = row.querySelector('.item-name');
        const qtyInput = row.querySelector('.item-qty');
        const capitalInput = row.querySelector('.item-capital');
        const hiddenInput = row.querySelector('.item-select-hidden');

        if (nameInput && nameInput.value.trim()) {
            items.push({
                id_item: hiddenInput ? hiddenInput.value : null,
                name_item: nameInput.value || '',
                quantity: parseInt(qtyInput?.value) || 0,
                capital_price: parseCurrencyValue(capitalInput?.value),
                from_stock: fromStock ? fromStock.checked : false,
            });
        }
    });

    return items;
}

function initSearchableDropdown(row) {
    const searchInput = row.querySelector('.item-search-input');
    const dropdown = row.querySelector('.item-dropdown');
    const options = row.querySelectorAll('.item-option');
    const noResults = row.querySelector('.no-results');
    const hiddenInput = row.querySelector('.item-select-hidden');
    const nameInput = row.querySelector('.item-name');
    const capitalInput = row.querySelector('.item-capital');

    if (!searchInput || !dropdown) return;

    searchInput.addEventListener('focus', function () {
        dropdown.classList.remove('hidden');
    });

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        let hasResults = false;

        options.forEach(option => {
            const searchText = option.dataset.search || '';
            if (searchText.includes(searchTerm)) {
                option.style.display = 'block';
                hasResults = true;
            } else {
                option.style.display = 'none';
            }
        });

        const itemOptionsDiv = row.querySelector('.item-options');
        if (hasResults) {
            noResults.classList.add('hidden');
            itemOptionsDiv.classList.remove('hidden');
        } else {
            noResults.classList.remove('hidden');
            itemOptionsDiv.classList.add('hidden');
        }
    });

    options.forEach(option => {
        option.addEventListener('click', function () {
            const value = this.dataset.value;
            const name = this.dataset.name;
            const capital = this.dataset.capital;

            searchInput.value = name || '';
            hiddenInput.value = value || '';

            if (value) {
                nameInput.value = name;
                capitalInput.value = capital;
                formatCurrencyInput(capitalInput);
            }

            dropdown.classList.add('hidden');
        });
    });

    document.addEventListener('click', function (e) {
        if (!row.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
}

function attachItemListeners(container) {
    const itemRows = container.querySelectorAll('.item-row');

    itemRows.forEach(row => {
        const removeBtn = row.querySelector('.remove-item');
        const fromStockCheckbox = row.querySelector('.item-from-stock, .item-from-stock-edit');
        const capitalInput = row.querySelector('.item-capital, .item-capital-edit');

        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const itemsList = row.closest('.items-list') || row.closest('[id^="items-list"]');
                row.remove();
                if (itemsList && itemsList.querySelectorAll('.item-row').length > 1) {
                    itemsList.querySelectorAll('.remove-item').forEach(btn => (btn.style.display = 'block'));
                }
            });
        }

        if (fromStockCheckbox) {
            fromStockCheckbox.addEventListener('change', function () {
                const selectWrapper = row.querySelector('.item-select-wrapper');
                const nameInput = row.querySelector('.item-name');
                const capitalInput = row.querySelector('.item-capital');

                if (this.checked) {
                    selectWrapper.style.display = 'block';
                    nameInput.readOnly = true;
                    nameInput.classList.add('bg-gray-100');
                } else {
                    selectWrapper.style.display = 'none';
                    nameInput.readOnly = false;
                    nameInput.classList.remove('bg-gray-100');
                    nameInput.value = '';
                    capitalInput.value = '';
                    row.querySelector('.item-select-hidden').value = '';
                }
            });
        }

        if (capitalInput) {
            if (capitalInput.value) {
                formatCurrencyInput(capitalInput);
            }
            capitalInput.addEventListener('input', function () {
                formatCurrencyInput(this);
            });
        }

        initSearchableDropdown(row);
    });
}

// ==========================================
// BUILD ITEM OPTION HTML (from window.STOCK_IN_ITEMS)
// ==========================================

function buildItemDropdownHtml() {
    const items = window.STOCK_IN_ITEMS || [];
    let optionsHtml = `
        <div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b border-border-light" data-value="">
            -- Pilih Barang --
        </div>`;

    items.forEach(item => {
        optionsHtml += `
            <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light item-option"
                data-value="${item.id_item}"
                data-name="${item.name_item}"
                data-capital="${item.capital_price}"
                data-stock="${item.quantity}"
                data-search="${item.name_item.toLowerCase()}">
                <div class="font-medium text-text-heading">${item.name_item}</div>
                <div class="text-xs text-text-secondary mt-1">
                    Stok: <span class="font-semibold text-primary">${item.quantity}</span> unit
                </div>
            </div>`;
    });

    return `
        <div class="relative mb-2 item-select-wrapper" style="display: none;">
            <input type="text"
                class="item-search-input w-full border border-border-strong rounded-lg p-2 pr-10 bg-surface-base text-text-input focus:border-primary focus:ring-2 focus:ring-primary-light"
                placeholder="Cari barang..."
                autocomplete="off">
            <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

            <div class="item-dropdown absolute z-50 w-full bg-surface-base border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                <div class="item-options">${optionsHtml}</div>
                <div class="no-results p-4 text-center text-sm text-text-secondary hidden">
                    <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                    <p>Tidak ada barang ditemukan</p>
                </div>
            </div>
        </div>`;
}

function buildNewItemRowHtml() {
    return `
        <div class="item-row mb-3 p-3 border rounded bg-surface-secondary">
            <div class="flex items-center gap-2 mb-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="item-from-stock accent-primary">
                    <span class="text-sm">Dari Stok</span>
                </label>
            </div>

            ${buildItemDropdownHtml()}

            <input type="hidden" class="item-select-hidden">

            <input type="text" class="item-name w-full border border-border-strong rounded p-2 mb-2 bg-surface-base text-text-input" placeholder="Nama Barang *" required
                oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                oninput="this.setCustomValidity('')">

            <div class="grid grid-cols-2 gap-2">
                <input type="number" class="item-qty border border-border-strong rounded p-2 bg-surface-base text-text-input" placeholder="Qty *" required min="1" value="1"
                    oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
                <input type="number" class="item-capital border border-border-strong rounded p-2 bg-surface-base text-text-input" placeholder="Harga Modal *" required min="0"
                    oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>

            <button type="button" class="remove-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                <i class="fa-solid fa-trash"></i> Hapus Item
            </button>
        </div>`;
}

// ==========================================
// INITIALIZATION
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    // ------------------------------------------
    // Dynamic Items - Add Modal
    // ------------------------------------------

    const addItemBtn = document.getElementById('add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const itemsContainer = document.getElementById('items-list');
            const newItem = document.createElement('div');
            newItem.innerHTML = buildNewItemRowHtml();
            const newRow = newItem.firstElementChild;
            itemsContainer.appendChild(newRow);
            attachItemListeners(itemsContainer);
        });
    }

    const itemsContainer = document.getElementById('items-list');
    if (itemsContainer) {
        attachItemListeners(itemsContainer);
    }

    // ------------------------------------------
    // Dynamic Items - Edit Modals
    // ------------------------------------------

    document.querySelectorAll('[id^="items-list-"]').forEach(list => {
        attachItemListeners(list);
    });

    // ------------------------------------------
    // Form Submission - Add Modal
    // ------------------------------------------

    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const container = document.getElementById('items-list');
            if (container) {
                const items = serializeItemsData(container);
                document.getElementById('items-json').value = JSON.stringify(items);
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ------------------------------------------
    // Form Submission - Edit Modals
    // ------------------------------------------

    document.querySelectorAll('[id^="editModal-"] form').forEach(editForm => {
        editForm.addEventListener('submit', function (e) {
            const container = this.querySelector('[id^="items-list-"]');
            if (container) {
                const items = serializeItemsData(container);
                const hiddenInput = this.querySelector('[id^="items-json-"]');
                if (hiddenInput) {
                    hiddenInput.value = JSON.stringify(items);
                }
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ------------------------------------------
    // Select All / Bulk Delete Checkboxes
    // ------------------------------------------

    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_stock_ins[]"]');
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

});
