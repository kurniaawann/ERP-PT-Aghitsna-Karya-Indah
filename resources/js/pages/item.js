/**
 * Data Barang - Page JavaScript
 *
 * Handles: price validation, bulk delete, select-all, print dropdown.
 */

/* global submitDeleteForm is called from inline onclick via Blade modal */
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

function validatePricePair(capitalInput, sellingInput, warningEl, submitBtn) {
    const capitalPrice = parseCurrencyInput(capitalInput.value);
    const sellingPrice = parseCurrencyInput(sellingInput.value);
    const isInvalid = capitalPrice >= sellingPrice && sellingPrice > 0;

    if (isInvalid) {
        warningEl.classList.remove('hidden');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    } else {
        warningEl.classList.add('hidden');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    return !isInvalid;
}

function bindCurrencyInput(input) {
    input.addEventListener('input', function () {
        this.value = formatRupiah(parseCurrencyInput(this.value));
    });
}

function bindPriceValidation(capitalInput, sellingInput, warningEl, submitBtn, form) {
    bindCurrencyInput(capitalInput);
    bindCurrencyInput(sellingInput);

    capitalInput.addEventListener('input', function () {
        validatePricePair(capitalInput, sellingInput, warningEl, submitBtn);
    });

    sellingInput.addEventListener('input', function () {
        validatePricePair(capitalInput, sellingInput, warningEl, submitBtn);
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!validatePricePair(capitalInput, sellingInput, warningEl, submitBtn)) {
                e.preventDefault();
                warningEl.classList.remove('hidden');
                warningEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            const submitBtnEl = form.querySelector('button[type="submit"]');
            const originalText = submitBtnEl.innerHTML;
            if (!handleFormSubmit(submitBtnEl, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // --- Add Modal Price Validation ---
    const addCapitalPrice = document.getElementById('add-capital-price');
    const addSellingPrice = document.getElementById('add-selling-price');
    const addPriceWarning = document.getElementById('add-price-warning');
    const addSubmitBtn = document.getElementById('submit-btn-addModal');
    const addForm = document.querySelector('#addModal form');

    if (addCapitalPrice && addSellingPrice) {
        bindPriceValidation(addCapitalPrice, addSellingPrice, addPriceWarning, addSubmitBtn, addForm);
    }

    // --- Edit Modal Price Validation ---
    const editCapitalPriceInputs = document.querySelectorAll('[id^="edit-capital-price-"]');

    editCapitalPriceInputs.forEach(function (capitalInput) {
        const itemId = capitalInput.id.replace('edit-capital-price-', '');
        const sellingInput = document.getElementById('edit-selling-price-' + itemId);
        const warningEl = document.getElementById('edit-price-warning-' + itemId);
        const editSubmitBtn = document.getElementById('submit-btn-editModal-' + itemId);
        const editForm = document.querySelector('#editModal-' + itemId + ' form');

        if (sellingInput) {
            bindPriceValidation(capitalInput, sellingInput, warningEl, editSubmitBtn, editForm);
        }
    });

    // --- Select All Checkbox ---
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('input[name="selected_items[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(itemCheckboxes).some(function (cb) {
            return cb.checked;
        });
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            itemCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateDeleteButtonState();
        });
    }

    itemCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(itemCheckboxes).every(function (cb) {
                    return cb.checked;
                });
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // --- Print Dropdown ---
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
    }
});
