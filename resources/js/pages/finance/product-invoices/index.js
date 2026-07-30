/**
 * Invoice Barang - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Searchable dropdown untuk pemilihan barang (add & edit mode)
 * - Toggle "Dari Stok" (add & edit mode)
 * - Validasi harga modal < harga jual
 * - Validasi stok mencukupi
 * - Dynamic add/remove item rows
 * - Form submission dengan JSON items
 * - Select all checkbox untuk bulk delete
 */

/* global parseCurrencyInput, handleFormSubmit, resetFormSubmitState */

// ─── Currency Helper (compat with inline oninput handlers in Blade) ──────────

function formatCurrencyInput(input) {
    if (!input) return;
    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}
window.formatCurrencyInput = formatCurrencyInput;

// ─── Item Options Builder ─────────────────────────────────────────────────

function buildBarangOptionsHtml(prefix) {
    const items = window._itemsData || [];
    if (!Array.isArray(items) || items.length === 0) return '';

    const optionClass = prefix === '-edit' ? 'barang-option-edit' : 'barang-option';

    return items.map(function (item) {
        return '<div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light ' + optionClass + '" ' +
            'data-value="' + item.id_item + '" data-name="' + item.name_item + '" ' +
            'data-capital="' + item.capital_price + '" data-selling="' + item.selling_price + '" ' +
            'data-stock="' + item.quantity + '" data-search="' + String(item.name_item).toLowerCase() + '">' +
            '<div class="font-medium text-text-heading">' + item.name_item + '</div>' +
            '<div class="text-xs text-text-secondary mt-1">Stok: <span class="font-semibold text-primary">' + item.quantity + '</span> unit</div>' +
        '</div>';
    }).join('');
}

// ─── Submit Delete Form ──────────────────────────────────────────────────────

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

// ─── ADD MODE: Searchable Dropdown ──────────────────────────────────────────

function initSearchableDropdown(row) {
    const searchInput = row.querySelector('.barang-search-input');
    const dropdown = row.querySelector('.barang-dropdown');
    const options = row.querySelectorAll('.barang-option');
    const noResults = row.querySelector('.barang-no-results');
    const hiddenInput = row.querySelector('.barang-select-hidden');
    const nameInput = row.querySelector('.barang-item-name');
    const capitalInput = row.querySelector('.barang-item-capital');
    const sellingInput = row.querySelector('.barang-item-selling');

    if (!searchInput) return;

    searchInput.addEventListener('focus', function () {
        dropdown.classList.remove('hidden');
    });

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        let hasResults = false;

        options.forEach(function (option) {
            const searchText = option.dataset.search || '';
            if (searchText.includes(searchTerm)) {
                option.style.display = 'block';
                hasResults = true;
            } else {
                option.style.display = 'none';
            }
        });

        const itemOptionsDiv = row.querySelector('.barang-options');
        if (hasResults) {
            noResults.classList.add('hidden');
            itemOptionsDiv.classList.remove('hidden');
        } else {
            noResults.classList.remove('hidden');
            itemOptionsDiv.classList.add('hidden');
        }
    });

    options.forEach(function (option) {
        option.addEventListener('click', function () {
            const value = this.dataset.value;
            const name = this.dataset.name;
            const capital = this.dataset.capital;
            const selling = this.dataset.selling;
            const stock = this.dataset.stock || 0;

            searchInput.value = name || '';
            hiddenInput.value = value || '';
            row.dataset.stock = stock;

            if (value) {
                capitalInput.value = capital;
                sellingInput.value = selling;
                formatCurrencyInput(capitalInput);
                formatCurrencyInput(sellingInput);
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

// ─── ADD MODE: Dari Stok Toggle ─────────────────────────────────────────────

function toggleStockHandler() {
    const row = this.closest('.barang-item-row');
    const selectWrapper = row.querySelector('.barang-select-wrapper');
    const nameInput = row.querySelector('.barang-item-name');
    const capitalInput = row.querySelector('.barang-item-capital');
    const sellingInput = row.querySelector('.barang-item-selling');

    if (this.checked) {
        selectWrapper.style.display = 'block';
        nameInput.style.display = 'none';
        nameInput.value = '';
        nameInput.removeAttribute('required');
        capitalInput.readOnly = true;
        sellingInput.readOnly = true;
    } else {
        selectWrapper.style.display = 'none';
        const searchInput = row.querySelector('.barang-search-input');
        const hiddenInput = row.querySelector('.barang-select-hidden');
        if (searchInput) searchInput.value = '';
        if (hiddenInput) hiddenInput.value = '';
        nameInput.style.display = 'block';
        nameInput.setAttribute('required', '');
        capitalInput.readOnly = false;
        sellingInput.readOnly = false;
    }
}

// ─── ADD MODE: Remove Item ──────────────────────────────────────────────────

function removeItemHandler(e) {
    e.preventDefault();
    const itemsContainer = document.getElementById('barang-items-list-add');
    const remainingItems = itemsContainer.querySelectorAll('.barang-item-row');

    if (remainingItems.length <= 1) {
        alert('Minimal harus ada 1 item!');
        return;
    }

    this.closest('.barang-item-row').remove();
}

// ─── ADD MODE: Price Validation ─────────────────────────────────────────────

function initPriceValidation(row) {
    const capitalInput = row.querySelector('.barang-item-capital');
    const sellingInput = row.querySelector('.barang-item-selling');
    const priceWarning = row.querySelector('.barang-price-warning');
    const submitBtn = document.getElementById('submit-btn-addModal');

    if (!capitalInput || !sellingInput || !priceWarning) return;

    function validatePrices() {
        const capital = parseCurrencyInput(capitalInput.value);
        const selling = parseCurrencyInput(sellingInput.value);

        if (capital >= selling && selling > 0) {
            priceWarning.classList.remove('hidden');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            return false;
        } else {
            priceWarning.classList.add('hidden');
            const allValid = Array.from(document.querySelectorAll('.barang-item-row')).every(function (r) {
                const cap = parseCurrencyInput(r.querySelector('.barang-item-capital')?.value);
                const sel = parseCurrencyInput(r.querySelector('.barang-item-selling')?.value);
                return sel === 0 || cap < sel;
            });
            if (submitBtn && allValid) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }
    }

    capitalInput.addEventListener('input', validatePrices);
    sellingInput.addEventListener('input', validatePrices);
}

// ─── ADD MODE: Stock Validation ─────────────────────────────────────────────

function initStockValidation(row) {
    const qtyInput = row.querySelector('.barang-item-qty');
    const fromStockCheckbox = row.querySelector('.barang-from-stock');
    const stockWarning = row.querySelector('.barang-stock-warning');
    const submitBtn = document.getElementById('submit-btn-addModal');

    if (!qtyInput || !fromStockCheckbox || !stockWarning) return;

    function validateStock() {
        const isFromStock = fromStockCheckbox.checked;
        const qty = parseInt(qtyInput.value) || 0;
        const availableStock = parseInt(row.dataset.stock) || 0;

        if (isFromStock && availableStock > 0 && qty > availableStock) {
            stockWarning.classList.remove('hidden');
            const warningText = stockWarning.querySelector('.barang-stock-warning-text');
            if (warningText) {
                warningText.textContent =
                    'Stok tersedia: ' + availableStock + ' unit. Qty (' + qty + ') melebihi stok yang tersedia!';
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            return false;
        } else {
            stockWarning.classList.add('hidden');
            const allStockValid = Array.from(document.querySelectorAll('.barang-item-row')).every(function (r) {
                const check = r.querySelector('.barang-from-stock')?.checked;
                const q = parseInt(r.querySelector('.barang-item-qty')?.value) || 0;
                const s = parseInt(r.dataset.stock) || 0;
                return !check || s === 0 || q <= s;
            });

            const allPricesValid = Array.from(document.querySelectorAll('.barang-item-row')).every(function (r) {
                const cap = parseCurrencyInput(r.querySelector('.barang-item-capital')?.value);
                const sel = parseCurrencyInput(r.querySelector('.barang-item-selling')?.value);
                return sel === 0 || cap < sel;
            });

            if (submitBtn && allStockValid && allPricesValid) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }
    }

    qtyInput.addEventListener('input', validateStock);
    fromStockCheckbox.addEventListener('change', validateStock);
}

// ─── ADD MODE: Attach Listeners ─────────────────────────────────────────────

function attachItemListeners() {
    document.querySelectorAll('.remove-barang-item').forEach(function (btn) {
        btn.removeEventListener('click', removeItemHandler);
        btn.addEventListener('click', removeItemHandler);
    });

    document.querySelectorAll('.barang-from-stock').forEach(function (checkbox) {
        checkbox.removeEventListener('change', toggleStockHandler);
        checkbox.addEventListener('change', toggleStockHandler);
    });

    document.querySelectorAll('.barang-item-row').forEach(function (row) {
        initSearchableDropdown(row);
    });
}

// ─── EDIT MODE: Searchable Dropdown ─────────────────────────────────────────

function initSearchableDropdownEdit(row) {
    const searchInput = row.querySelector('.barang-search-input-edit');
    const dropdown = row.querySelector('.barang-dropdown-edit');
    const options = row.querySelectorAll('.barang-option-edit');
    const noResults = row.querySelector('.barang-no-results-edit');
    const hiddenInput = row.querySelector('.barang-select-hidden-edit');
    const nameInput = row.querySelector('.barang-item-name-edit');
    const capitalInput = row.querySelector('.barang-item-capital-edit');
    const sellingInput = row.querySelector('.barang-item-selling-edit');
    const idItemHidden = row.querySelector('.barang-id-item-hidden');

    if (!searchInput) return;

    searchInput.addEventListener('focus', function () {
        dropdown.classList.remove('hidden');
    });

    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        let hasResults = false;

        options.forEach(function (option) {
            const searchText = option.dataset.search || '';
            if (searchText.includes(searchTerm)) {
                option.style.display = 'block';
                hasResults = true;
            } else {
                option.style.display = 'none';
            }
        });

        const itemOptionsDiv = row.querySelector('.barang-options-edit');
        if (hasResults) {
            noResults.classList.add('hidden');
            itemOptionsDiv.classList.remove('hidden');
        } else {
            noResults.classList.remove('hidden');
            itemOptionsDiv.classList.add('hidden');
        }
    });

    options.forEach(function (option) {
        option.addEventListener('click', function () {
            const value = this.dataset.value;
            const name = this.dataset.name;
            const capital = this.dataset.capital;
            const selling = this.dataset.selling;
            const stock = this.dataset.stock || 0;

            searchInput.value = name || '';
            hiddenInput.value = value || '';

            if (value) {
                capitalInput.value = capital;
                sellingInput.value = selling;
                idItemHidden.value = value;
                row.dataset.stock = stock;

                formatCurrencyInput(capitalInput);
                formatCurrencyInput(sellingInput);
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

// ─── EDIT MODE: Dari Stok Toggle ────────────────────────────────────────────

function toggleEditStockHandler() {
    const row = this.closest('.barang-item-row-edit');
    const selectWrapper = row.querySelector('.barang-select-wrapper-edit');
    const nameInput = row.querySelector('.barang-item-name-edit');
    const capitalInput = row.querySelector('.barang-item-capital-edit');
    const sellingInput = row.querySelector('.barang-item-selling-edit');
    const fromStockHidden = row.querySelector('.barang-from-stock-hidden');
    const idItemHidden = row.querySelector('.barang-id-item-hidden');

    if (this.checked) {
        selectWrapper.style.display = 'block';
        nameInput.style.display = 'none';
        nameInput.value = '';
        nameInput.removeAttribute('required');
        capitalInput.readOnly = true;
        sellingInput.readOnly = true;
        fromStockHidden.value = 'true';
    } else {
        selectWrapper.style.display = 'none';
        const searchInput = row.querySelector('.barang-search-input-edit');
        const hiddenInput = row.querySelector('.barang-select-hidden-edit');
        if (searchInput) searchInput.value = '';
        if (hiddenInput) hiddenInput.value = '';
        nameInput.style.display = 'block';
        nameInput.value = '';
        nameInput.setAttribute('required', '');
        capitalInput.readOnly = false;
        sellingInput.readOnly = false;
        fromStockHidden.value = 'false';
        if (idItemHidden) idItemHidden.value = '';
    }
}

// ─── EDIT MODE: Price Validation ────────────────────────────────────────────

function initPriceValidationEdit(row, invoiceNumber) {
    const capitalInput = row.querySelector('.barang-item-capital-edit');
    const sellingInput = row.querySelector('.barang-item-selling-edit');
    const priceWarning = row.querySelector('.barang-price-warning-edit');
    const submitBtn = document.getElementById('submit-btn-editModal-' + invoiceNumber);

    if (!capitalInput || !sellingInput || !priceWarning) return;

    function validatePrices() {
        const capital = parseCurrencyInput(capitalInput.value) || 0;
        const selling = parseCurrencyInput(sellingInput.value) || 0;

        if (capital >= selling && selling > 0) {
            priceWarning.classList.remove('hidden');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            return false;
        } else {
            priceWarning.classList.add('hidden');
            const modalContainer = document.getElementById('barang-items-list-edit-' + invoiceNumber);
            if (modalContainer) {
                const allValid = Array.from(modalContainer.querySelectorAll('.barang-item-row-edit')).every(function (r) {
                    const cap = parseCurrencyInput(r.querySelector('.barang-item-capital-edit')?.value) || 0;
                    const sel = parseCurrencyInput(r.querySelector('.barang-item-selling-edit')?.value) || 0;
                    return sel === 0 || cap < sel;
                });
                if (submitBtn && allValid) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            return true;
        }
    }

    capitalInput.addEventListener('input', validatePrices);
    sellingInput.addEventListener('input', validatePrices);
}

// ─── EDIT MODE: Stock Validation ────────────────────────────────────────────

function initStockValidationEdit(row, invoiceNumber) {
    const qtyInput = row.querySelector('.barang-item-qty-edit');
    const fromStockCheckbox = row.querySelector('.barang-from-stock-edit');
    const stockWarning = row.querySelector('.barang-stock-warning-edit');
    const submitBtn = document.getElementById('submit-btn-editModal-' + invoiceNumber);

    if (!qtyInput || !fromStockCheckbox || !stockWarning) return;

    function validateStock() {
        const isFromStock = fromStockCheckbox.checked;
        const qty = parseInt(qtyInput.value) || 0;
        const availableStock = parseInt(row.dataset.stock) || 0;

        if (isFromStock && availableStock > 0 && qty > availableStock) {
            stockWarning.classList.remove('hidden');
            const warningText = stockWarning.querySelector('.barang-stock-warning-text-edit');
            if (warningText) {
                warningText.textContent =
                    'Stok tersedia: ' + availableStock + ' unit. Qty (' + qty + ') melebihi stok yang tersedia!';
            }
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            return false;
        } else {
            stockWarning.classList.add('hidden');
            const modalContainer = document.getElementById('barang-items-list-edit-' + invoiceNumber);
            if (modalContainer) {
                const allStockValid = Array.from(modalContainer.querySelectorAll('.barang-item-row-edit'))
                    .every(function (r) {
                        const check = r.querySelector('.barang-from-stock-edit')?.checked;
                        const q = parseInt(r.querySelector('.barang-item-qty-edit')?.value) || 0;
                        const s = parseInt(r.dataset.stock) || 0;
                        return !check || s === 0 || q <= s;
                    });

                const allPricesValid = Array.from(modalContainer.querySelectorAll('.barang-item-row-edit'))
                    .every(function (r) {
                        const cap = parseCurrencyInput(r.querySelector('.barang-item-capital-edit')?.value) || 0;
                        const sel = parseCurrencyInput(r.querySelector('.barang-item-selling-edit')?.value) || 0;
                        return sel === 0 || cap < sel;
                    });

                if (submitBtn && allStockValid && allPricesValid) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            return true;
        }
    }

    qtyInput.addEventListener('input', validateStock);
    fromStockCheckbox.addEventListener('change', validateStock);
}

// ─── EDIT MODE: Remove Item ─────────────────────────────────────────────────

function removeEditItemHandler(e) {
    e.preventDefault();
    const itemsContainer = this.closest('[id^="barang-items-list-edit-"]');
    const remainingItems = itemsContainer.querySelectorAll('.barang-item-row-edit');

    if (remainingItems.length <= 1) {
        alert('Minimal harus ada 1 item!');
        return;
    }

    this.closest('.barang-item-row-edit').remove();

    itemsContainer.querySelectorAll('.barang-item-row-edit').forEach(function (row, index) {
        row.querySelectorAll('input[name^="items"]').forEach(function (input) {
            const match = input.name.match(/\[(\w+)\]$/);
            if (match) {
                const fieldName = match[1];
                input.name = 'items[' + index + '][' + fieldName + ']';
            }
        });
    });
}

// ─── EDIT MODE: Attach Listeners ────────────────────────────────────────────

function attachEditListeners() {
    document.querySelectorAll('.remove-barang-item-edit').forEach(function (btn) {
        btn.removeEventListener('click', removeEditItemHandler);
        btn.addEventListener('click', removeEditItemHandler);
    });

    document.querySelectorAll('.barang-from-stock-edit').forEach(function (checkbox) {
        checkbox.removeEventListener('change', toggleEditStockHandler);
        checkbox.addEventListener('change', toggleEditStockHandler);
    });

    document.querySelectorAll('.barang-item-row-edit').forEach(function (row) {
        initSearchableDropdownEdit(row);
    });
}

// ─── ADD MODE: Add Item Button ──────────────────────────────────────────────

function initAddItemButton() {
    var addBtn = document.querySelector('.add-barang-item');
    if (!addBtn) return;

    addBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var itemsContainer = document.getElementById('barang-items-list-add');
        var newItem = document.createElement('div');
        newItem.className = 'barang-item-row mb-3 p-3 border rounded bg-surface-secondary';
        newItem.innerHTML =
            '<div class="flex items-center gap-2 mb-2">' +
                '<label class="flex items-center gap-2">' +
                    '<input type="checkbox" class="barang-from-stock accent-primary">' +
                    '<span class="text-sm">Dari Stok</span>' +
                '</label>' +
            '</div>' +
            '<div class="relative mb-2 barang-select-wrapper" style="display: none;">' +
                '<input type="text" class="barang-search-input w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light" placeholder="Cari barang..." autocomplete="off">' +
                '<i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>' +
                '<div class="barang-dropdown absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">' +
                    '<div class="barang-options">' +
                        '<div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b" data-value="">-- Pilih Barang --</div>' +
                        buildBarangOptionsHtml('') +
                    '</div>' +
                    '<div class="barang-no-results p-4 text-center text-sm text-text-secondary hidden">' +
                        '<i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>' +
                        '<p>Tidak ada barang ditemukan</p>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<input type="hidden" class="barang-select-hidden">' +
            '<input type="text" class="barang-item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required ' +
                'oninvalid="this.setCustomValidity(\'Nama barang tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">' +
            '<div class="grid grid-cols-3 gap-2">' +
                '<input type="number" class="barang-item-qty border rounded p-2" placeholder="Qty *" required min="1" value="1" ' +
                    'oninvalid="this.setCustomValidity(\'Qty tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">' +
                '<input type="text" inputmode="numeric" class="barang-item-capital border rounded p-2" placeholder="Rp 0" required ' +
                    'oninvalid="this.setCustomValidity(\'Harga modal tidak boleh kosong\')" oninput="formatCurrencyInput(this); this.setCustomValidity(\'\')">' +
                '<input type="text" inputmode="numeric" class="barang-item-selling border rounded p-2" placeholder="Rp 0" required ' +
                    'oninvalid="this.setCustomValidity(\'Harga jual tidak boleh kosong\')" oninput="formatCurrencyInput(this); this.setCustomValidity(\'\')">' +
            '</div>' +
            '<p class="barang-stock-warning text-error text-sm mt-2 hidden">' +
                '<span class="font-semibold">Peringatan Stok:</span> <span class="barang-stock-warning-text">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>' +
            '</p>' +
            '<p class="barang-price-warning text-error text-sm mt-2 hidden">' +
                '<span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!' +
            '</p>' +
            '<button type="button" class="remove-barang-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">' +
                '<i class="fa-solid fa-trash"></i> Hapus Item' +
            '</button>';

        itemsContainer.appendChild(newItem);
        attachItemListeners();
        initPriceValidation(newItem);
        initStockValidation(newItem);
    });
}

// ─── ADD MODE: Form Submission ──────────────────────────────────────────────

function initAddFormSubmission() {
    var addModal = document.getElementById('addModal');
    if (!addModal) return;

    var addForm = addModal.querySelector('form');
    if (!addForm) return;

    addForm.addEventListener('submit', function (e) {
        var items = [];
        var itemRows = document.querySelectorAll('.barang-item-row');

        var hasInvalidPrice = false;
        itemRows.forEach(function (row) {
            var capital = parseCurrencyInput(row.querySelector('.barang-item-capital')?.value);
            var selling = parseCurrencyInput(row.querySelector('.barang-item-selling')?.value);
            if (capital >= selling && selling > 0) {
                hasInvalidPrice = true;
            }
        });

        if (hasInvalidPrice) {
            e.preventDefault();
            alert('Harga modal tidak boleh lebih besar atau sama dengan harga jual!');
            return false;
        }

        itemRows.forEach(function (row) {
            var fromStockCheck = row.querySelector('.barang-from-stock');
            var hiddenSelect = row.querySelector('.barang-select-hidden');
            var itemName = row.querySelector('.barang-item-name').value;
            var qty = parseInt(row.querySelector('.barang-item-qty').value) || 0;
            var capital = parseCurrencyInput(row.querySelector('.barang-item-capital').value);
            var selling = parseCurrencyInput(row.querySelector('.barang-item-selling').value);

            if (qty > 0 && (itemName || (fromStockCheck.checked && hiddenSelect && hiddenSelect.value))) {
                items.push({
                    name_item: itemName,
                    quantity: qty,
                    capital_price: capital,
                    selling_price: selling,
                    from_stock: fromStockCheck.checked,
                    id_item: fromStockCheck.checked ? (hiddenSelect ? hiddenSelect.value : null) : null
                });
            }
        });

        if (items.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada 1 item dengan data lengkap!');
            return false;
        }

        document.getElementById('barang-items-json').value = JSON.stringify(items);

        var submitBtn = this.querySelector('button[type="submit"]');
        var originalText = submitBtn ? submitBtn.innerHTML : '';
        if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
            e.preventDefault();
            return false;
        }
    });
}

// ─── EDIT MODE: Add Item Button ─────────────────────────────────────────────

function initEditItemButtons() {
    document.querySelectorAll('.add-barang-item-edit').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var invoiceNumber = this.getAttribute('data-invoice-number');
            var itemsContainer = document.getElementById('barang-items-list-edit-' + invoiceNumber);
            var currentItems = itemsContainer.querySelectorAll('.barang-item-row-edit');
            var newIndex = currentItems.length;

            var newItem = document.createElement('div');
            newItem.className = 'barang-item-row-edit mb-3 p-3 border rounded bg-surface-secondary';
            newItem.setAttribute('data-index', newIndex);
            newItem.innerHTML =
                '<div class="flex items-center gap-2 mb-2">' +
                    '<label class="flex items-center gap-2">' +
                        '<input type="checkbox" class="barang-from-stock-edit accent-primary">' +
                        '<span class="text-sm">Dari Stok</span>' +
                    '</label>' +
                '</div>' +
                '<div class="relative mb-2 barang-select-wrapper-edit" style="display: none;">' +
                    '<input type="text" class="barang-search-input-edit w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light" placeholder="Cari barang..." autocomplete="off">' +
                    '<i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>' +
                    '<div class="barang-dropdown-edit absolute z-50 w-full bg-white border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">' +
                        '<div class="barang-options-edit">' +
                            '<div class="p-2 text-sm text-text-secondary hover:bg-surface-secondary cursor-pointer border-b" data-value="">-- Pilih Barang --</div>' +
                            buildBarangOptionsHtml('-edit') +
                        '</div>' +
                        '<div class="barang-no-results-edit p-4 text-center text-sm text-text-secondary hidden">' +
                            '<i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>' +
                            '<p>Tidak ada barang ditemukan</p>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<input type="hidden" class="barang-select-hidden-edit">' +
                '<input type="text" name="items[' + newIndex + '][name_item]" class="barang-item-name-edit w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required ' +
                    'oninvalid="this.setCustomValidity(\'Nama barang tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">' +
                '<div class="grid grid-cols-3 gap-2">' +
                    '<input type="number" name="items[' + newIndex + '][quantity]" class="barang-item-qty-edit border rounded p-2" placeholder="Qty *" required min="1" value="1" ' +
                        'oninvalid="this.setCustomValidity(\'Qty tidak boleh kosong\')" oninput="this.setCustomValidity(\'\')">' +
                    '<input type="text" inputmode="numeric" name="items[' + newIndex + '][capital_price]" class="barang-item-capital-edit border rounded p-2" placeholder="Rp 0" required value="Rp 0" ' +
                        'oninvalid="this.setCustomValidity(\'Harga modal tidak boleh kosong\')" oninput="formatCurrencyInput(this); this.setCustomValidity(\'\')">' +
                    '<input type="text" inputmode="numeric" name="items[' + newIndex + '][selling_price]" class="barang-item-selling-edit border rounded p-2" placeholder="Rp 0" required value="Rp 0" ' +
                        'oninvalid="this.setCustomValidity(\'Harga jual tidak boleh kosong\')" oninput="formatCurrencyInput(this); this.setCustomValidity(\'\')">' +
                '</div>' +
                '<p class="barang-price-warning-edit text-error text-sm mt-2 hidden">' +
                    '<span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!' +
                '</p>' +
                '<p class="barang-stock-warning-edit text-error text-sm mt-2 hidden">' +
                    '<span class="font-semibold">Peringatan Stok:</span> <span class="barang-stock-warning-text-edit">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>' +
                '</p>' +
                '<input type="hidden" name="items[' + newIndex + '][from_stock]" class="barang-from-stock-hidden" value="false">' +
                '<input type="hidden" name="items[' + newIndex + '][id_item]" class="barang-id-item-hidden" value="">' +
                '<button type="button" class="remove-barang-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">' +
                    '<i class="fa-solid fa-trash"></i> Hapus Item' +
                '</button>';

            itemsContainer.appendChild(newItem);
            attachEditListeners();
            initPriceValidationEdit(newItem, invoiceNumber);
            initStockValidationEdit(newItem, invoiceNumber);
        });
    });
}

// ─── EDIT MODE: Form Submission ─────────────────────────────────────────────

function initEditFormSubmissions() {
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var modal = this.closest('[id^="editModal-"]');
            var invoiceNumber = modal ? modal.id.replace('editModal-', '') : '';
            var itemsContainer = document.getElementById('barang-items-list-edit-' + invoiceNumber);

            if (itemsContainer) {
                var hasInvalidPrice = false;
                itemsContainer.querySelectorAll('.barang-item-row-edit').forEach(function (row) {
                    var capital = parseCurrencyInput(row.querySelector('.barang-item-capital-edit')?.value);
                    var selling = parseCurrencyInput(row.querySelector('.barang-item-selling-edit')?.value);
                    if (capital >= selling && selling > 0) {
                        hasInvalidPrice = true;
                    }
                });

                if (hasInvalidPrice) {
                    e.preventDefault();
                    alert('Harga modal tidak boleh lebih besar atau sama dengan harga jual!');
                    return false;
                }
            }

            var submitBtn = this.querySelector('button[type="submit"]');
            var originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText, 'Update...')) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ─── SELECT ALL CHECKBOX ────────────────────────────────────────────────────

function initSelectAllCheckbox() {
    var selectAllCheckbox = document.getElementById('selectAll');
    var invoiceCheckboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    var deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        var anyChecked = Array.from(invoiceCheckboxes).some(function (cb) { return cb.checked; });
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            invoiceCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButtonState();
        });
    }

    invoiceCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (!this.checked && selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }

            if (selectAllCheckbox) {
                var allChecked = Array.from(invoiceCheckboxes).every(function (cb) { return cb.checked; });
                selectAllCheckbox.checked = allChecked;
            }

            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();
}

// ─── INITIALIZATION ─────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    // Add mode
    attachItemListeners();
    document.querySelectorAll('.barang-item-row').forEach(function (row) {
        initSearchableDropdown(row);
        initPriceValidation(row);
        initStockValidation(row);
    });
    initAddItemButton();
    initAddFormSubmission();

    // Edit mode
    attachEditListeners();
    document.querySelectorAll('[id^="barang-items-list-edit-"]').forEach(function (container) {
        var invoiceNumber = container.id.replace('barang-items-list-edit-', '');
        container.querySelectorAll('.barang-item-row-edit').forEach(function (row) {
            initPriceValidationEdit(row, invoiceNumber);
            initStockValidationEdit(row, invoiceNumber);
        });
    });
    initEditItemButtons();
    initEditFormSubmissions();

    // Shared
    initSelectAllCheckbox();

    // Reset form submit state on page show (back/forward navigation)
    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
