/**
 * Surat Jalan (Delivery Note) - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Auto-generate nomor dokumen
 * - Select all checkbox
 * - Bulk delete button state
 * - Print selected handler
 * - Manajemen item row (tambah/hapus)
 * - Loading indicator pada tombol aksi
 * - Dropdown print handler
 */

/* ==========================================
 * HELPER: Submit Form Hapus dengan Loading
 * ========================================== */

/**
 * Submit form hapus dengan loading indicator.
 * Dipanggil dari onclick pada modal konfirmasi hapus.
 *
 * @param {string} buttonId - ID tombol konfirmasi (default: confirm-btn-deleteModal)
 * @param {string} formId - ID form yang akan di-submit (default: deleteForm)
 * @param {string} loadingText - Teks loading saat proses (default: Menghapus...)
 */
window.submitDeleteForm = function (buttonId = 'confirm-btn-deleteModal', formId = 'deleteForm', loadingText = 'Menghapus...') {
    const deleteBtn = document.getElementById(buttonId);
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + loadingText;
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
};

/* ==========================================
 * AUTO-GENERATE NOMOR DOKUMEN
 * ========================================== */

/**
 * Menghasilkan nomor dokumen otomatis dengan format DN-YYYYMMDD-XXXX.
 * XXXX adalah nomor random 4 digit.
 *
 * Catatan client vs server:
 * - Client-side: fungsi ini hanya mengisi field `documentNumber` pada form
 *   sebagai nilai tampilan awal (preview). Format `DN-{tanggal}-{random 4 digit}`.
 * - Server-side: `DeliveryNoteService::create()` memanggil
 *   `DeliveryNote::generateDeliveryNoteId()` yang menghasilkan ID unik
 *   `id_delivery_note` sebagai kunci utama record. Nilai hasil generate di sini
 *   tidak dijadikan referensi unik di database; uniqueness ditentukan server.
 *
 * @returns {string} Nomor dokumen
 */
function generateDocumentNumber() {
    const now = new Date();
    const date = now.toISOString().slice(0, 10).replace(/-/g, '');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    return `DN-${date}-${random}`;
}

/* ==========================================
 * MANAJEMEN ITEM ROW
 * ========================================== */

/**
 * Menambahkan baris item baru ke dalam container items.
 *
 * Alur:
 * 1. Ambil kontainer `itemsContainer-{modalId}` (addModal atau editModal-{id}).
 * 2. Tentukan nomor urut baru dari jumlah `.item-row` existing + 1.
 * 3. Bangun elemen `.item-row` berisi input no (readonly), nama barang,
 *    jumlah, satuan, catatan, dan tombol hapus.
 * 4. Append ke kontainer lalu panggil `updateDeleteButtonVisibility(modalId)`.
 *
 * Catatan backend: input dengan nama `item_no[]`, `item_name[]`, `quantity[]`,
 * `unit[]`, `item_notes[]` akan diolah oleh `DeliveryNoteService::processItems()`.
 *
 * @param {string} modalId - ID modal (addModal atau editModal-{id})
 */
function addItemRow(modalId) {
    const container = document.getElementById('itemsContainer-' + modalId);
    if (!container) return;

    const itemRows = container.querySelectorAll('.item-row');
    const newNo = itemRows.length + 1;

    const newRow = document.createElement('div');
    newRow.className =
        'item-row bg-surface-base border-2 border-border-strong rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow';
    newRow.innerHTML = `
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-semibold text-text-label mb-1.5">No</label>
                <input type="number" name="item_no[]"
                    class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="1" min="1" value="${newNo}" required readonly>
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-label mb-1.5">Nama Barang <span
                        class="text-error">*</span></label>
                <input type="text" name="item_name[]"
                    class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Masukkan nama barang..." required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-label mb-1.5">Jumlah <span
                        class="text-error">*</span></label>
                <input type="number" name="quantity[]"
                    class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="0" min="1" required>
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-label mb-1.5">Satuan</label>
                <input type="text" name="unit[]"
                    class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="pcs" value="pcs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-text-label mb-1.5">Catatan</label>
                <input type="text" name="item_notes[]"
                    class="w-full border border-border-strong rounded-lg px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Masukkan catatan...">
            </div>
            <button type="button" onclick="removeItemRow(this)"
                class="delete-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded-lg text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
                <i class="fa-solid fa-trash"></i>
                <span>Hapus</span>
            </button>
        </div>
    `;

    container.appendChild(newRow);
    updateDeleteButtonVisibility(modalId);
}

/**
 * Menghapus baris item dari container.
 *
 * Alur:
 * 1. Cari `.item-row` terdekat dari tombol dan hapus dari DOM.
 * 2. Cari modal induk (`[id^="addModal"]` atau `[id^="editModal-"]`).
 * 3. Perbarui visibilitas tombol hapus pada seluruh baris (minimal 1 baris).
 *
 * @param {HTMLElement} button - Tombol hapus yang diklik
 */
function removeItemRow(button) {
    const row = button.closest('.item-row');
    if (row) {
        row.remove();
        const modal = button.closest('[id^="addModal"], [id^="editModal-"]');
        if (modal) {
            updateDeleteButtonVisibility(modal.id);
        }
    }
}

/**
 * Memperbarui visibilitas tombol hapus pada setiap item row.
 * Tombol hapus hanya ditampilkan jika ada lebih dari 1 item.
 *
 * Alur:
 * 1. Ambil kontainer `itemsContainer-{modalId}`.
 * 2. Hitung jumlah `.item-row` yang ada.
 * 3. Untuk tiap baris, tampilkan tombol hapus (`display: flex`) jika jumlah
 *    item > 1, atau sembunyikan (`display: none`) jika hanya tersisa 1 item
 *    agar tidak ada surat jalan tanpa minimal satu baris.
 *
 * @param {string} modalId - ID modal (addModal atau editModal-{id})
 */
function updateDeleteButtonVisibility(modalId) {
    const container = document.getElementById('itemsContainer-' + modalId);
    if (!container) return;

    const itemRows = container.querySelectorAll('.item-row');

    itemRows.forEach(function (row) {
        const deleteBtn = row.querySelector('.delete-btn');
        if (deleteBtn) {
            deleteBtn.style.display = itemRows.length > 1 ? 'flex' : 'none';
        }
    });
}

/* ==========================================
 * STATUS TOMBOL (SELECT ALL & DELETE)
 * ========================================== */

/**
 * Memperbarui status tombol hapus dan tombol print berdasarkan checkbox yang dipilih.
 *
 * Alur:
 * 1. Hitung jumlah `.row-checkbox` yang dicentang.
 * 2. Perbarui teks `selectedCountText`.
 * 3. Aktifkan/nonaktifkan tombol hapus beserta kelas visualnya.
 * 4. Tampilkan/sembunyikan tombol cetak terpilih.
 */
function updateButtonStates() {
    const deleteButton = document.getElementById('delete-button');
    const printSelectedItem = document.getElementById('printSelectedItem');
    const selectedCountText = document.getElementById('selectedCountText');
    const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = checkedCheckboxes.length;

    if (selectedCountText) {
        selectedCountText.textContent = count;
    }

    if (deleteButton) {
        if (count > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    if (printSelectedItem) {
        if (count > 0) {
            printSelectedItem.classList.remove('hidden');
        } else {
            printSelectedItem.classList.add('hidden');
        }
    }
}

/* ==========================================
 * CETAK TERPILIH
 * ========================================== */

/**
 * Fungsi untuk print surat jalan yang dipilih.
 * Mengumpulkan checkbox yang dicentang, mengirim via AJAX, dan download PDF.
 *
 * Alur:
 * 1. Ambil route dari meta `print-selected-route` (fallback ke endpoint PDF terpilih).
 * 2. Delegasikan ke sharedPrintSelected(route, btn) yang memposting `ids[]`
 *    tercentang lalu mengunduh file PDF.
 *
 * @param {HTMLButtonElement} btn - Tombol yang diklik
 * @returns {boolean} true jika proses dimulai
 */
function printSelected(btn) {
    return sharedPrintSelected(
        document.querySelector('meta[name="print-selected-route"]')?.content ||
        '/delivery-note/export/pdf-selected',
        btn
    );
}

/* ==========================================
 * INISIALISASI HALAMAN
 * ========================================== */

/**
 * Inisialisasi interaksi pada halaman index Surat Jalan.
 *
 * Alur:
 * 1. Auto-generate nomor dokumen awal pada field `documentNumber`.
 * 2. Pasang handler checkbox "Pilih Semua" dan sinkronisasi checkbox individual.
 * 3. Inisialisasi visibilitas tombol hapus item pada seluruh modal.
 * 4. Pasang loading indicator pada submit form tambah & edit, serta
 *    cegah double submit pada form hapus.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Auto-generate Nomor Dokumen ─────────────────────────────────
    const docNumField = document.getElementById('documentNumber');
    if (docNumField) {
        docNumField.value = generateDocumentNumber();
    }

    // ─── Checkbox Pilih Semua ─────────────────────────────────────────
    const selectAllEl = document.getElementById('selectAll');
    if (selectAllEl) {
        selectAllEl.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = selectAllEl.checked;
            });
            updateButtonStates();
        });
    }

    document.querySelectorAll('.row-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');

            if (selectAll) {
                selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            }
            updateButtonStates();
        });
    });

    updateButtonStates();

    // ─── Inisialisasi Visibilitas Tombol Hapus Item ──────────────────
    document.querySelectorAll('[id^="itemsContainer-"]').forEach(function (container) {
        const modalId = container.id.replace('itemsContainer-', '');
        updateDeleteButtonVisibility(modalId);
    });

    // ─── Form Submit dengan Loading Indicator: Modal Tambah ──────────
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ─── Form Submit dengan Loading Indicator: Modal Edit ────────────
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (!handleFormSubmit(submitBtn, originalText, 'Memperbarui...')) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ─── Form Hapus: Cegah Double Submit ──────────────────────
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            const submitBtn = document.getElementById('confirm-btn-deleteModal');
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
        });
    }
});

// ─── Reset isSubmitting Flag Saat Halaman Dimuat Kembali ──────────────
/**
 * Reset state isSubmitting ketika halaman dimuat ulang (mis. dari cache bfcache).
 *
 * Alur:
 * 1. Panggil `resetFormSubmitState()` untuk membuka kembali kunci double submit
 *    dan mengembalikan tombol submit ke kondisi awal.
 */
window.addEventListener('pageshow', function () {
    resetFormSubmitState();
});

// Expose ke global scope agar bisa dipanggil dari inline onclick di Blade
window.printSelected = printSelected;
window.addItemRow = addItemRow;
window.removeItemRow = removeItemRow;
