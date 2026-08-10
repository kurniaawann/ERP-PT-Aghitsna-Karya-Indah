/**
 * Laporan Keuangan Proyek — Modular JavaScript
 *
 * Fitur:
 * - Struktur transaksi dinamis (mirip Detail Pekerjaan RAB): pilih kategori →
 *   baris transaksi bernomor otomatis (No. 1, No. 2, ...), isi keterangan &
 *   jumlah. Label jumlah menyesuaikan tipe kategori (Pemasukan/Pengeluaran).
 *   Struktur berlaku untuk modal Tambah (global) dan modal Edit gabungan
 *   (data Rekap Proyek + transaksi, per rekap; terisi otomatis dari
 *   transaksi existing).
 * - Rekapitulasi otomatis per modal (Total Pemasukan, Total Pengeluaran, Saldo)
 * - Format input currency (Rupiah)
 * - Hapus massal (submit form hapus)
 * - Checkbox pilih semua
 * - Penanganan submit form (cegah double submit)
 * - Reset state submit saat halaman dimuat ulang (tombol kembali)
 */

// ============================================================
// FORMAT INPUT CURRENCY
// ============================================================

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

// ============================================================
// UTILITAS
// ============================================================

/**
 * Escape HTML agar nilai dari data attribute aman disisipkan ke markup.
 * @param {*} value
 * @returns {string}
 */
function escapeHtml(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Format angka murni ke format ribuan Indonesia.
 * @param {*} value
 * @returns {string}
 */
function formatNumber(value) {
    const numeric = parseInt(String(value == null ? '' : value).replace(/[^\d]/g, ''), 10) || 0;
    return numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
}

/**
 * Ambil elemen kontainer transaksi dari id atau elemen.
 * @param {string|HTMLElement} containerId - ID atau elemen kontainer.
 * @returns {HTMLElement|null}
 */
function getTransactionContainer(containerId) {
    return typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
}

// ============================================================
// KATEGORI INCOME vs EXPENSE — LABEL JUMLAH (MODAL EDIT LEGACY)
// ============================================================

/**
 * Sinkronkan form edit legacy berdasarkan tipe kategori terpilih.
 * - INCOME: label "Jumlah Pemasukan"
 * - EXPENSE: label "Jumlah Pengeluaran"
 *
 * @param {HTMLFormElement} form
 */
function syncCategoryFields(form) {
    const select = form.querySelector('select[name="transaction_category_id"]');
    if (!select) return;

    const selected = select.options[select.selectedIndex];
    const isIncome = selected && selected.dataset.type === 'INCOME';

    const amountLabel = form.querySelector('.amount-label');
    if (amountLabel) {
        amountLabel.innerHTML = (isIncome ? 'Jumlah Pemasukan' : 'Jumlah Pengeluaran') +
            ' <span class="text-error">*</span>';
    }
}
window.syncCategoryFields = syncCategoryFields;

// ============================================================
// STRUKTUR TRANSAKSI DINAMIS (MIRIP DETAIL PEKERJAAN RAB)
// ============================================================

/**
 * Ambil daftar kategori dari data attribute kontainer transaksi.
 * @param {HTMLElement} container - Kontainer transaksi.
 * @returns {Array<{id:number,name:string,type:string}>}
 */
function getTransactionCategories(container) {
    if (!container || !container.dataset.categories) return [];
    try {
        return JSON.parse(container.dataset.categories);
    } catch (e) {
        return [];
    }
}

/**
 * Bangun HTML option kategori untuk select dalam blok transaksi.
 * @param {Array<{id:number,name:string,type:string}>} categories
 * @param {number|string} [selectedId] ID kategori yang terpilih
 * @returns {string}
 */
function categoryOptionsHtml(categories, selectedId) {
    let html = '<option value="">-- Pilih Kategori --</option>';
    categories.forEach(function (cat) {
        const selected = String(cat.id) === String(selectedId) ? ' selected' : '';
        html += '<option value="' + cat.id + '" data-type="' + cat.type + '"' + selected + '>' +
            escapeHtml(cat.name) + '</option>';
    });
    return html;
}

/**
 * Bangun HTML satu blok transaksi.
 *
 * Nama input memakai array `items[{index}][...]` dengan indeks numerik yang
 * sama dalam satu blok, agar PHP/Laravel mengelompokkan seluruh field satu
 * transaksi ke satu entri array (bukan `items[][...]` yang terpecah per field).
 * Item existing menyertakan hidden `items[{index}][id]`.
 *
 * @param {Array<{id:number,name:string,type:string}>} categories
 * @param {Object} [data] Data transaksi existing (untuk modal edit).
 * @param {number} [index] Indeks baris transaksi pada array `items`.
 * @returns {string}
 */
function transactionBlockHtml(categories, data, index) {
    data = data || {};
    index = index == null ? 0 : index;

    const idHidden = data.id
        ? '<input type="hidden" name="items[' + index + '][id]" value="' + escapeHtml(data.id) + '">'
        : '';

    const proofNotice = (data.proof_url && data.proof_file_name)
        ? '<p class="text-xs text-blue-600 mt-1">Bukti saat ini: <a href="' + escapeHtml(data.proof_url) +
            '" target="_blank" rel="noopener noreferrer" class="underline">' + escapeHtml(data.proof_file_name) +
            '</a></p>'
        : '';

    return `
        <div class="flex items-center justify-between mb-3">
            <span class="transaction-number text-sm font-semibold text-primary">Transaksi No. 1</span>
            <button type="button" onclick="removeTransactionBlock(this)"
                class="flex items-center gap-1 bg-error hover:bg-error text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                title="Hapus transaksi">
                <i class="fa-solid fa-trash w-3 h-3"></i>
                Hapus
            </button>
        </div>

        ${idHidden}

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Kategori <span class="text-error">*</span></label>
            <select name="items[${index}][transaction_category_id]"
                class="w-full border rounded p-2 transaction-category-select" required
                oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
                ${categoryOptionsHtml(categories, data.transaction_category_id)}
            </select>
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
            <input type="date" name="items[${index}][transaction_date]" class="w-full border rounded p-2"
                value="${escapeHtml(data.transaction_date || '')}" required
                oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Keterangan <span class="text-error">*</span></label>
            <textarea name="items[${index}][description]" class="w-full border rounded p-2" rows="3" required maxlength="1000"
                placeholder="Contoh: Kasbon Transport Tukang"
                oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                oninput="this.setCustomValidity('')">${escapeHtml(data.description || '')}</textarea>
        </div>

        <div class="mb-3">
            <label class="amount-label block text-text-primary mb-1">Jumlah Pengeluaran <span class="text-error">*</span></label>
            <input type="text" inputmode="numeric" name="items[${index}][expense_amount]"
                class="w-full border rounded p-2 expense-amount-input" placeholder="Contoh: 50000"
                value="${escapeHtml(formatNumber(data.amount))}" required min="0"
                oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')">
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Keterangan Bon</label>
            <input type="text" name="items[${index}][keterangan_bon]" class="w-full border rounded p-2"
                value="${escapeHtml(data.keterangan_bon || '')}"
                placeholder="Contoh: Bon Pembelian Material" maxlength="255">
        </div>

        <div class="mb-3">
            <label class="block text-text-primary mb-1">Bukti Pembayaran</label>
            <input type="file" name="items[${index}][proof_file]"
                accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
                class="w-full border rounded p-2">
            <p class="text-xs text-text-secondary mt-1">Opsional. Format gambar: JPG, PNG, GIF, WEBP, BMP. Maksimal 5 MB.
                Kosongkan jika tidak ingin mengubah file.</p>
            ${proofNotice}
        </div>
    `;
}

/**
 * Menentukan indeks array `items` untuk blok transaksi berikutnya.
 *
 * Indeks diambil dari nilai maksimum yang sudah dipakai + 1 agar tidak
 * bertabrakan walau ada blok yang dihapus (indeks non-kontigu tetap aman
 * karena tiap field dalam satu blok memakai indeks yang sama).
 *
 * @param {HTMLElement} container - Kontainer transaksi.
 * @returns {number}
 */
function getNextTransactionIndex(container) {
    let next = 0;

    container.querySelectorAll('.transaction-block [name^="items["]').forEach(function (input) {
        const match = input.name.match(/^items\[(\d+)\]/);
        if (match) {
            const used = parseInt(match[1], 10);
            if (used >= next) next = used + 1;
        }
    });

    return next;
}

/**
 * Menambahkan satu blok transaksi baru (bernomor otomatis) ke kontainer.
 *
 * @param {string|HTMLElement} containerId - ID atau elemen kontainer transaksi.
 * @param {Object} [data] Data transaksi existing (untuk modal edit).
 */
function addTransactionBlock(containerId, data) {
    const container = getTransactionContainer(containerId);
    if (!container) return;

    const block = document.createElement('div');
    block.className = 'transaction-block border border-border-strong rounded p-3 bg-surface-base';
    block.innerHTML = transactionBlockHtml(getTransactionCategories(container), data, getNextTransactionIndex(container));

    container.appendChild(block);
    renumberTransactionBlocks(container);
    syncBlockCategoryFields(block);
    updateTransactionsSummary(container);
}
window.addTransactionBlock = addTransactionBlock;

/**
 * Menghapus satu blok transaksi lalu renumber & hitung ulang rekapitulasi.
 * @param {HTMLElement} button - Tombol hapus pada blok transaksi.
 */
function removeTransactionBlock(button) {
    const block = button.closest('.transaction-block');
    const container = block ? block.closest('[data-categories]') : null;
    if (block) block.remove();
    if (container) {
        renumberTransactionBlocks(container);
        updateTransactionsSummary(container);
    }
}
window.removeTransactionBlock = removeTransactionBlock;

/**
 * Memperbarui nomor tampilan tiap blok transaksi ("Transaksi No. X").
 * @param {HTMLElement} container - Kontainer daftar transaksi.
 */
function renumberTransactionBlocks(container) {
    if (!container) return;
    container.querySelectorAll('.transaction-block').forEach(function (block, index) {
        const numberEl = block.querySelector('.transaction-number');
        if (numberEl) numberEl.textContent = 'Transaksi No. ' + (index + 1);
    });
}

/**
 * Sinkronkan label jumlah dalam satu blok berdasarkan tipe kategori terpilih.
 * - INCOME: "Jumlah Pemasukan"
 * - EXPENSE: "Jumlah Pengeluaran"
 *
 * @param {HTMLElement} block - Blok transaksi terkait.
 */
function syncBlockCategoryFields(block) {
    const select = block.querySelector('.transaction-category-select');
    const label = block.querySelector('.amount-label');
    if (!select || !label) return;
    const selected = select.options[select.selectedIndex];
    const isIncome = selected && selected.dataset.type === 'INCOME';
    label.innerHTML = (isIncome ? 'Jumlah Pemasukan' : 'Jumlah Pengeluaran') + ' <span class="text-error">*</span>';
}

/**
 * Menghitung ulang rekapitulasi modal (Total Pemasukan, Total Pengeluaran, Saldo).
 *
 * Elemen rekap memakai ID turunan dari kontainer:
 * `{containerId}-totalIncome`, `{containerId}-totalExpense`, `{containerId}-balance`.
 *
 * @param {string|HTMLElement} containerId - ID atau elemen kontainer transaksi.
 */
function updateTransactionsSummary(containerId) {
    const container = getTransactionContainer(containerId);
    if (!container || !container.id) return;

    let totalIncome = 0;
    let totalExpense = 0;

    container.querySelectorAll('.transaction-block').forEach(function (block) {
        const select = block.querySelector('.transaction-category-select');
        const amountInput = block.querySelector('.expense-amount-input');
        const amount = parseInt((amountInput ? amountInput.value : '').replace(/[^\d]/g, ''), 10) || 0;
        const selected = select ? select.options[select.selectedIndex] : null;
        if (selected && selected.dataset.type === 'INCOME') {
            totalIncome += amount;
        } else {
            totalExpense += amount;
        }
    });

    const formatRupiah = function (value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(value);
    };

    const baseId = container.id;
    const incomeEl = document.getElementById(baseId + '-totalIncome');
    const expenseEl = document.getElementById(baseId + '-totalExpense');
    const balanceEl = document.getElementById(baseId + '-balance');

    if (incomeEl) incomeEl.textContent = formatRupiah(totalIncome);
    if (expenseEl) expenseEl.textContent = formatRupiah(totalExpense);
    if (balanceEl) balanceEl.textContent = formatRupiah(totalIncome - totalExpense);
}

/**
 * Submit bulk delete form dengan loading indicator.
 *
 * @param {string} [modalId] ID modal konfirmasi hapus (memuat tombol konfirmasi).
 * @param {string} [formId]  ID form hapus massal yang akan di-submit.
 */
function submitDeleteForm(modalId = 'deleteModal', formId = 'deleteForm') {
    const deleteBtn = document.getElementById('confirm-btn-' + modalId);
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById(formId);
    if (form) {
        form.submit();
    }
}
window.submitDeleteForm = submitDeleteForm;

// ============================================================
// INISIALISASI
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ============================================================
    // STRUKTUR TRANSAKSI DINAMIS — MODAL TAMBAH (blok kosong pertama)
    // ============================================================

    if (document.getElementById('transactionsContainer')) {
        addTransactionBlock('transactionsContainer');
    }

    // ============================================================
    // STRUKTUR TRANSAKSI DINAMIS — MODAL EDIT (terisi transaksi existing)
    // ============================================================

    document.querySelectorAll('[data-existing-items]').forEach(function (container) {
        let existingItems = [];
        try {
            existingItems = JSON.parse(container.dataset.existingItems || '[]');
        } catch (e) {
            existingItems = [];
        }

        existingItems.forEach(function (item) {
            addTransactionBlock(container, item);
        });
    });

    // ============================================================
    // DELEGATION — ganti kategori & input jumlah (berlaku untuk
    // blok yang dibuat dinamis pada semua modal)
    // ============================================================

    document.addEventListener('change', function (e) {
        if (e.target.matches('.transaction-category-select')) {
            const block = e.target.closest('.transaction-block');
            syncBlockCategoryFields(block);
            if (block) updateTransactionsSummary(block.closest('[data-categories]'));
        }
    });

    document.addEventListener('input', function (e) {
        if (e.target.matches('.expense-amount-input')) {
            formatCurrencyInput(e.target);
            updateTransactionsSummary(e.target.closest('[data-categories]'));
        }
    });

    // ============================================================
    // KATEGORI INCOME vs EXPENSE — SINKRON LABEL FORM EDIT LEGACY
    // ============================================================

    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        const select = form.querySelector('select[name="transaction_category_id"]');
        if (select) {
            syncCategoryFields(form);
            select.addEventListener('change', function () {
                syncCategoryFields(form);
            });
        }
    });

    // ============================================================
    // CHECKBOX PILIH SEMUA (per form hapus massal)
    // ============================================================

    document.querySelectorAll('form[id="deleteForm"], form[id^="deleteForm-"]').forEach(function (form) {
        const suffix = form.id === 'deleteForm' ? '' : form.id.slice('deleteForm'.length);
        const selectAllCheckbox = form.querySelector('#selectAll' + suffix);
        const itemCheckboxes = form.querySelectorAll('input[name="selected_items[]"], input[name="selected_recaps[]"]');
        const deleteButton = document.getElementById('delete-button' + suffix);

        function updateDeleteButtonState() {
            const anyChecked = Array.from(itemCheckboxes).some(cb => cb.checked);
            if (deleteButton) {
                deleteButton.disabled = !anyChecked;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButtonState();
            });
        }

        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                if (!this.checked) {
                    selectAllCheckbox.checked = false;
                } else {
                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                }
                updateDeleteButtonState();
            });
        });

        updateDeleteButtonState();
    });

    // ============================================================
    // FORMAT INPUT CURRENCY (nilai awal & input listener statis)
    // ============================================================

    document.querySelectorAll('.expense-amount-input, .total-rab-input').forEach(input => {
        if (input.value) {
            formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // ============================================================
    // PENANGANAN SUBMIT FORM — MODAL TAMBAH
    // ============================================================

    const addForms = document.querySelectorAll('#addModal form, [id^="addModal-"] form');
    addForms.forEach(function (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ============================================================
    // PENANGANAN SUBMIT FORM — MODAL EDIT
    // ============================================================

    const editForms = document.querySelectorAll('[id^="editModal-"] form, [id^="editPfrModal-"] form');
    editForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';

            if (!handleFormSubmit(submitBtn, originalText)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ============================================================
    // RESET STATE SUBMIT SAAT HALAMAN DIMUAT ULANG
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
    });
});
