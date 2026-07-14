/* global parseCurrencyInput, handleFormSubmit, resetFormSubmitState */

// ==========================================
// ALUMUNIUM-SPECIFIC PARSERS
// ==========================================

/**
 * Parse currency input handling Indonesian format:
 * - "1.000" => 1000 (dots as thousand separators)
 * - "1,5" => 1.5 (comma as decimal separator)
 * - "Rp 1.000" => 1000
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
 * Format input value as Indonesian currency (no "Rp" prefix).
 */
function formatCurrencyInput(input) {
    const str = String(input.value ?? '').trim();
    if (!str) return;

    const num = parseCurrencyInput(str);
    input.value = num ? Math.round(num).toLocaleString('id-ID') : '';
}

function parseDecimalInput(inputElement) {
    const rawValue = String(inputElement?.value ?? '').trim();
    if (!rawValue) return 0;
    return parseFloat(rawValue.replace(',', '.')) || 0;
}

function normalizeInvoicePriceFields(form) {
    form.querySelectorAll('input[name*="[harga]"]').forEach(input => {
        const value = parseCurrencyInput(input.value);
        input.value = value ? String(value) : '0';
    });
}

// Expose to window for Blade inline handlers
window.parseCurrencyInput = parseCurrencyInput;
window.formatCurrencyInput = formatCurrencyInput;

// ==========================================
// LIVE CALCULATION FUNCTIONS
// ==========================================

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

function calculateEditRowTotal(input) {
    const row = input.closest('.item-row-edit');
    const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
    const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
    const total = volume * harga;

    const totalSpan = row.querySelector('.item-total');
    if (totalSpan) {
        totalSpan.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }

    updateEditInvoiceTotal(input);
}

window.calculateRowTotal = calculateRowTotal;
window.calculateEditRowTotal = calculateEditRowTotal;

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

    const wordsElement = document.getElementById('invoice-total-words');
    if (wordsElement && grandTotal > 0) {
        wordsElement.textContent = 'Terbilang: ' + numberToWords(grandTotal) + ' rupiah';
    } else if (wordsElement) {
        wordsElement.textContent = '';
    }

    calculateDiscount();
}

function updateEditInvoiceTotal(input) {
    const modal = input.closest('[id^="editModal-"]');
    if (!modal) return;

    const invoiceId = modal.id.replace('editModal-', '');
    let grandTotal = 0;

    modal.querySelectorAll('.item-row-edit').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        grandTotal += (volume * harga);
    });

    const totalPreview = document.getElementById('invoice-total-preview-edit-' + invoiceId);
    if (totalPreview) {
        totalPreview.textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    }

    calculateDiscountEdit(invoiceId);
}

// ==========================================
// DISCOUNT & DP CALCULATIONS
// ==========================================

function calculateDiscount() {
    const discountType = document.getElementById('discount-type')?.value;
    const discountValueInput = document.getElementById('discount-value');
    let discountValue = parseDecimalInput(discountValueInput);
    const discountError = document.getElementById('discount-error');

    if (discountValueInput) {
        if (!discountType) {
            discountValueInput.value = '';
            discountValue = 0;
        }
    }

    if (discountType === 'percentage' && discountValue > 100) {
        discountValue = 100;
        if (discountValueInput) discountValueInput.value = 100;
        if (discountError) {
            discountError.classList.remove('hidden');
            setTimeout(() => discountError.classList.add('hidden'), 3000);
        }
    } else {
        if (discountError) discountError.classList.add('hidden');
    }

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

    const discountAmountEl = document.getElementById('discount-amount');
    const totalAfterDiscountEl = document.getElementById('total-after-discount');
    if (discountAmountEl) discountAmountEl.textContent = 'Rp ' + discountAmount.toLocaleString('id-ID');
    if (totalAfterDiscountEl) totalAfterDiscountEl.textContent = 'Rp ' + totalAfterDiscount.toLocaleString('id-ID');

    calculateDP();
}

function calculateDP() {
    const dpType = document.getElementById('dp-type')?.value;
    const dpValueInput = document.getElementById('dp-value');
    let dpValue = parseDecimalInput(dpValueInput);
    const dpError = document.getElementById('dp-error');

    if (dpValueInput) {
        if (!dpType) {
            dpValueInput.value = '';
            dpValue = 0;
        }
    }

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

    let baseTotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const volume = parseFloat(row.querySelector('.item-volume')?.value) || 0;
        const harga = parseCurrencyInput(row.querySelector('.item-harga')?.value);
        baseTotal += (volume * harga);
    });
    baseTotal = Math.round(baseTotal);

    const discountType = document.getElementById('discount-type')?.value;
    const discountValue = parseDecimalInput(document.getElementById('discount-value'));

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

    const dpAmountEl = document.getElementById('dp-amount');
    if (dpAmountEl) dpAmountEl.textContent = 'Rp ' + dpAmount.toLocaleString('id-ID');
}

window.calculateDiscount = calculateDiscount;
window.calculateDP = calculateDP;

function calculateDiscountEdit(invoiceNumber) {
    const typeEl = document.getElementById('discount-type-edit-' + invoiceNumber);
    const valueEl = document.getElementById('discount-value-edit-' + invoiceNumber);
    const discountType = typeEl?.value;
    let discountValue = parseDecimalInput(valueEl);

    if (valueEl) {
        if (!discountType) {
            valueEl.value = 0;
            discountValue = 0;
        }
    }

    if (discountType === 'percentage' && discountValue > 100) {
        discountValue = 100;
        if (valueEl) valueEl.value = 100;
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

    calculateDPEdit(invoiceNumber);
}

function calculateDPEdit(invoiceNumber) {
    const typeEl = document.getElementById('dp-type-edit-' + invoiceNumber);
    const valueEl = document.getElementById('dp-value-edit-' + invoiceNumber);
    const dpType = typeEl?.value;
    let dpValue = parseDecimalInput(valueEl);

    if (valueEl) {
        if (!dpType) {
            valueEl.value = 0;
            dpValue = 0;
        }
    }

    if (dpType === 'percentage' && dpValue > 100) {
        dpValue = 100;
        if (valueEl) valueEl.value = 100;
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
    const discountValue = parseDecimalInput(document.getElementById('discount-value-edit-' + invoiceNumber));

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

window.calculateDiscountEdit = calculateDiscountEdit;
window.calculateDPEdit = calculateDPEdit;

// ==========================================
// NUMBER TO WORDS
// ==========================================

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
// PAYMENT ACCOUNT VALIDATION
// ==========================================

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

function validatePaymentSelectionEdit(invoiceNumber) {
    const modal = document.getElementById('editModal-' + invoiceNumber);
    const checkboxes = modal?.querySelectorAll('.payment-account-checkbox-edit') ?? [];
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
    if (form) form.submit();
}

window.submitDeleteForm = submitDeleteForm;

// ==========================================
// DOM READY
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    // SELECT ALL CHECKBOX
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

    // ADD MODAL - ADD ITEM
    const addItemBtn = document.getElementById('add-item');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const itemsContainer = document.getElementById('items-list');
            const newItem = document.createElement('div');
            newItem.className = 'item-row mb-3 p-3 border rounded bg-gray-50';
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                        oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" class="item-volume border rounded p-2 w-full" placeholder="Volume *" required oninput="calculateRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" class="item-satuan border rounded p-2 w-full" placeholder="Satuan (m3, unit) *" required
                        oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" min="0" class="item-harga border rounded p-2 w-full" placeholder="Harga *" required oninput="formatCurrencyInput(this); calculateRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <button type="button" class="remove-item bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
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

    // Format existing harga inputs in edit modals
    document.querySelectorAll('[id^="editModal-"] .item-harga').forEach(input => {
        if (input.value) formatCurrencyInput(input);
    });

    // EDIT MODAL - ADD ITEM
    document.querySelectorAll('.add-item-edit').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const invoiceId = this.getAttribute('data-invoice-id');
            const itemsContainer = document.getElementById('items-list-edit-' + invoiceId);
            const currentItems = itemsContainer.querySelectorAll('.item-row-edit');
            const newIndex = currentItems.length;

            const newItem = document.createElement('div');
            newItem.className = 'item-row-edit mb-3 p-3 border rounded bg-gray-50';
            newItem.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-2">
                    <input type="text" name="items[${newIndex}][keterangan]"
                        class="item-keterangan border rounded p-2 w-full" placeholder="Keterangan *" required
                        oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="number" step="0.01" min="0" name="items[${newIndex}][volume]"
                        class="item-volume border rounded p-2 w-full" placeholder="Volume *" required oninput="calculateEditRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Volume tidak boleh kosong')">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <input type="text" name="items[${newIndex}][satuan]"
                        class="item-satuan border rounded p-2 w-full" placeholder="Satuan *" required
                        oninvalid="this.setCustomValidity('Satuan tidak boleh kosong')"
                        oninput="this.setCustomValidity('')">
                    <input type="text" inputmode="numeric" min="0" name="items[${newIndex}][harga]"
                        class="item-harga border rounded p-2 w-full" placeholder="Harga *" required oninput="formatCurrencyInput(this); calculateEditRowTotal(this); this.setCustomValidity('')"
                        oninvalid="this.setCustomValidity('Harga tidak boleh kosong')">
                    <div class="flex items-center">
                        <span class="item-total text-sm font-semibold text-primary">Rp 0</span>
                    </div>
                    <button type="button" class="remove-item-edit bg-btn-delete text-white px-2 py-2 rounded hover:bg-btn-delete-hover">
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

        // Re-index items
        itemsContainer.querySelectorAll('.item-row-edit').forEach((row, index) => {
            row.querySelectorAll('input[name^="items"]').forEach(input => {
                const fieldName = input.name.match(/\[(\w+)\]$/)[1];
                input.name = `items[${index}][${fieldName}]`;
            });
        });

        const firstInput = itemsContainer.querySelector('.item-volume');
        if (firstInput) updateEditInvoiceTotal(firstInput);
    }

    attachRemoveListenerEdit();

    // FORM SUBMIT - ADD MODAL
    const addModalElement = document.getElementById('addModal');
    if (addModalElement) {
        const addForm = addModalElement.querySelector('form');
        if (addForm) {
            addForm.addEventListener('submit', function (e) {
                const submitBtn = this.querySelector('button[type="submit"]');

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

                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }

                return true;
            });
        }
    }

    // FORM SUBMIT - EDIT MODALS
    document.querySelectorAll('form[action*="alumunium-invoice"]').forEach(form => {
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

                if (!handleFormSubmit(submitBtn)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });

    // INITIALIZE TOTALS
    updateInvoiceTotal();

    document.querySelectorAll('[id^="discount-type-edit-"]').forEach(el => {
        const invoiceNumber = el.id.replace('discount-type-edit-', '');
        calculateDiscountEdit(invoiceNumber);
    });

    // PAYMENT ACCOUNT BUTTON STATES
    validatePaymentSelection();

    document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
        const invoiceNumber = modal.id.replace('editModal-', '');
        validatePaymentSelectionEdit(invoiceNumber);

        modal.querySelectorAll('.payment-account-checkbox-edit').forEach(cb => {
            cb.addEventListener('change', () => validatePaymentSelectionEdit(invoiceNumber));
        });
    });

    // FILTER URL HANDLING
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

    // RESET SUBMIT STATE ON PAGE SHOW
    window.addEventListener('pageshow', () => resetFormSubmitState());
});
