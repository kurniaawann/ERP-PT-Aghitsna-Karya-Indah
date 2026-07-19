/**
 * Penawaran Proyek (Project Quotation) — Index Page JavaScript
 *
 * Modul ini menangani:
 * - Auto-generate nomor penawaran
 * - Manajemen dynamic items (tambah/hapus/hitung ulang)
 * - Serialisasi items ke JSON untuk form submission
 * - Validasi rekening pembayaran (wajib pilih minimal 1)
 * - Loading indicator pada tombol aksi
 * - Select all checkbox & bulk delete button state
 * - Error display pada modal
 */

/* ==========================================
 * HELPER: Escape HTML
 * ========================================== */

/**
 * Meng-escape karakter HTML untuk keamanan saat menyisipkan string ke DOM.
 *
 * @param  {string} str  String yang akan di-escape
 * @return {string}      String yang sudah di-escape
 */
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ==========================================
 * HELPER: Format Rupiah
 * ========================================== */

/**
 * Memformat angka sebagai Rupiah.
 *
 * @param  {number|string} value  Nilai angka
 * @return {string}               String format Rp
 */
function formatRp(value) {
    const n = parseAmount(value);
    return 'Rp ' + n.toLocaleString('id-ID');
}

/**
 * Menghapus pemisah ribuan dan mengembalikan angka murni.
 *
 * @param  {string} str  String angka dengan pemisah
 * @return {number}      Angka murni
 */
function parseAmount(str) {
    if (!str) return 0;
    return parseInt(str.toString().replace(/\./g, '').replace(/,/g, '')) || 0;
}

/**
 * Menerapkan format pemisah ribuan pada input harga.
 *
 * @param  {HTMLInputElement} input  Element input
 */
function formatPriceInput(input) {
    let raw = input.value.replace(/\./g, '').replace(/[^0-9]/g, '');
    if (raw === '') {
        input.value = '';
        return;
    }
    input.value = parseInt(raw).toLocaleString('id-ID');
}

/* ==========================================
 * ERROR DISPLAY PADA MODAL
 * ========================================== */

/**
 * Menampilkan pesan error di dalam modal.
 *
 * @param  {string} prefix       Prefix ID modal (contoh: 'add', 'edit-1/1/PT.AKI/26')
 * @param  {string} errorMessage  Pesan error
 */
function showModalError(prefix, errorMessage) {
    const errorDiv = document.getElementById(prefix + 'ModalError');
    const errorText = document.getElementById(prefix + 'ModalErrorText');
    if (errorDiv && errorText) {
        errorText.textContent = errorMessage;
        errorDiv.classList.remove('hidden');
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

/**
 * Menyembunyikan pesan error di dalam modal.
 *
 * @param  {string} prefix  Prefix ID modal
 */
function hideModalError(prefix) {
    const errorDiv = document.getElementById(prefix + 'ModalError');
    if (errorDiv) {
        errorDiv.classList.add('hidden');
    }
}

/* ==========================================
 * RESOLVE CONTAINER / GRAND-TOTAL / JSON IDs
 * ========================================== */

/**
 * Mendapatkan ID container, grand total, dan JSON input berdasarkan prefix.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotNumber}'
 * @return {Object}         { container, grandTotal, jsonInput }
 */
function resolveIds(prefix) {
    if (prefix === 'add') {
        return {
            container: 'addItemsContainer',
            grandTotal: 'addGrandTotal',
            jsonInput: 'addItemsJson',
        };
    }
    // prefix = 'edit-{quotationNumber}'
    const quotNum = prefix.replace(/^edit-/, '');
    return {
        container: `editItemsContainer-${quotNum}`,
        grandTotal: `editGrandTotal-${quotNum}`,
        jsonInput: `editItemsJson-${quotNum}`,
    };
}

/* ==========================================
 * ITEMS STORE MANAGEMENT
 * ========================================== */

/**
 * Global state untuk add items.
 */
let addItemsStore = [];
let addNextItemId = 1;

/**
 * Edit items disimpan per quotation number.
 */
let editItemsStore = window.editItemsStore || {};
let editNextItemId = {};

/**
 * Mendapatkan items store berdasarkan prefix.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotNumber}'
 * @return {Array}          Array of items
 */
function getItemsStore(prefix) {
    if (prefix === 'add') {
        return addItemsStore;
    }
    const quotNum = prefix.replace(/^edit-/, '');
    return editItemsStore[quotNum] || [];
}

/**
 * Menyimpan items store berdasarkan prefix.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotNumber}'
 * @param  {Array}  items   Array of items
 */
function setItemsStore(prefix, items) {
    if (prefix === 'add') {
        addItemsStore = items;
    } else {
        const quotNum = prefix.replace(/^edit-/, '');
        editItemsStore[quotNum] = items;
    }
}

/* ==========================================
 * ITEM OPERATIONS
 * ========================================== */

/**
 * Menambahkan item baru ke dalam daftar.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotNumber}'
 */
function addItem(prefix) {
    const items = getItemsStore(prefix);

    let newId;
    if (prefix === 'add') {
        newId = addNextItemId++;
    } else {
        const quotNum = prefix.replace(/^edit-/, '');
        if (!editNextItemId[quotNum]) {
            editNextItemId[quotNum] = 1;
        }
        newId = editNextItemId[quotNum]++;
    }

    const newItem = {
        id: newId,
        order_number: items.length + 1,
        description: '',
        volume: '',
        unit: '',
        unit_price: '',
        total_price: 0
    };

    items.push(newItem);
    setItemsStore(prefix, items);
    renderItems(prefix);
}

/**
 * Menghapus item dari daftar.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotNumber}'
 * @param  {number} itemId  ID item yang akan dihapus
 */
function removeItem(prefix, itemId) {
    let items = getItemsStore(prefix);
    items = items.filter(i => i.id !== itemId);

    // Reorder
    items.forEach((item, idx) => {
        item.order_number = idx + 1;
    });

    setItemsStore(prefix, items);
    renderItems(prefix);
}

/**
 * Memperbarui field item tertentu.
 *
 * @param  {string}  prefix   'add' atau 'edit-{quotNumber}'
 * @param  {number}  itemId   ID item
 * @param  {string}  field    Nama field (description, volume, unit, unit_price)
 * @param  {string}  value    Nilai baru
 * @param  {boolean} render   Apakah perlu render ulang (default: true)
 */
function updateItemField(prefix, itemId, field, value, render = true) {
    const items = getItemsStore(prefix);
    const item = items.find(i => i.id === itemId);
    if (!item) return;

    item[field] = value;

    // Hitung total_price
    const vol = parseFloat((item.volume || '1').toString().replace(',', '.')) || 1;
    const price = parseAmount(item.unit_price);
    item.total_price = Math.round(vol * price);

    setItemsStore(prefix, items);

    if (render) {
        renderItems(prefix);
        return;
    }

    // Update hanya DOM nodes yang relevan agar tidak kehilangan fokus/caret
    const ids = resolveIds(prefix);
    const container = document.getElementById(ids.container);
    if (!container) return;
    const itemEl = container.querySelector(`[data-item-id="${itemId}"]`);
    if (itemEl) {
        const totalEl = itemEl.querySelector('.item-total-display');
        if (totalEl) totalEl.textContent = formatRp(item.total_price);
    }

    // Update grand total
    updateGrandTotal(prefix);
}

/* ==========================================
 * RENDER ITEMS
 * ========================================== */

/**
 * Merender semua item ke dalam container.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotNumber}'
 */
function renderItems(prefix) {
    const ids = resolveIds(prefix);
    const container = document.getElementById(ids.container);
    if (!container) return;

    const items = getItemsStore(prefix);

    container.innerHTML = '';

    if (items.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 p-4 border-2 border-dashed rounded-lg">
                Belum ada item. Klik tombol "Tambah Item" untuk menambahkan.
            </div>
        `;
        updateGrandTotal(prefix);
        return;
    }

    items.forEach((item, idx) => {
        const itemCard = document.createElement('div');
        itemCard.className = 'bg-surface-secondary rounded-lg p-4';
        itemCard.setAttribute('data-item-id', item.id);
        itemCard.innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-sm text-text-heading">
                    <i class="fa-solid fa-circle-dot text-primary mr-1"></i>
                    Item ${idx + 1}
                </h4>
                <button type="button" onclick="removeItem('${prefix}', ${item.id})"
                    class="text-error hover:bg-error-light px-2 py-1 rounded-md transition-colors duration-200">
                    <i class="fa-solid fa-trash-can"></i> Hapus
                </button>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="block text-xs font-medium text-text-label mb-1">Keterangan</label>
                    <input type="text" value="${escHtml(item.description || '')}"
                        oninput="updateItemField('${prefix}', ${item.id}, 'description', this.value, false)"
                        onchange="updateItemField('${prefix}', ${item.id}, 'description', this.value)"
                        class="w-full border border-border-strong rounded-md p-2 text-sm text-text-input bg-surface-base"
                        placeholder="Deskripsi item">
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-text-label mb-1">Volume</label>
                        <input type="number" min="0" step="0.01" value="${escHtml(item.volume || '')}"
                            oninput="this.value = this.value.replace(/[^0-9.,]/g, ''); updateItemField('${prefix}', ${item.id}, 'volume', this.value, false)"
                            onchange="updateItemField('${prefix}', ${item.id}, 'volume', this.value)"
                            class="w-full border border-border-strong rounded-md p-2 text-sm text-text-input bg-surface-base item-volume"
                            placeholder="1">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-label mb-1">Satuan</label>
                        <input type="text" value="${escHtml(item.unit || '')}"
                            oninput="updateItemField('${prefix}', ${item.id}, 'unit', this.value, false)"
                            onchange="updateItemField('${prefix}', ${item.id}, 'unit', this.value)"
                            class="w-full border border-border-strong rounded-md p-2 text-sm text-text-input bg-surface-base"
                            placeholder="unit">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-text-label mb-1">Harga Satuan</label>
                        <input type="number" min="0" step="1"
                            value="${item.unit_price ? parseAmount(item.unit_price) : ''}"
                            oninput="updateItemField('${prefix}', ${item.id}, 'unit_price', this.value, false)"
                            onchange="updateItemField('${prefix}', ${item.id}, 'unit_price', this.value)"
                            class="w-full border border-border-strong rounded-md p-2 text-sm text-text-input bg-surface-base item-unit-price"
                            placeholder="0">
                    </div>
                </div>

                <div class="bg-info-light rounded-md p-2 flex justify-between items-center">
                    <span class="text-xs font-medium text-text-label">Total Harga:</span>
                    <span class="font-bold text-primary item-total-display">${formatRp(item.total_price)}</span>
                </div>
            </div>
        `;

        container.appendChild(itemCard);
    });

    updateGrandTotal(prefix);
}

/* ==========================================
 * GRAND TOTAL
 * ========================================== */

/**
 * Menghitung dan menampilkan grand total.
 *
 * @param  {string} prefix  'add' atau 'edit-{quotNumber}'
 */
function updateGrandTotal(prefix) {
    const ids = resolveIds(prefix);
    const grandTotalEl = document.getElementById(ids.grandTotal);
    if (!grandTotalEl) return;

    const items = getItemsStore(prefix);
    const grandTotal = items.reduce((sum, item) => sum + item.total_price, 0);

    grandTotalEl.textContent = formatRp(grandTotal);
}

/* ==========================================
 * FETCH NEXT QUOTATION NUMBER
 * ========================================== */

/**
 * Mengambil nomor penawaran berikutnya via AJAX.
 */
function fetchNextQuotationNumber() {
    const url = document.querySelector('meta[name="project-quotation-get-next-number"]')?.content;
    if (!url) return;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const displayEl = document.getElementById('addQuotationNumberDisplay');
            if (displayEl) {
                displayEl.textContent = data.quotation_number;
            }
        })
        .catch(error => {
            console.error('Error fetching next quotation number:', error);
        });
}

/* ==========================================
 * RENDER EDIT MODAL ITEMS
 * ========================================== */

/**
 * Merender items untuk modal edit berdasarkan data yang sudah di-load
 * dari inline script di edit-modal Blade component.
 *
 * @param  {string} quotNum  Nomor penawaran
 */
function renderEditModalItems(quotNum) {
    // Data sudah di-load oleh inline script di edit-modal Blade
    // via window.editItemsStore[quotNum] dan window.editNextItemId[quotNum]
    if (!editItemsStore[quotNum]) {
        editItemsStore[quotNum] = [];
    }

    // Pastikan next item ID benar
    if (editItemsStore[quotNum].length > 0) {
        editNextItemId[quotNum] = Math.max(...editItemsStore[quotNum].map(i => i.id), 0) + 1;
    } else {
        editNextItemId[quotNum] = 1;
    }

    renderItems('edit-' + quotNum);
}

/* ==========================================
 * VALIDASI PEMBAYARAN
 * ========================================== */

/**
 * Memvalidasi bahwa minimal 1 rekening pembayaran dipilih dalam modal tertentu.
 *
 * @param  {string} modalId  ID modal (contoh: 'addModal', 'editModal-1/1/PT.AKI/26')
 */
function validatePaymentSelection(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const checkboxes = modal.querySelectorAll('.payment-account-checkbox');
    const checkedCount = modal.querySelectorAll('.payment-account-checkbox:checked').length;

    checkboxes.forEach(cb => {
        cb.required = checkedCount === 0;
        cb.setCustomValidity('');
    });
}

/* ==========================================
 * PREPARE SUBMIT
 * ========================================== */

/**
 * Menyiapkan data sebelum submit form tambah penawaran.
 *
 * @return {boolean}  true jika valid, false jika tidak
 */
function prepareAddSubmit() {
    hideModalError('add');

    if (addItemsStore.length === 0) {
        showModalError('add', 'Minimal harus ada 1 item');
        return false;
    }

    // Validasi items
    for (let i = 0; i < addItemsStore.length; i++) {
        const item = addItemsStore[i];
        if (!item.description || item.description.trim() === '') {
            showModalError('add', `Item ${i + 1}: Keterangan harus diisi`);
            return false;
        }
        if (!item.unit_price || parseAmount(item.unit_price) <= 0) {
            showModalError('add', `Item ${i + 1}: Harga satuan harus lebih dari 0`);
            return false;
        }
        // Validasi volume: harus numerik jika diisi
        if (item.volume && item.volume !== '' && isNaN(parseFloat(String(item.volume).replace(',', '.')))) {
            showModalError('add', `Item ${i + 1}: Volume harus berupa angka`);
            return false;
        }
        // Validasi volume: tidak boleh negatif
        if (item.volume && parseFloat(String(item.volume).replace(',', '.')) < 0) {
            showModalError('add', `Item ${i + 1}: Volume tidak boleh negatif`);
            return false;
        }
    }

    // Set JSON
    const ids = resolveIds('add');
    const jsonInput = document.getElementById(ids.jsonInput);
    if (jsonInput) {
        jsonInput.value = JSON.stringify(addItemsStore);
    }

    // Show loading spinner, cegah double submit
    const submitBtn = document.getElementById('submit-btn-addModal');
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
            return false;
        }
    }
    return true;
}

/**
 * Menyiapkan data sebelum submit form edit penawaran.
 *
 * @param  {string} quotNum  Nomor penawaran
 * @return {boolean}         true jika valid, false jika tidak
 */
function prepareEditSubmit(quotNum) {
    const errorPrefix = 'edit-' + quotNum;
    hideModalError(errorPrefix);

    const items = editItemsStore[quotNum] || [];

    if (items.length === 0) {
        showModalError(errorPrefix, 'Minimal harus ada 1 item');
        return false;
    }

    // Validasi items
    for (let i = 0; i < items.length; i++) {
        const item = items[i];
        if (!item.description || item.description.trim() === '') {
            showModalError(errorPrefix, `Item ${i + 1}: Keterangan harus diisi`);
            return false;
        }
        if (!item.unit_price || parseAmount(item.unit_price) <= 0) {
            showModalError(errorPrefix, `Item ${i + 1}: Harga satuan harus lebih dari 0`);
            return false;
        }
        // Validasi volume: harus numerik jika diisi
        if (item.volume && item.volume !== '' && isNaN(parseFloat(String(item.volume).replace(',', '.')))) {
            showModalError(errorPrefix, `Item ${i + 1}: Volume harus berupa angka`);
            return false;
        }
        // Validasi volume: tidak boleh negatif
        if (item.volume && parseFloat(String(item.volume).replace(',', '.')) < 0) {
            showModalError(errorPrefix, `Item ${i + 1}: Volume tidak boleh negatif`);
            return false;
        }
    }

    // Set JSON
    const ids = resolveIds('edit-' + quotNum);
    const jsonInput = document.getElementById(ids.jsonInput);
    if (jsonInput) {
        jsonInput.value = JSON.stringify(items);
    }

    // Show loading spinner, cegah double submit
    const submitBtn = document.getElementById('submit-btn-editModal-' + quotNum);
    if (submitBtn) {
        const originalText = submitBtn.innerHTML;
        if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
            return false;
        }
    }
    return true;
}

/* ==========================================
 * DELETE
 * ========================================== */

/**
 * Submit form hapus setelah konfirmasi.
 */
function submitDeleteForm() {
    const checkboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]:checked');
    if (checkboxes.length === 0) {
        alert('Pilih minimal 1 penawaran untuk dihapus');
        closeModal('deleteModal');
        return false;
    }

    // Set delete button ke loading state, cegah double click
    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        const originalText = deleteBtn.innerHTML;
        if (!handleFormSubmit(deleteBtn, originalText, 'Menghapus...')) {
            return;
        }
    }

    // Submit form
    setTimeout(() => {
        document.getElementById('deleteForm').submit();
    }, 100);
}

/**
 * Memperbarui status tombol hapus berdasarkan checkbox yang dipilih.
 */
function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-button');
    const checkedCheckboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]:checked');

    if (deleteButton) {
        if (checkedCheckboxes.length > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');
        }
    }
}

/* ==========================================
 * DOMCONTENTLOADED INITIALIZATION
 * ========================================== */

document.addEventListener('DOMContentLoaded', function() {
    // ═══ Auto-fetch next quotation number untuk Add modal ═══
    const addModal = document.getElementById('addModal');
    if (addModal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (m.target.id === 'addModal' && !m.target.classList.contains('hidden')) {
                    hideModalError('add');
                    fetchNextQuotationNumber();
                    addItemsStore = [];
                    addNextItemId = 1;
                    renderItems('add');
                }
            });
        });
        observer.observe(addModal, {
            attributes: true,
            attributeFilter: ['class']
        });
    }

    // ═══ Render items untuk Edit modals saat modal terbuka ═══
    // Data sudah di-load oleh inline script di edit-modal Blade
    document.querySelectorAll('[id^="editModal-"]').forEach(function(editModal) {
        const quotNum = editModal.id.replace('editModal-', '');
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                if (!m.target.classList.contains('hidden')) {
                    hideModalError('edit-' + quotNum);
                    renderEditModalItems(quotNum);
                }
            });
        });
        observer.observe(editModal, {
            attributes: true,
            attributeFilter: ['class']
        });
    });

    // ═══ Select All checkbox ═══
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateDeleteButtonState();
        });
    }

    // Tambahkan event listener ke individual checkboxes
    const checkboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allCheckboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('#deleteForm input[name="ids[]"]:checked');

            // Update select all checkbox state
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
            }

            updateDeleteButtonState();
        });
    });

    // Inisialisasi delete button state saat halaman dimuat
    updateDeleteButtonState();

    // ═══ Payment account validation: Add Modal ═══
    const addModalCheckboxes = document.querySelectorAll('#addModal .payment-account-checkbox');
    addModalCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            validatePaymentSelection('addModal');
        });
    });
    validatePaymentSelection('addModal');

    // ═══ Payment account validation: Edit Modals ═══
    document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
        const modalId = modal.id;
        modal.querySelectorAll('.payment-account-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                validatePaymentSelection(modalId);
            });
        });
        validatePaymentSelection(modalId);
    });

    // ═══ Delete Form: Prevent Double Submission ═══
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('confirm-btn-deleteModal');
            if (submitBtn && submitBtn.disabled) {
                e.preventDefault();
                return false;
            }
        });
    }
});

// ─── Reset isSubmitting Flag Saat Halaman Dimuat Kembali ──────────────────
window.addEventListener('pageshow', function() {
    resetFormSubmitState();
    updateDeleteButtonState();
});

// ─── Expose ke global scope agar bisa dipanggil dari inline onclick di Blade ──
window.addItem = addItem;
window.removeItem = removeItem;
window.updateItemField = updateItemField;
window.prepareAddSubmit = prepareAddSubmit;
window.prepareEditSubmit = prepareEditSubmit;
window.submitDeleteForm = submitDeleteForm;
