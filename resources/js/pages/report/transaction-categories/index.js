/**
 * Kategori Transaksi — Modular JavaScript
 *
 * Fitur:
 * - Bulk delete dengan pengecekan kategori yang sedang digunakan
 * - Toggle status aktif/nonaktif kategori
 * - Validasi kode duplikat (client-side) untuk modal tambah
 * - Validasi kode duplikat (client-side) untuk modal edit
 * - Form submission handling (prevent double submit)
 * - Select all checkbox
 */

// ============================================================
// BULK DELETE — CHECK USED CATEGORIES
// ============================================================

/**
 * Mengecek apakah ada kategori yang sedang digunakan sebelum menampilkan
 * modal konfirmasi hapus. Jika ada, tampilkan modal peringatan.
 */
function checkAndDelete() {
    const checkboxes = document.querySelectorAll('.category-checkbox:checked');
    const usedCategories = [];

    checkboxes.forEach(cb => {
        if (cb.dataset.isUsed === 'true') {
            usedCategories.push(cb.dataset.categoryName);
        }
    });

    if (usedCategories.length > 0) {
        showWarningModal(usedCategories);
    } else {
        openModal('deleteModal');
    }
}
window.checkAndDelete = checkAndDelete;

/**
 * Menampilkan modal peringatan untuk kategori yang sedang digunakan.
 * @param {string[]} usedCategories Array nama kategori yang sedang digunakan
 */
function showWarningModal(usedCategories) {
    const modal = document.getElementById('warningUsedModal');
    const list = document.getElementById('usedCategoriesList');
    const modalContent = modal.querySelector('.bg-surface-base');

    list.innerHTML = '';

    usedCategories.forEach(categoryName => {
        const li = document.createElement('li');
        li.innerHTML = `<strong>${categoryName}</strong>`;
        li.classList.add('font-medium');
        list.appendChild(li);
    });

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

/**
 * Menutup modal peringatan kategori yang sedang digunakan.
 */
function closeWarningModal() {
    const modal = document.getElementById('warningUsedModal');
    const modalContent = modal.querySelector('.bg-surface-base');

    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 300);
}
window.closeWarningModal = closeWarningModal;

/**
 * Submit form bulk delete dengan loading indicator.
 */
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

// ============================================================
// TOGGLE STATUS
// ============================================================

/**
 * Mengubah status aktif/nonaktif kategori menggunakan temporary form
 * dengan method spoofing PATCH.
 * @param {number} categoryId ID kategori yang akan di-toggle
 */
function toggleStatus(categoryId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/transaction-category/${categoryId}/toggle-status`;

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = window.csrfToken || '';
    form.appendChild(csrfInput);

    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'PATCH';
    form.appendChild(methodInput);

    document.body.appendChild(form);
    form.submit();
}
window.toggleStatus = toggleStatus;

// ============================================================
// INITIALIZATION
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // CODE VALIDATION — ADD MODAL
    // ============================================================

    /**
     * Validasi kode kategori duplikat untuk modal tambah.
     * Membandingkan input dengan daftar kode yang sudah ada (client-side).
     */
    const existingCodes = window.existingCodes || [];
    const addCodeInput = document.getElementById('add-code');
    const addCodeWarning = document.getElementById('add-code-warning');
    const addCodeWarningText = document.getElementById('add-code-warning-text');
    const addSubmitBtn = document.getElementById('submit-btn-addModal');

    function validateAddCode() {
        if (!addCodeInput) return true;

        const code = addCodeInput.value.trim().toUpperCase();

        if (existingCodes.includes(code)) {
            addCodeWarning.classList.remove('hidden');
            addCodeInput.classList.add('border-red-500', 'border-2');
            addCodeWarningText.textContent =
                `Kode "${code}" sudah digunakan! Silakan gunakan kode yang berbeda.`;

            if (addSubmitBtn) {
                addSubmitBtn.disabled = true;
                addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            return false;
        } else {
            addCodeWarning.classList.add('hidden');
            addCodeInput.classList.remove('border-red-500', 'border-2');
            if (addSubmitBtn) {
                addSubmitBtn.disabled = false;
                addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }
    }

    if (addCodeInput) {
        addCodeInput.addEventListener('input', validateAddCode);
        addCodeInput.addEventListener('blur', validateAddCode);
    }

    // ============================================================
    // CODE VALIDATION — EDIT MODALS
    // ============================================================

    /**
     * Validasi kode kategori duplikat untuk modal edit.
     * Mengecualikan kategori yang sedang diedit dari pengecekan.
     */
    const existingCodesWithId = window.existingCodesWithId || {};
    const editCodeInputs = document.querySelectorAll('[id^="edit-code-"]');

    editCodeInputs.forEach(function (editCodeInput) {
        const categoryId = editCodeInput.getAttribute('data-category-id');
        const editCodeWarning = document.getElementById('edit-code-warning-' + categoryId);
        const editCodeWarningText = document.getElementById('edit-code-warning-text-' + categoryId);
        const editSubmitBtn = document.getElementById('submit-btn-editModal-' + categoryId);

        function validateEditCode() {
            const code = editCodeInput.value.trim().toUpperCase();

            let codeExists = false;
            for (let id in existingCodesWithId) {
                if (id != categoryId && existingCodesWithId[id] === code) {
                    codeExists = true;
                    break;
                }
            }

            if (codeExists) {
                editCodeWarning.classList.remove('hidden');
                editCodeInput.classList.add('border-red-500', 'border-2');
                editCodeWarningText.textContent =
                    `Kode "${code}" sudah digunakan! Silakan gunakan kode yang berbeda.`;

                if (editSubmitBtn) {
                    editSubmitBtn.disabled = true;
                    editSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            } else {
                editCodeWarning.classList.add('hidden');
                editCodeInput.classList.remove('border-red-500', 'border-2');
                if (editSubmitBtn) {
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                return true;
            }
        }

        editCodeInput.addEventListener('input', validateEditCode);
        editCodeInput.addEventListener('blur', validateEditCode);
    });

    // ============================================================
    // FORM SUBMISSION — ADD MODAL
    // ============================================================

    /**
     * Handle form submission untuk modal tambah.
     * Validasi kode duplikat dan prevent double submit.
     */
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            if (addCodeInput && !validateAddCode()) {
                e.preventDefault();
                addCodeWarning.classList.remove('hidden');
                addCodeInput.focus();
                return false;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ============================================================
    // FORM SUBMISSION — EDIT MODALS
    // ============================================================

    /**
     * Handle form submission untuk semua modal edit.
     * Prevent double submit.
     */
    const editForms = document.querySelectorAll('[id^="editModal-"] form');
    editForms.forEach(function (editForm) {
        editForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ============================================================
    // SELECT ALL CHECKBOX
    // ============================================================

    /**
     * Fungsionalitas checkbox "Pilih Semua" untuk bulk delete.
     * Sinkronisasi status checkbox individu dengan checkbox "Pilih Semua".
     */
    const selectAllCheckbox = document.getElementById('selectAll');
    const categoryCheckboxes = document.querySelectorAll('input[name="selected_categories[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButtonState() {
        const anyChecked = Array.from(categoryCheckboxes).some(cb => cb.checked);
        if (deleteButton) {
            deleteButton.disabled = !anyChecked;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            categoryCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            if (!this.checked) {
                selectAllCheckbox.checked = false;
            } else {
                const allChecked = Array.from(categoryCheckboxes).every(cb => cb.checked);
                selectAllCheckbox.checked = allChecked;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();

    // ============================================================
    // RESET SUBMIT STATE ON PAGE SHOW
    // ============================================================

    /**
     * Reset state tombol submit ketika user navigasi menggunakan tombol back browser.
     */
    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
