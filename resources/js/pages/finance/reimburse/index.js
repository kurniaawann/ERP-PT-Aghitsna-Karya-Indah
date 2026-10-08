/* global handleFormSubmit, resetFormSubmitState, openModal, closeModal, showToast, formatCurrencyInput */

/**
 * ════════════════════════════════════════════════════════════════════════════
 * MODUL JAVASCRIPT: REIMBURSEMENT INDEX
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Menangani semua interaktivitas halaman Reimbursement:
 * - Select All checkbox
 * - Update state tombol (delete, approval, Export Dipilih)
 * - Perhitungan total dari data terpilih
 * - Submit form (add, edit, approve, reject, delete)
 * - Dropdown persetujuan
 * - Pratinjau otomatis PDF reimburse yang baru disetujui (tanpa tab baru)
 *
 * Checkbox `ids[]` tersedia di semua baris (untuk Export Dipilih). Bagi admin
 * (penyetuju), aksi Setujui/Tolak/Hapus hanya memproses baris berstatus draft
 * (atribut data-status).
 */

// ════════════════════════════════════════════════════════════════════════════
// HELPER SELEKSI
// ════════════════════════════════════════════════════════════════════════════

/**
 * Apakah user saat ini adalah penyetuju (admin) — ditandai dengan adanya
 * tombol dropdown persetujuan di halaman.
 *
 * @returns {boolean}
 */
function isApprover() {
    return !!document.getElementById('approval-dropdown-button');
}

/**
 * Checkbox `ids[]` yang sedang dicentang.
 *
 * @returns {HTMLInputElement[]}
 */
function getCheckedBoxes() {
    return Array.from(document.querySelectorAll('input[name="ids[]"]:checked'));
}

/**
 * Checkbox tercentang yang berstatus draft (bisa disetujui/ditolak).
 *
 * @returns {HTMLInputElement[]}
 */
function getCheckedDraftBoxes() {
    return getCheckedBoxes().filter(function (checkbox) {
        return checkbox.dataset.status === 'draft';
    });
}

/**
 * Jumlahkan data-amount dari daftar checkbox.
 *
 * @param  {HTMLInputElement[]} checkboxes
 * @returns {number}
 */
function sumAmount(checkboxes) {
    return checkboxes.reduce(function (total, checkbox) {
        return total + (parseInt(checkbox.getAttribute('data-amount'), 10) || 0);
    }, 0);
}

// ════════════════════════════════════════════════════════════════════════════
// CHECKBOX PILIH SEMUA
// ════════════════════════════════════════════════════════════════════════════

/**
 * Inisialisasi checkbox Select All.
 * Ketika Select All di-check/un-check, semua checkbox individu mengikuti.
 *
 * Alur:
 * 1. Saat #selectAll berubah, set checked semua checkbox #ids[] mengikuti.
 * 2. Panggil updateButtonStates() untuk mengaktifkan/nonaktifkan tombol
 *    Delete & Approval.
 * 3. Panggil updateSelectedInfo() untuk memperbarui ringkasan total.
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
 * Update state tombol Delete, Approval, dan Export Dipilih berdasarkan
 * checkbox yang dipilih.
 *
 * Alur:
 * 1. Hitung jumlah checkbox #ids[] yang dicentang (dan yang berstatus draft).
 * 2. Tombol Delete: aktif bila ada pilihan (admin: bila ada pilihan draft,
 *    karena admin hanya boleh menghapus draft).
 * 3. Tombol dropdown persetujuan (admin): aktif bila ada pilihan draft.
 * 4. Menu "Export Dipilih" pada dropdown Print: tampil bila ada pilihan,
 *    beserta jumlah data terpilih.
 */
function updateButtonStates() {
    var checkedCount = getCheckedBoxes().length;
    var draftCount = getCheckedDraftBoxes().length;
    var deletableCount = isApprover() ? draftCount : checkedCount;

    // Tombol Hapus
    var deleteButton = document.getElementById('delete-button');
    if (deleteButton) {
        if (deletableCount > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');
        }
    }

    // Tombol Dropdown Persetujuan (Admin) — hanya untuk pilihan berstatus draft
    var approvalButton = document.getElementById('approval-dropdown-button');
    if (approvalButton) {
        if (draftCount > 0) {
            approvalButton.disabled = false;
            approvalButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            approvalButton.disabled = true;
            approvalButton.classList.add('opacity-50', 'cursor-not-allowed');
            var approvalMenu = document.getElementById('approval-dropdown-menu');
            if (approvalMenu) approvalMenu.classList.add('hidden');
        }
    }

    // Menu Export Dipilih (PDF/Excel) pada dropdown Print
    var printSelectedItem = document.getElementById('printSelectedItem');
    if (printSelectedItem) {
        printSelectedItem.classList.toggle('hidden', checkedCount === 0);
    }
    var selectedCountText = document.getElementById('selectedCountText');
    if (selectedCountText) {
        selectedCountText.textContent = checkedCount;
    }
}

// ════════════════════════════════════════════════════════════════════════════
// FORMAT TOTAL AMOUNT (MATA UANG)
// ════════════════════════════════════════════════════════════════════════════

/**
 * Inisialisasi format mata uang pada input `.reimburse-amount-input`
 * (modal Tambah & Edit) menggunakan formatCurrencyInput dari shared/currency.js.
 *
 * Alur:
 * 1. Jika input sudah berisi nilai (mis. modal edit), format nilainya.
 * 2. Ikat event input untuk memformat saat mengetik (pemisah ribuan id-ID).
 */
function initAmountFormatting() {
    document.querySelectorAll('.reimburse-amount-input').forEach(function (input) {
        if (input.value) {
            formatCurrencyInput(input);
        }
        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });
}

// ════════════════════════════════════════════════════════════════════════════
// UPDATE INFO TERPILIH (Super Admin)
// ════════════════════════════════════════════════════════════════════════════

/**
 * Menghitung dan menampilkan ringkasan data terpilih (panel admin).
 * Info mencakup jumlah item dan total amount seluruh pilihan, plus jumlah
 * draft yang bisa disetujui bila pilihan bercampur status.
 * Juga mengupdate jumlah & total di modal approve/reject (hanya draft).
 */
function updateSelectedInfo() {
    var selectedInfo = document.getElementById('selected-info');
    var selectedCount = document.getElementById('selected-count');
    var selectedTotal = document.getElementById('selected-total');
    var selectedDraftInfo = document.getElementById('selected-draft-info');

    if (!selectedInfo) return;

    var checkedCheckboxes = getCheckedBoxes();
    var draftCheckboxes = getCheckedDraftBoxes();
    var count = checkedCheckboxes.length;

    if (count > 0) {
        var formattedTotal = 'Rp ' + sumAmount(checkedCheckboxes).toLocaleString('id-ID');
        var formattedDraftTotal = 'Rp ' + sumAmount(draftCheckboxes).toLocaleString('id-ID');

        // Tampilkan panel info
        selectedInfo.classList.remove('hidden');
        selectedCount.textContent = count;
        selectedTotal.textContent = formattedTotal;

        if (selectedDraftInfo) {
            selectedDraftInfo.textContent = draftCheckboxes.length !== count
                ? '(' + draftCheckboxes.length + ' draft dapat disetujui/ditolak)'
                : '';
        }

        // Update total di modal approve & reject (hanya draft)
        var approveTotalModal = document.getElementById('approve-total-modal');
        if (approveTotalModal) {
            approveTotalModal.textContent = formattedDraftTotal;
        }

        var rejectTotalModal = document.getElementById('reject-total-modal');
        if (rejectTotalModal) {
            rejectTotalModal.textContent = formattedDraftTotal;
        }

        // Update teks jumlah di modal approve & reject (hanya draft)
        var approveCountText = document.getElementById('approve-count-text');
        if (approveCountText) {
            approveCountText.textContent = draftCheckboxes.length;
        }

        var rejectCountText = document.getElementById('reject-count-text');
        if (rejectCountText) {
            rejectCountText.textContent = draftCheckboxes.length;
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
 * Hidden inputs berisi `ids[]` dari checkbox terpilih yang berstatus draft.
 *
 * Alur persetujuan level 1 (submit form):
 * 1. Saat form disubmit, kosongkan container hidden inputs.
 * 2. Loop checkbox terpilih berstatus draft, buat <input type="hidden" name="ids[]">
 *    untuk masing-masing, lalu masukkan ke container.
 * 3. Panggil handleFormSubmit untuk menampilkan loading & mencegah double submit.
 *    Setelah disetujui, server mengembalikan halaman dengan pratinjau PDF
 *    reimburse yang disetujui (lihat openApprovedPreview).
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
        // Injeksi hidden inputs dari checkbox terpilih (hanya draft)
        var checkedCheckboxes = getCheckedDraftBoxes();
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
 *
 * Alur persetujuan level 2 (dropdown):
 * 1. Klik tombol #approval-dropdown-button men-toggle menu (bila tombol aktif).
 * 2. Klik di luar tombol & menu menutup dropdown.
 * 3. Klik di dalam menu tidak menutup dropdown (stopPropagation) — user memilih
 *    aksi Approve atau Reject dari menu tersebut.
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
// PRATINJAU SETELAH DISETUJUI
// ════════════════════════════════════════════════════════════════════════════

/**
 * Buka pratinjau PDF reimburse yang baru disetujui di dalam halaman.
 *
 * Alur:
 * 1. Server menaruh URL export PDF (ids[] reimburse yang disetujui) pada
 *    input tersembunyi #reimburse-approved-preview-url setelah approve.
 * 2. Bila ada, panggil window.openDocumentPreview (shared/document-preview.js)
 *    — tidak membuka tab baru. Bila modul pratinjau belum siap, tunggu
 *    event load lalu coba lagi.
 */
function openApprovedPreview() {
    var input = document.getElementById('reimburse-approved-preview-url');
    if (!input || !input.value) return;

    var open = function () {
        if (typeof window.openDocumentPreview !== 'function') return false;
        window.openDocumentPreview(input.value, { title: 'Reimburse Disetujui' });
        input.remove();
        return true;
    };

    if (!open()) {
        window.addEventListener('load', open, { once: true });
    }
}

// ════════════════════════════════════════════════════════════════════════════
// INISIALISASI
// ════════════════════════════════════════════════════════════════════════════

/**
 * Inisialisasi logika halaman saat DOM siap.
 *
 * Alur:
 * 1. Inisialisasi checkbox pilih semua dan checkbox individu.
 * 2. Update state awal tombol & info terpilih.
 * 3. Inisialisasi submit form Tambah, Edit, dan form persetujuan
 *    (approve/reject — 2 level: submit form + dropdown).
 * 4. Inisialisasi dropdown persetujuan.
 * 5. Buka pratinjau PDF reimburse yang baru disetujui (bila ada).
 * 6. Reset status submit saat halaman dimuat ulang (pageshow).
 */
document.addEventListener('DOMContentLoaded', function () {
    // Checkbox
    initSelectAll();
    initIndividualCheckboxes();

    // Update state awal
    updateButtonStates();
    updateSelectedInfo();

    // Format input Total Amount menjadi format Rupiah
    initAmountFormatting();

    // Handler submit form
    initAddFormSubmit();
    initEditFormSubmit();
    initApprovalFormSubmit('#approveModal form', 'approve-hidden-inputs', 'submit-btn-approveModal', 'Setujui');
    initApprovalFormSubmit('#rejectModal form', 'reject-hidden-inputs', 'submit-btn-rejectModal', 'Tolak');

    // Dropdown
    initApprovalDropdown();

    // Pratinjau PDF reimburse yang baru disetujui (bila ada)
    openApprovedPreview();

    // Reset submit state saat halaman dimuat ulang
    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});

// Ekspos ke global scope untuk akses dari onclick di Blade
window.submitDeleteForm = submitDeleteForm;
