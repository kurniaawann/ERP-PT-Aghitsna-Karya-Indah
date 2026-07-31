/* global handleFormSubmit, resetFormSubmitState, openModal, closeModal, showToast */

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MODUL JAVASCRIPT: REIMBURSEMENT INDEX
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Menangani semua interaktivitas halaman Reimbursement:
 * - Select All checkbox
 * - Update state tombol (delete & approval)
 * - Perhitungan total dari data terpilih
 * - Submit form (add, edit, approve, reject, delete)
 * - Dropdown persetujuan
 */

// ════════════════════════════════════════════════════════════════════════════
// CHECKBOX PILIH SEMUA
// ════════════════════════════════════════════════════════════════════════════

/**
 * Inisialisasi checkbox Select All.
 * Ketika Select All di-check/un-check, semua checkbox individu mengikuti.
 */
function initSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAll');
    if (!selectAllCheckbox) return;

    selectAllCheckbox.addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateButtonStates();
        updateSelectedInfo();
    });
}

/**
 * Inisialisasi checkbox individu.
 * Mengupdate Select All dan state tombol saat checkbox individu berubah.
 */
function initIndividualCheckboxes() {
    document.querySelectorAll('input[name="ids[]"]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const selectAll = document.getElementById('selectAll');
            const allCheckboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            if (selectAll) {
                selectAll.checked = allCheckboxes.length === checkedCheckboxes.length
                    && allCheckboxes.length > 0;
            }

            updateButtonStates();
            updateSelectedInfo();
        });
    });
}

// ════════════════════════════════════════════════════════════════════════════
// UPDATE STATE TOMBOL
// ════════════════════════════════════════════════════════════════════════════

/**
 * Update state tombol Delete dan Approval berdasarkan checkbox yang dipilih.
 * Tombol akan aktif jika ada minimal 1 checkbox yang dicentang.
 */
function updateButtonStates() {
    var checkedCount = document.querySelectorAll('input[name="ids[]"]:checked').length;

    // Tombol Hapus
    var deleteButton = document.getElementById('delete-button');
    if (deleteButton) {
        if (checkedCount > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');
        }
    }

    // Tombol Dropdown Persetujuan (Super Admin)
    var approvalButton = document.getElementById('approval-dropdown-button');
    if (approvalButton) {
        if (checkedCount > 0) {
            approvalButton.disabled = false;
            approvalButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            approvalButton.disabled = true;
            approvalButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
}

// ════════════════════════════════════════════════════════════════════════════
// UPDATE INFO TERPILIH (Super Admin)
// ════════════════════════════════════════════════════════════════════════════

/**
 * Menghitung dan menampilkan ringkasan data terpilih.
 * Info mencakup jumlah item dan total amount.
 * Juga mengupdate total di modal approve/reject.
 */
function updateSelectedInfo() {
    var selectedInfo = document.getElementById('selected-info');
    var selectedCount = document.getElementById('selected-count');
    var selectedTotal = document.getElementById('selected-total');

    if (!selectedInfo) return;

    var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
    var count = checkedCheckboxes.length;

    if (count > 0) {
        var total = 0;
        checkedCheckboxes.forEach(function (checkbox) {
            var amount = parseInt(checkbox.getAttribute('data-amount')) || 0;
            total += amount;
        });

        var formattedTotal = 'Rp ' + total.toLocaleString('id-ID');

        // Tampilkan panel info
        selectedInfo.classList.remove('hidden');
        selectedCount.textContent = count;
        selectedTotal.textContent = formattedTotal;

        // Update total di modal approve
        var approveTotalModal = document.getElementById('approve-total-modal');
        if (approveTotalModal) {
            approveTotalModal.textContent = formattedTotal;
        }

        // Update total di modal reject
        var rejectTotalModal = document.getElementById('reject-total-modal');
        if (rejectTotalModal) {
            rejectTotalModal.textContent = formattedTotal;
        }

        // Update teks jumlah di modal approve
        var approveCountText = document.getElementById('approve-count-text');
        if (approveCountText) {
            approveCountText.textContent = count;
        }

        // Update teks jumlah di modal reject
        var rejectCountText = document.getElementById('reject-count-text');
        if (rejectCountText) {
            rejectCountText.textContent = count;
        }
    } else {
        selectedInfo.classList.add('hidden');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// SUBMIT FORM: HAPUS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Submit form bulk delete.
 * Menampilkan loading state pada tombol konfirmasi.
 */
function submitDeleteForm() {
    var deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }
    document.getElementById('deleteForm').submit();
}

// ════════════════════════════════════════════════════════════════════════════
// SUBMIT FORM: TAMBAH & EDIT
// ════════════════════════════════════════════════════════════════════════════

/**
 * Inisialisasi form submit handler untuk modal Tambah.
 * Mencegah double submit dengan loading state.
 */
function initAddFormSubmit() {
    var addModalForm = document.querySelector('#addModal form');
    if (!addModalForm) return;

    addModalForm.addEventListener('submit', function (e) {
        var submitBtn = document.querySelector('#submit-btn-addModal');
        if (!handleFormSubmit(submitBtn, 'Simpan')) {
            e.preventDefault();
        }
    });
}

/**
 * Inisialisasi form submit handler untuk semua modal Edit.
 * Mencegah double submit dengan loading state.
 */
function initEditFormSubmit() {
    document.querySelectorAll('[id^="editModal-"]').forEach(function (modal) {
        var form = modal.querySelector('form');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            var submitBtn = document.querySelector('#submit-btn-' + modal.id);
            if (!handleFormSubmit(submitBtn, 'Update')) {
                e.preventDefault();
            }
        });
    });
}

// ════════════════════════════════════════════════════════════════════════════
// SUBMIT FORM: SETUJUI & TOLAK
// ════════════════════════════════════════════════════════════════════════════

/**
 * Injeksikan hidden inputs ke form approve/reject sebelum submit.
 * Hidden inputs berisi `ids[]` dari checkbox yang dipilih.
 *
 * @param  {string} formSelector  CSS selector form target
 * @param  {string} containerId   ID container untuk hidden inputs
 * @param  {string} submitBtnId   ID tombol submit
 * @param  {string} originalText  Teks tombol asli
 */
function initApprovalFormSubmit(formSelector, containerId, submitBtnId, originalText) {
    var form = document.querySelector(formSelector);
    if (!form) return;

    form.addEventListener('submit', function (e) {
        // Injiksi hidden inputs dari checkbox terpilih
        var checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');
        var container = document.getElementById(containerId);

        if (container) {
            container.innerHTML = '';
            checkedCheckboxes.forEach(function (checkbox) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = checkbox.value;
                container.appendChild(input);
            });
        }

        var submitBtn = document.querySelector('#' + submitBtnId);
        if (!handleFormSubmit(submitBtn, originalText)) {
            e.preventDefault();
        }
    });
}

// ════════════════════════════════════════════════════════════════════════════
// DROPDOWN PERSETUJUAN
// ════════════════════════════════════════════════════════════════════════════

/**
 * Inisialisasi dropdown persetujuan (approve/reject).
 * Toggle visibility saat tombol diklik, tutup saat klik di luar.
 */
function initApprovalDropdown() {
    var approvalButton = document.getElementById('approval-dropdown-button');
    var approvalMenu = document.getElementById('approval-dropdown-menu');

    if (!approvalButton || !approvalMenu) return;

    approvalButton.addEventListener('click', function (e) {
        e.stopPropagation();
        if (!this.disabled) {
            approvalMenu.classList.toggle('hidden');
        }
    });

    document.addEventListener('click', function (e) {
        if (!approvalButton.contains(e.target) && !approvalMenu.contains(e.target)) {
            approvalMenu.classList.add('hidden');
        }
    });

    approvalMenu.addEventListener('click', function (e) {
        e.stopPropagation();
    });
}

// ════════════════════════════════════════════════════════════════════════════
// INISIALISASI
// ════════════════════════════════════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', function () {
    // Checkbox
    initSelectAll();
    initIndividualCheckboxes();

    // Update state awal
    updateButtonStates();
    updateSelectedInfo();

    // Handler submit form
    initAddFormSubmit();
    initEditFormSubmit();
    initApprovalFormSubmit('#approveModal form', 'approve-hidden-inputs', 'submit-btn-approveModal', 'Setujui');
    initApprovalFormSubmit('#rejectModal form', 'reject-hidden-inputs', 'submit-btn-rejectModal', 'Tolak');

    // Dropdown
    initApprovalDropdown();

    // Reset submit state saat halaman dimuat ulang
    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});

// Ekspos ke global scope untuk akses dari onclick di Blade
window.submitDeleteForm = submitDeleteForm;
