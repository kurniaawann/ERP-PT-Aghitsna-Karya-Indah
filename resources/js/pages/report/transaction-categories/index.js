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
// HAPUS MASSAL — PERIKSA KATEGORI YANG DIGUNAKAN
// ============================================================

/**
 * Mengecek apakah ada kategori yang sedang digunakan sebelum menampilkan
 * modal konfirmasi hapus. Jika ada, tampilkan modal peringatan.
 *
 * Alur:
 * 1. Kumpulkan semua checkbox tercentang (.category-checkbox:checked).
 * 2. Untuk tiap checkbox, periksa flag data-is-used yang di-set server dari
 *    TransactionCategoryService::getUsedCategoryIds() (fetch kategori yang
 *    sedang dipakai di expense reports).
 * 3. Jika ada kategori terpakai → tampilkan showWarningModal() berisi daftar
 *    nama kategori yang tidak boleh dihapus.
 * 4. Jika tidak ada → langsung buka modal konfirmasi hapus (openModal('deleteModal')).
 *
 * @returns {void}
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
 *
 * Alur: kosongkan daftar #usedCategoriesList, render satu <li> per nama
 * kategori, tampilkan modal #warningUsedModal (hapus 'hidden', tambah 'flex'),
 * lalu animasikan konten modal dari scale-95/opacity-0 ke scale-100/opacity-100
 * setelah 10ms (agar transisi CSS berjalan).
 *
 * @param {string[]} usedCategories Array nama kategori yang sedang digunakan
 * @returns {void}
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
 *
 * Alur: animasikan konten modal keluar (scale-100/opacity-100 →
 * scale-95/opacity-0), lalu sembunyikan modal (tambah 'hidden', hapus 'flex')
 * setelah 300ms menunggu transisi selesai.
 *
 * @returns {void}
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
 * Mengirim form hapus massal dengan indikator loading.
 *
 * Alur: tampilkan spinner "Menghapus..." dan nonaktifkan tombol konfirmasi
 * #confirm-btn-deleteModal (double-submit prevention), lalu submit #deleteForm
 * yang membawa selected_categories[] ke server
 * (TransactionCategoryService::deleteSelected).
 *
 * @returns {void}
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
// UBAH STATUS
// ============================================================

/**
 * Mengubah status aktif/nonaktif kategori menggunakan temporary form
 * dengan method spoofing PATCH.
 *
 * Alur:
 * 1. Buat element <form> temporary dengan method POST ke
 *    /transaction-category/{id}/toggle-status.
 * 2. Tambahkan input hidden _token (window.csrfToken) dan _method = 'PATCH'
 *    (Laravel method spoofing agar route menerima PATCH).
 * 3. Append form ke document.body lalu submit → server memanggil
 *    TransactionCategoryService::toggleStatus() yang membalik nilai is_active.
 *
 * @param {number} categoryId ID kategori yang akan di-toggle
 * @returns {void}
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
// INISIALISASI
// ============================================================

/**
 * Inisialisasi halaman Kategori Transaksi setelah DOM selesai dimuat.
 *
 * Alur:
 * 1. Validasi kode duplikat (client-side) untuk modal tambah (validateAddCode)
 *    dan semua modal edit (validateEditCode) via event 'input' dan 'blur'.
 * 2. Pengiriman form modal tambah: validasi ulang kode, lalu handleFormSubmit()
 *    (double-submit prevention); semua modal edit: handleFormSubmit().
 * 3. Checkbox "Pilih Semua": sinkronisasi status dengan checkbox individual
 *    plus updateDeleteButtonState().
 * 4. Reset state tombol submit saat navigasi kembali via tombol back browser
 *    (event 'pageshow').
 *
 * @returns {void}
 */
document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // VALIDASI KODE — MODAL TAMBAH
    // ============================================================

    /**
     * Validasi kode kategori duplikat untuk modal tambah.
     * Membandingkan input dengan daftar kode yang sudah ada (client-side).
     *
     * Alur: baca nilai input #add-code (di-trim + uppercase), lalu bandingkan
     * dengan existingCodes (window.existingCodes dari
     * TransactionCategoryService::getExistingCodes()). Jika duplikat →
     * tampilkan warning + border merah + nonaktifkan tombol submit; jika unik →
     * sembunyikan warning + aktifkan kembali tombol submit.
     *
     * @returns {boolean} true bila kode valid/unik, false bila duplikat
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
    // VALIDASI KODE — MODAL EDIT
    // ============================================================

    /**
     * Validasi kode kategori duplikat untuk modal edit.
     * Mengecualikan kategori yang sedang diedit dari pengecekan.
     *
     * Alur: baca nilai input edit-code-{id} (di-trim + uppercase), lalu iterasi
     * existingCodesWithId (window.existingCodesWithId: map [id => code]).
     * Kode kategori yang sedang diedit (id == data-category-id) dikecualikan.
     * Jika duplikat → tampilkan warning + nonaktifkan tombol submit; jika unik →
     * sembunyikan warning + aktifkan kembali tombol submit.
     *
     * @returns {boolean} true bila kode valid/unik, false bila duplikat
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
    // PENGIRIMAN FORM — MODAL TAMBAH
    // ============================================================

    /**
     * Menangani pengiriman form untuk modal tambah.
     * Validasi kode duplikat dan cegah pengiriman ganda.
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
    // PENGIRIMAN FORM — MODAL EDIT
    // ============================================================

    /**
     * Menangani pengiriman form untuk semua modal edit.
     * Mencegah pengiriman ganda.
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
    // CHECKBOX PILIH SEMUA
    // ============================================================

    /**
     * Fungsionalitas checkbox "Pilih Semua" untuk bulk delete.
     * Sinkronisasi status checkbox individu dengan checkbox "Pilih Semua".
     */
    const selectAllCheckbox = document.getElementById('selectAll');
    const categoryCheckboxes = document.querySelectorAll('input[name="selected_categories[]"]');
    const deleteButton = document.getElementById('delete-button');

    /**
     * Menyinkronkan state tombol "Hapus" dengan checkbox yang dipilih.
     *
     * Alur: tombol #delete-button aktif (disabled = false) hanya jika minimal
     * satu checkbox selected_categories[] dicentang (cek via Array.some()).
     * Dipanggil saat halaman dimuat dan setiap status checkbox berubah
     * (termasuk lewat "Pilih Semua").
     *
     * @returns {void}
     */
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
    // RESET STATUS SUBMIT SAAT HALAMAN DITAMPILKAN
    // ============================================================

    /**
     * Reset state tombol submit ketika user navigasi menggunakan tombol back browser.
     */
    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
