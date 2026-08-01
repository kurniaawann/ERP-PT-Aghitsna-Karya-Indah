/**
 * Rekap Penjualan — Modular JavaScript
 *
 * Fitur:
 * - Toggle dropdown print
 * - Checkbox select all
 * - Toggle Dari Stok
 * - Dropdown yang dapat dicari (modal tambah & edit)
 * - Tambah/hapus item (modal tambah & edit)
 * - Validasi harga & stok
 * - Submit form dengan serialisasi JSON
 * - Filter auto-submit
 */

// ============================================================
// HELPER BERSAMA
// ============================================================

/**
 * Generate dropdown options HTML dari window._itemsData.
 * @param {string} prefix  '' untuk add modal, '-edit' untuk edit modal
 * @returns {string}
 */
function buildItemOptionsHtml(prefix = '') {
    const items = window._itemsData || [];
    if (!Array.isArray(items) || items.length === 0) return '';

    const optionClass = prefix === '-edit' ? 'item-option-edit' : 'item-option';
    const searchClass = prefix === '-edit' ? 'item-option-edit' : 'item-option';

    return items.map(item => `
        <div class="p-3 hover:bg-primary-light cursor-pointer border-b border-border-light ${optionClass}"
            data-value="${item.id_item}" data-name="${item.name_item}"
            data-capital="${item.capital_price}" data-selling="${item.selling_price}"
            data-stock="${item.quantity}" data-search="${String(item.name_item).toLowerCase()}">
            <div class="font-medium text-text-heading">${item.name_item}</div>
            <div class="text-xs text-text-secondary mt-1">
                Stok: <span class="font-semibold text-primary">${item.quantity}</span> unit
            </div>
        </div>
    `).join('');
}

/**
 * Parse input currency ke integer.
 * @param {string|number} value
 * @returns {number}
 */
function parseCurrencyInput(value) {
    const rawValue = String(value ?? '').replace(/[^0-9]/g, '');
    return rawValue ? parseInt(rawValue, 10) || 0 : 0;
}

/**
 * Format input currency ke format Indonesia (Rp X.XXX).
 * @param {HTMLInputElement} input
 */
function formatCurrencyInput(input) {
    if (!input) return;
    const numeric = String(input.value ?? '').replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}
window.formatCurrencyInput = formatCurrencyInput;

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

/**
 * Menangani submit form (status loading).
 * @param {HTMLButtonElement} submitBtn
 * @param {string} originalText
 * @param {string} loadingText
 * @returns {boolean}
 */
function handleFormSubmit(submitBtn, originalText, loadingText = 'Menyimpan...') {
    if (window._isSubmitting) return false;
    window._isSubmitting = true;
    if (submitBtn) {
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    return true;
}

/**
 * Mereset status submit form.
 */
function resetFormSubmitState() {
    window._isSubmitting = false;
    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.disabled = false;
        btn.classList.remove('opacity-70', 'cursor-not-allowed');
    });
}

// ============================================================
// INISIALISASI
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // CHECKBOX SELECT ALL
    // ============================================================

    const selectAllCheckbox = document.getElementById('selectAll');
    const saleCheckboxes = document.querySelectorAll('input[name="selected_sales[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(saleCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            saleCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    saleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(saleCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ============================================================
    // DROPDOWN YANG DAPAT DICARI — LOGIKA BERSAMA
    // ============================================================

    /**
     * Inisialisasi searchable dropdown untuk satu baris item.
     * @param {HTMLElement} row
     * @param {string} prefix  '' untuk add modal, '-edit' untuk edit modal
     */
    function initSearchableDropdownForRow(row, prefix = '') {
        const searchInput = row.querySelector(`.item-search-input${prefix}`);
        const dropdown = row.querySelector(`.item-dropdown${prefix}`);
        const options = row.querySelectorAll(`.item-option${prefix}`);
        const noResults = row.querySelector(`.no-results${prefix}`);
        const hiddenInput = row.querySelector(`.item-select-hidden${prefix}`);
        const nameInput = row.querySelector(`.item-name${prefix}`);
        const capitalInput = row.querySelector(`.item-capital${prefix}`);
        const sellingInput = row.querySelector(`.item-selling${prefix}`);
        const idItemHidden = row.querySelector('.id-item-hidden');

        if (!searchInput) return;

        // Tampilkan dropdown saat focus
        searchInput.addEventListener('focus', function () {
            dropdown.classList.remove('hidden');
        });

        // Fungsi pencarian
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

            const itemOptionsDiv = row.querySelector(`.item-options${prefix}`);
            if (hasResults) {
                noResults.classList.add('hidden');
                itemOptionsDiv.classList.remove('hidden');
            } else {
                noResults.classList.remove('hidden');
                itemOptionsDiv.classList.add('hidden');
            }
        });

        // Handle pemilihan opsi
        options.forEach(option => {
            option.addEventListener('click', function () {
                const value = this.dataset.value;
                const name = this.dataset.name;
                const capital = this.dataset.capital;
                const selling = this.dataset.selling;
                const stock = this.dataset.stock || 0;

                searchInput.value = name || '';
                hiddenInput.value = value || '';
                row.dataset.stock = stock;

                if (nameInput) nameInput.value = name || '';

                if (value) {
                    if (capitalInput) capitalInput.value = capital;
                    if (sellingInput) sellingInput.value = selling;
                    if (idItemHidden) idItemHidden.value = value;
                }

                dropdown.classList.add('hidden');
            });
        });

        // Tutup dropdown saat klik di luar
        document.addEventListener('click', function (e) {
            if (!row.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    // ============================================================
    // TOGGLE DARI STOK — MODAL TAMBAH
    // ============================================================

    function toggleStockHandler() {
        const row = this.closest('.item-row') || this.closest('.item-row-edit');
        const isEdit = row.classList.contains('item-row-edit');
        const prefix = isEdit ? '-edit' : '';

        const selectWrapper = row.querySelector(`.item-select-wrapper${prefix}`);
        const nameInput = row.querySelector(`.item-name${prefix}`);
        const capitalInput = row.querySelector(`.item-capital${prefix}`);
        const sellingInput = row.querySelector(`.item-selling${prefix}`);
        const fromStockHidden = row.querySelector('.from-stock-hidden');
        const idItemHidden = row.querySelector('.id-item-hidden');

        if (this.checked) {
            selectWrapper.style.display = 'block';
            nameInput.style.display = 'none';
            nameInput.value = '';
            nameInput.removeAttribute('required');
            capitalInput.readOnly = true;
            sellingInput.readOnly = true;
            if (fromStockHidden) fromStockHidden.value = 'true';
        } else {
            selectWrapper.style.display = 'none';
            const searchInput = row.querySelector(`.item-search-input${prefix}`);
            const hiddenInput = row.querySelector(`.item-select-hidden${prefix}`);
            if (searchInput) searchInput.value = '';
            if (hiddenInput) hiddenInput.value = '';
            nameInput.style.display = 'block';
            nameInput.value = '';
            nameInput.setAttribute('required', '');
            capitalInput.readOnly = false;
            sellingInput.readOnly = false;
            if (fromStockHidden) fromStockHidden.value = 'false';
            if (idItemHidden) idItemHidden.value = '';
        }
    }

    // ============================================================
    // HAPUS ITEM — MODAL TAMBAH
    // ============================================================

    function removeItemHandler(e) {
        e.preventDefault();
        const itemsContainer = document.getElementById('items-list');
        const remainingItems = itemsContainer.querySelectorAll('.item-row');

        if (remainingItems.length <= 1) {
            alert('Minimal harus ada 1 item!');
            return;
        }

        this.closest('.item-row').remove();
    }

    // ============================================================
    // HAPUS ITEM — MODAL EDIT
    // ============================================================

    function removeEditItemHandler(e) {
        e.preventDefault();
        const itemsContainer = this.closest('[id^="items-list-edit-"]');
        const remainingItems = itemsContainer.querySelectorAll('.item-row-edit');

        if (remainingItems.length <= 1) {
            alert('Minimal harus ada 1 item!');
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
    }

    // ============================================================
    // PASANG LISTENER — MODAL TAMBAH
    // ============================================================

    function attachItemListeners() {
        document.querySelectorAll('.remove-item').forEach(btn => {
            btn.removeEventListener('click', removeItemHandler);
            btn.addEventListener('click', removeItemHandler);
        });

        document.querySelectorAll('.item-from-stock').forEach(checkbox => {
            checkbox.removeEventListener('change', toggleStockHandler);
            checkbox.addEventListener('change', toggleStockHandler);
        });

        document.querySelectorAll('.item-row').forEach(row => {
            initSearchableDropdownForRow(row, '');
        });
    }

    // ============================================================
    // PASANG LISTENER — MODAL EDIT
    // ============================================================

    function attachEditRemoveListeners() {
        document.querySelectorAll('.remove-item-edit').forEach(btn => {
            btn.removeEventListener('click', removeEditItemHandler);
            btn.addEventListener('click', removeEditItemHandler);
        });
    }

    function attachEditStockListeners() {
        document.querySelectorAll('.item-from-stock-edit').forEach(checkbox => {
            checkbox.removeEventListener('change', toggleStockHandler);
            checkbox.addEventListener('change', toggleStockHandler);
        });
    }

    // ============================================================
    // VALIDASI HARGA — MODAL TAMBAH
    // ============================================================

    function initPriceValidation(row) {
        const capitalInput = row.querySelector('.item-capital');
        const sellingInput = row.querySelector('.item-selling');
        const priceWarning = row.querySelector('.price-warning');
        const submitBtn = document.getElementById('submit-btn-addModal');

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
                const allValid = Array.from(document.querySelectorAll('.item-row')).every(r => {
                    const cap = parseCurrencyInput(r.querySelector('.item-capital')?.value);
                    const sel = parseCurrencyInput(r.querySelector('.item-selling')?.value);
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

    // ============================================================
    // VALIDASI HARGA — MODAL EDIT
    // ============================================================

    function initPriceValidationEdit(row, saleId) {
        const capitalInput = row.querySelector('.item-capital-edit');
        const sellingInput = row.querySelector('.item-selling-edit');
        const priceWarning = row.querySelector('.price-warning-edit');
        const submitBtn = document.getElementById('submit-btn-editModal-' + saleId);

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
                const modalContainer = document.getElementById('items-list-edit-' + saleId);
                if (modalContainer) {
                    const allValid = Array.from(modalContainer.querySelectorAll('.item-row-edit')).every(r => {
                        const cap = parseCurrencyInput(r.querySelector('.item-capital-edit')?.value) || 0;
                        const sel = parseCurrencyInput(r.querySelector('.item-selling-edit')?.value) || 0;
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

    // ============================================================
    // VALIDASI STOK — MODAL TAMBAH
    // ============================================================

    function initStockValidation(row) {
        const qtyInput = row.querySelector('.item-qty');
        const fromStockCheckbox = row.querySelector('.item-from-stock');
        const stockWarning = row.querySelector('.stock-warning');
        const submitBtn = document.getElementById('submit-btn-addModal');

        if (!qtyInput || !fromStockCheckbox || !stockWarning) return;

        function validateStock() {
            const isFromStock = fromStockCheckbox.checked;
            const qty = parseInt(qtyInput.value) || 0;
            const availableStock = parseInt(row.dataset.stock) || 0;

            if (isFromStock && availableStock > 0 && qty > availableStock) {
                stockWarning.classList.remove('hidden');
                const warningText = stockWarning.querySelector('.stock-warning-text');
                if (warningText) {
                    warningText.textContent = `Stok tersedia: ${availableStock} unit. Qty (${qty}) melebihi stok yang tersedia!`;
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            } else {
                stockWarning.classList.add('hidden');
                const allStockValid = Array.from(document.querySelectorAll('.item-row')).every(r => {
                    const check = r.querySelector('.item-from-stock')?.checked;
                    const q = parseInt(r.querySelector('.item-qty')?.value) || 0;
                    const s = parseInt(r.dataset.stock) || 0;
                    return !check || s === 0 || q <= s;
                });
                const allPricesValid = Array.from(document.querySelectorAll('.item-row')).every(r => {
                    const cap = parseCurrencyInput(r.querySelector('.item-capital')?.value);
                    const sel = parseCurrencyInput(r.querySelector('.item-selling')?.value);
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

    // ============================================================
    // VALIDASI STOK — MODAL EDIT
    // ============================================================

    function initStockValidationEdit(row, saleId) {
        const qtyInput = row.querySelector('.item-qty-edit');
        const fromStockCheckbox = row.querySelector('.item-from-stock-edit');
        const stockWarning = row.querySelector('.stock-warning-edit');
        const submitBtn = document.getElementById('submit-btn-editModal-' + saleId);

        if (!qtyInput || !fromStockCheckbox || !stockWarning) return;

        function validateStock() {
            const isFromStock = fromStockCheckbox.checked;
            const qty = parseInt(qtyInput.value) || 0;
            const availableStock = parseInt(row.dataset.stock) || 0;

            if (isFromStock && availableStock > 0 && qty > availableStock) {
                stockWarning.classList.remove('hidden');
                const warningText = stockWarning.querySelector('.stock-warning-text-edit');
                if (warningText) {
                    warningText.textContent = `Stok tersedia: ${availableStock} unit. Qty (${qty}) melebihi stok yang tersedia!`;
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            } else {
                stockWarning.classList.add('hidden');
                const modalContainer = document.getElementById('items-list-edit-' + saleId);
                if (modalContainer) {
                    const allStockValid = Array.from(modalContainer.querySelectorAll('.item-row-edit')).every(r => {
                        const check = r.querySelector('.item-from-stock-edit')?.checked;
                        const q = parseInt(r.querySelector('.item-qty-edit')?.value) || 0;
                        const s = parseInt(r.dataset.stock) || 0;
                        return !check || s === 0 || q <= s;
                    });
                    const allPricesValid = Array.from(modalContainer.querySelectorAll('.item-row-edit')).every(r => {
                        const cap = parseCurrencyInput(r.querySelector('.item-capital-edit')?.value) || 0;
                        const sel = parseCurrencyInput(r.querySelector('.item-selling-edit')?.value) || 0;
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

    // ============================================================
    // INISIALISASI — MODAL TAMBAH
    // ============================================================

    attachItemListeners();

    document.querySelectorAll('.item-row').forEach(row => {
        initSearchableDropdownForRow(row, '');
        initPriceValidation(row);
        initStockValidation(row);
    });

    // Tombol Tambah Item (Add Modal)
    const addItemBtn = document.getElementById('add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const itemsContainer = document.getElementById('items-list');
            const newItem = document.createElement('div');
            newItem.className = 'item-row mb-3 p-3 border rounded bg-gray-50';
            newItem.innerHTML = `
                <div class="flex items-center gap-2 mb-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="item-from-stock accent-primary">
                        <span class="text-sm">Dari Stok</span>
                    </label>
                </div>

                <div class="relative mb-2 item-select-wrapper" style="display: none;">
                    <input type="text"
                        class="item-search-input w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
                        placeholder="Cari barang..." autocomplete="off">
                    <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

                    <div class="item-dropdown absolute z-50 w-full bg-surface-base border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                        <div class="item-options">
                            <div class="p-2 text-sm text-text-secondary hover:bg-gray-50 cursor-pointer border-b" data-value="">
                                -- Pilih Barang --
                            </div>
                            ${buildItemOptionsHtml('')}
                        </div>
                        <div class="no-results p-4 text-center text-sm text-text-secondary hidden">
                            <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                            <p>Tidak ada barang ditemukan</p>
                        </div>
                    </div>
                </div>

                <input type="hidden" class="item-select-hidden">

                <input type="text" class="item-name w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required
                    oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">

                <div class="grid grid-cols-3 gap-2">
                    <input type="number" class="item-qty border rounded p-2" placeholder="Qty *" required min="1" value="1"
                        oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" class="item-capital border rounded p-2" placeholder="Rp 0" required
                        oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" class="item-selling border rounded p-2" placeholder="Rp 0" required
                        oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                </div>

                <p class="stock-warning text-error text-sm mt-2 hidden">
                    <span class="font-semibold">Peringatan Stok:</span> <span class="stock-warning-text">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>
                </p>

                <p class="price-warning text-error text-sm mt-2 hidden">
                    <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!
                </p>

                <button type="button" class="remove-item mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                    <i class="fa-solid fa-trash"></i> Hapus Item
                </button>
            `;
            itemsContainer.appendChild(newItem);
            attachItemListeners();
            initPriceValidation(newItem);
            initStockValidation(newItem);
        });
    }

    // ============================================================
    // INISIALISASI — MODAL EDIT
    // ============================================================

    attachEditRemoveListeners();
    attachEditStockListeners();

    document.querySelectorAll('.item-row-edit').forEach(row => {
        initSearchableDropdownForRow(row, '-edit');
    });

    document.querySelectorAll('[id^="items-list-edit-"]').forEach(container => {
        const saleId = container.id.replace('items-list-edit-', '');
        container.querySelectorAll('.item-row-edit').forEach(row => {
            initPriceValidationEdit(row, saleId);
            initStockValidationEdit(row, saleId);
        });
    });

    // Tombol Tambah Item (Edit Modal)
    document.querySelectorAll('.add-item-edit').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const saleId = this.getAttribute('data-sale-id');
            const itemsContainer = document.getElementById('items-list-edit-' + saleId);
            const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
            const newIndex = currentItems.length;

            const newItem = document.createElement('div');
            newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-gray-50';
            newItem.setAttribute('data-index', newIndex);
            newItem.innerHTML = `
                <div class="flex items-center gap-2 mb-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="item-from-stock-edit accent-primary">
                        <span class="text-sm">Dari Stok</span>
                    </label>
                </div>

                <div class="relative mb-2 item-select-wrapper-edit" style="display: none;">
                    <input type="text"
                        class="item-search-input-edit w-full border rounded-lg p-2 pr-10 focus:border-primary focus:ring-2 focus:ring-primary-light"
                        placeholder="Cari barang..." autocomplete="off">
                    <i class="fa-solid fa-search absolute right-3 top-3 text-text-tertiary pointer-events-none"></i>

                    <div class="item-dropdown-edit absolute z-50 w-full bg-surface-base border border-border-strong rounded-lg shadow-lg mt-1 max-h-64 overflow-y-auto hidden">
                        <div class="item-options-edit">
                            <div class="p-2 text-sm text-text-secondary hover:bg-gray-50 cursor-pointer border-b" data-value="">
                                -- Pilih Barang --
                            </div>
                            ${buildItemOptionsHtml('-edit')}
                        </div>
                        <div class="no-results-edit p-4 text-center text-sm text-text-secondary hidden">
                            <i class="fa-solid fa-search mb-2 text-2xl text-text-placeholder"></i>
                            <p>Tidak ada barang ditemukan</p>
                        </div>
                    </div>
                </div>

                <input type="hidden" class="item-select-hidden-edit">

                <input type="text" name="items[${newIndex}][name_item]"
                    class="item-name-edit w-full border rounded p-2 mb-2" placeholder="Nama Barang *" required
                    oninvalid="this.setCustomValidity('Nama barang tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">

                <div class="grid grid-cols-3 gap-2">
                    <input type="number" name="items[${newIndex}][quantity]"
                        class="item-qty-edit border rounded p-2" placeholder="Qty *" required min="1" value="1"
                        oninvalid="this.setCustomValidity('Qty tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" name="items[${newIndex}][capital_price]"
                        class="item-capital-edit border rounded p-2" placeholder="Rp 0" required value="Rp 0"
                        oninvalid="this.setCustomValidity('Harga modal tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" name="items[${newIndex}][selling_price]"
                        class="item-selling-edit border rounded p-2" placeholder="Rp 0" required value="Rp 0"
                        oninvalid="this.setCustomValidity('Harga jual tidak boleh kosong')"
                        oninput="formatCurrencyInput(this); this.setCustomValidity('')">
                </div>

                <p class="price-warning-edit text-error text-sm mt-2 hidden">
                    <span class="font-semibold">Peringatan:</span> Harga modal tidak boleh lebih besar atau sama dengan harga jual!
                </p>

                <p class="stock-warning-edit text-error text-sm mt-2 hidden">
                    <span class="font-semibold">Peringatan Stok:</span> <span class="stock-warning-text-edit">Stok Barang Tidak Cukup! Silahkan Sesuaikan Dengan Stok Yang Tersedia.</span>
                </p>

                <input type="hidden" name="items[${newIndex}][from_stock]" class="from-stock-hidden" value="false">
                <input type="hidden" name="items[${newIndex}][id_item]" class="id-item-hidden" value="">

                <button type="button"
                    class="remove-item-edit mt-2 bg-btn-delete text-white px-3 py-1 rounded hover:bg-btn-delete-hover w-full">
                    <i class="fa-solid fa-trash"></i> Hapus Item
                </button>
            `;
            itemsContainer.appendChild(newItem);
            attachEditRemoveListeners();
            attachEditStockListeners();
            initSearchableDropdownForRow(newItem, '-edit');
            initPriceValidationEdit(newItem, saleId);
            initStockValidationEdit(newItem, saleId);
        });
    });

    // ============================================================
    // SUBMIT FORM — MODAL TAMBAH
    // ============================================================

    const addModal = document.getElementById('addModal');
    if (addModal) {
        const addForm = addModal.querySelector('form');
        if (addForm) {
            addForm.addEventListener('submit', function (e) {
                const items = [];
                const itemRows = document.querySelectorAll('.item-row');

                // Validasi harga
                let hasInvalidPrice = false;
                itemRows.forEach(row => {
                    const capital = parseCurrencyInput(row.querySelector('.item-capital')?.value);
                    const selling = parseCurrencyInput(row.querySelector('.item-selling')?.value);
                    if (capital >= selling && selling > 0) {
                        hasInvalidPrice = true;
                    }
                });

                if (hasInvalidPrice) {
                    e.preventDefault();
                    alert('Harga modal tidak boleh lebih besar atau sama dengan harga jual!');
                    return false;
                }

                // Serialisasi items ke JSON
                itemRows.forEach(row => {
                    const fromStockCheck = row.querySelector('.item-from-stock');
                    const hiddenSelect = row.querySelector('.item-select-hidden');
                    const searchInput = row.querySelector('.item-search-input');
                    const nameInput = row.querySelector('.item-name');
                    const itemName = (fromStockCheck.checked && searchInput && searchInput.value)
                        ? searchInput.value
                        : nameInput.value;
                    const qty = parseInt(row.querySelector('.item-qty').value) || 0;
                    const capital = parseCurrencyInput(row.querySelector('.item-capital').value);
                    const selling = parseCurrencyInput(row.querySelector('.item-selling').value);

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

                document.getElementById('items-json').value = JSON.stringify(items);

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.innerHTML : '';
                if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    }

    // ============================================================
    // SUBMIT FORM — MODAL EDIT
    // ============================================================

    const editForms = document.querySelectorAll('[id^="editModal-"] form');
    editForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const modal = this.closest('[id^="editModal-"]');
            const saleId = modal ? modal.id.replace('editModal-', '') : '';
            const itemsContainer = document.getElementById('items-list-edit-' + saleId);

            // Validasi harga
            if (itemsContainer) {
                let hasInvalidPrice = false;
                itemsContainer.querySelectorAll('.item-row-edit').forEach(row => {
                    const capital = parseCurrencyInput(row.querySelector('.item-capital-edit')?.value);
                    const selling = parseCurrencyInput(row.querySelector('.item-selling-edit')?.value);
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

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText, 'Update...')) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ============================================================
    // FILTER AUTO-SUBMIT
    // ============================================================

    const monthSelect = document.getElementById('month-select');
    const yearSelect = document.getElementById('year-select');
    const statusSelect = document.getElementById('status-select');
    const filterForm = monthSelect ? monthSelect.closest('form') : null;

    if (monthSelect && filterForm) {
        monthSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (yearSelect && filterForm) {
        yearSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (statusSelect && filterForm) {
        statusSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    // ============================================================
    // RESET STATUS SUBMIT SAAT HALAMAN DITAMPILKAN
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
