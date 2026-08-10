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
 * Ambil tipe kategori (INCOME/EXPENSE) berdasarkan id.
 *
 * @param {Array<{id:number,name:string,type:string}>} categories
 * @param {number|string} [id] ID kategori
 * @returns {string}
 */
function categoryTypeOf(categories, id) {
    if (id == null || id === '') return '';
    const found = categories.find(function (cat) { return String(cat.id) === String(id); });
    return found ? found.type : '';
}

/**
 * Bangun HTML satu blok "Bon" (satu keterangan) dalam grup kategori.
 *
 * Nama input memakai array `items[{index}][...]` dengan indeks numerik yang
 * sama dalam satu blok, agar PHP/Laravel mengelompokkan seluruh field satu
 * transaksi ke satu entri array. Kategori diwarisi dari grup lewat hidden
 * `items[{index}][transaction_category_id]`. Item existing menyertakan hidden
 * `items[{index}][id]`.
 *
 * @param {Array<{id:number,name:string,type:string}>} categories
 * @param {Object} [data] Data transaksi existing (untuk modal edit).
 * @param {number} [index] Indeks baris transaksi pada array `items`.
 * @returns {string}
 */
function bonBlockHtml(categories, data, index) {
    data = data || {};
    index = index == null ? 0 : index;

    const idHidden = data.id
        ? '<input type="hidden" name="items[' + index + '][id]" value="' + escapeHtml(data.id) + '">'
        : '';

    const catHidden = '<input type="hidden" class="transaction-category-hidden" ' +
        'name="items[' + index + '][transaction_category_id]" value="' + escapeHtml(data.transaction_category_id || '') + '" ' +
        'data-type="' + escapeHtml(data.category_type || '') + '">';

    const proofNotice = (data.proof_url && data.proof_file_name)
        ? '<p class="text-xs text-blue-600 mt-1">Bukti saat ini: <a href="' + escapeHtml(data.proof_url) +
            '" target="_blank" rel="noopener noreferrer" class="underline">' + escapeHtml(data.proof_file_name) +
            '</a></p>'
        : '';

    return `
        <div class="bon-block border border-border-strong rounded p-3 bg-surface-base">
            <div class="flex items-center justify-between mb-3">
                <span class="bon-number text-sm font-semibold text-primary">Bon No. 1</span>
                <button type="button" onclick="removeBonBlock(this)"
                    class="flex items-center gap-1 bg-error hover:bg-error text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                    title="Hapus keterangan">
                    <i class="fa-solid fa-trash w-3 h-3"></i>
                    Hapus
                </button>
            </div>

            ${idHidden}
            ${catHidden}

            <div class="mb-3">
                <label class="block text-text-primary mb-1">Tanggal <span class="text-error">*</span></label>
                <input type="date" name="items[${index}][transaction_date]" class="w-full border rounded p-2"
                    value="${escapeHtml(data.transaction_date || '')}" required
                    oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
            </div>

            <div class="mb-3">
                <label class="block text-text-primary mb-1">Keterangan <span class="text-error">*</span></label>
                <textarea name="items[${index}][description]" class="w-full border rounded p-2" rows="2" required maxlength="1000"
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
        </div>
    `;
}

/**
 * Bangun HTML satu grup kategori (select kategori + kontainer bons).
 *
 * Kategori dipilih sekali per grup; seluruh blok bon di dalamnya mewarisi
 * kategori tersebut lewat hidden `items[index][transaction_category_id]`,
 * sehingga satu kategori bisa memiliki banyak keterangan.
 *
 * @param {Array<{id:number,name:string,type:string}>} categories
 * @param {Object} [data] Data grup existing (memuat transaction_category_id dan items).
 * @returns {string}
 */
function transactionCategoryGroupHtml(categories, data) {
    data = data || {};

    return `
        <div class="transaction-category-group border border-border-strong rounded p-3 bg-surface-base">
            <div class="flex items-center justify-between mb-3">
                <span class="transaction-category-number text-sm font-semibold text-primary">Kategori No. 1</span>
                <button type="button" onclick="removeTransactionCategoryGroup(this)"
                    class="flex items-center gap-1 bg-error hover:bg-error text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                    title="Hapus kategori beserta semua keterangannya">
                    <i class="fa-solid fa-trash w-3 h-3"></i>
                    Hapus Kategori
                </button>
            </div>

            <div class="mb-3">
                <label class="block text-text-primary mb-1">Kategori <span class="text-error">*</span></label>
                <select class="w-full border rounded p-2 transaction-category-select" required
                    oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
                    oninput="this.setCustomValidity('')">
                    ${categoryOptionsHtml(categories, data.transaction_category_id)}
                </select>
            </div>

            <div class="bon-blocks space-y-4 mb-3"></div>

            <button type="button" onclick="addBonBlock(this)" class="btn btn-outline-primary w-full">
                <i class="fa-solid fa-plus"></i> Tambah Keterangan
            </button>
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

    container.querySelectorAll('.bon-block [name^="items["]').forEach(function (input) {
        const match = input.name.match(/^items\[(\d+)\]/);
        if (match) {
            const used = parseInt(match[1], 10);
            if (used >= next) next = used + 1;
        }
    });

    return next;
}

/**
 * Tambahkan satu blok "Bon" (keterangan) ke dalam grup kategori tertentu.
 *
 * @param {HTMLElement} groupEl - Elemen grup kategori.
 * @param {Array<{id:number,name:string,type:string}>} categories
 * @param {Object} [data] Data transaksi (untuk blok existing).
 */
function appendBonBlock(groupEl, categories, data) {
    const bonsContainer = groupEl ? groupEl.querySelector('.bon-blocks') : null;
    if (!bonsContainer) return;

    const container = groupEl.closest('[data-categories]');
    if (!container) return;

    const groupSelect = groupEl.querySelector('.transaction-category-select');
    const selectedId = (data && data.transaction_category_id) || (groupSelect ? groupSelect.value : '');

    data = data || {};
    data.transaction_category_id = selectedId;
    data.category_type = categoryTypeOf(categories, selectedId);

    const wrapper = document.createElement('div');
    wrapper.innerHTML = bonBlockHtml(categories, data, getNextTransactionIndex(container));
    bonsContainer.appendChild(wrapper.firstElementChild);
}

/**
 * "Tambah Keterangan" — tambah satu blok bon baru dalam grup kategori
 * (dipanggil dari tombol dalam grup).
 *
 * @param {HTMLElement} button - Tombol Tambah Keterangan.
 */
function addBonBlock(button) {
    const groupEl = button.closest('.transaction-category-group');
    if (!groupEl) return;
    const container = groupEl.closest('[data-categories]');
    if (!container) return;

    appendBonBlock(groupEl, getTransactionCategories(container), null);

    renumberTransactionBonBlocks(container);
    syncBonCategoryFields(groupEl);
    updateTransactionsSummary(container);
}
window.addBonBlock = addBonBlock;

/**
 * Hapus satu blok "Bon" (keterangan) dari grup lalu renumber & hitung ulang.
 *
 * @param {HTMLElement} button - Tombol hapus pada blok bon.
 */
function removeBonBlock(button) {
    const block = button.closest('.bon-block');
    const groupEl = block ? block.closest('.transaction-category-group') : null;
    const container = groupEl ? groupEl.closest('[data-categories]') : null;
    if (block) block.remove();
    if (container) {
        renumberTransactionBonBlocks(container);
        updateTransactionsSummary(container);
    }
}
window.removeBonBlock = removeBonBlock;

/**
 * Menambahkan satu grup kategori baru (bernomor otomatis) ke kontainer.
 * Setiap grup berisi select kategori + satu blok bon kosong pertama.
 *
 * @param {string|HTMLElement} containerId - ID atau elemen kontainer transaksi.
 * @param {Object} [data] Data grup existing (memuat transaction_category_id & items).
 */
function addTransactionCategoryGroup(containerId, data) {
    const container = getTransactionContainer(containerId);
    if (!container) return;
    data = data || {};

    const wrapper = document.createElement('div');
    wrapper.innerHTML = transactionCategoryGroupHtml(getTransactionCategories(container), data);
    const groupEl = wrapper.firstElementChild;
    container.appendChild(groupEl);

    const items = (data.items && data.items.length) ? data.items : [null];
    items.forEach(function (item) {
        appendBonBlock(groupEl, getTransactionCategories(container), item || {});
    });

    renumberTransactionBonBlocks(container);
    syncBonCategoryFields(groupEl);
    updateTransactionsSummary(container);
}
window.addTransactionCategoryGroup = addTransactionCategoryGroup;

/**
 * Hapus satu grup kategori beserta semua blok bon-nya.
 *
 * @param {HTMLElement} button - Tombol hapus pada grup kategori.
 */
function removeTransactionCategoryGroup(button) {
    const groupEl = button.closest('.transaction-category-group');
    const container = groupEl ? groupEl.closest('[data-categories]') : null;
    if (groupEl) groupEl.remove();
    if (container) {
        renumberTransactionBonBlocks(container);
        updateTransactionsSummary(container);
    }
}
window.removeTransactionCategoryGroup = removeTransactionCategoryGroup;

/**
 * Memperbarui nomor tampilan tiap grup kategori ("Kategori No. X") dan
 * blok bon di dalamnya ("Bon No. Y").
 *
 * @param {HTMLElement} container - Kontainer daftar transaksi.
 */
function renumberTransactionBonBlocks(container) {
    if (!container) return;

    container.querySelectorAll('.transaction-category-group').forEach(function (groupEl, catIndex) {
        const catNumberEl = groupEl.querySelector('.transaction-category-number');
        if (catNumberEl) catNumberEl.textContent = 'Kategori No. ' + (catIndex + 1);

        groupEl.querySelectorAll('.bon-block').forEach(function (block, bonIndex) {
            const bonNumberEl = block.querySelector('.bon-number');
            if (bonNumberEl) bonNumberEl.textContent = 'Bon No. ' + (bonIndex + 1);
        });
    });
}

/**
 * Sinkronkan hidden kategori & label jumlah seluruh blok bon dalam satu grup
 * berdasarkan kategori yang dipilih pada select grup.
 * - INCOME: hidden data-type=INCOME, label "Jumlah Pemasukan"
 * - EXPENSE: hidden data-type=EXPENSE, label "Jumlah Pengeluaran"
 *
 * @param {HTMLElement} groupEl - Grup kategori terkait.
 */
function syncBonCategoryFields(groupEl) {
    if (!groupEl) return;

    const select = groupEl.querySelector('.transaction-category-select');
    if (!select) return;

    const selected = select.options[select.selectedIndex];
    const categoryId = selected ? selected.value : '';
    const isIncome = selected && selected.dataset.type === 'INCOME';
    const labelText = (isIncome ? 'Jumlah Pemasukan' : 'Jumlah Pengeluaran') + ' <span class="text-error">*</span>';

    groupEl.querySelectorAll('.transaction-category-hidden').forEach(function (hidden) {
        hidden.value = categoryId;
        hidden.dataset.type = selected ? selected.dataset.type : '';
    });

    groupEl.querySelectorAll('.amount-label').forEach(function (label) {
        label.innerHTML = labelText;
    });
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

    container.querySelectorAll('.bon-block').forEach(function (block) {
        const catHidden = block.querySelector('.transaction-category-hidden');
        const amountInput = block.querySelector('.expense-amount-input');
        const amount = parseInt((amountInput ? amountInput.value : '').replace(/[^\d]/g, ''), 10) || 0;
        const type = catHidden ? catHidden.dataset.type : '';
        if (type === 'INCOME') {
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
        addTransactionCategoryGroup('transactionsContainer');
    }

    // ============================================================
    // STRUKTUR TRANSAKSI DINAMIS — MODAL EDIT (terisi transaksi existing,
    // dikelompokkan per kategori: 1 kategori = banyak keterangan/bon)
    // ============================================================

    document.querySelectorAll('[data-existing-items]').forEach(function (container) {
        let existingItems = [];
        try {
            existingItems = JSON.parse(container.dataset.existingItems || '[]');
        } catch (e) {
            existingItems = [];
        }

        const grouped = {};
        existingItems.forEach(function (item) {
            const key = item.transaction_category_id || 'none';
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(item);
        });

        Object.keys(grouped).forEach(function (key) {
            addTransactionCategoryGroup(container, {
                transaction_category_id: grouped[key][0].transaction_category_id,
                items: grouped[key],
            });
        });
    });

    // ============================================================
    // DELEGATION — ganti kategori & input jumlah (berlaku untuk
    // grup yang dibuat dinamis pada semua modal)
    // ============================================================

    document.addEventListener('change', function (e) {
        if (e.target.matches('.transaction-category-select')) {
            const groupEl = e.target.closest('.transaction-category-group');
            syncBonCategoryFields(groupEl);
            if (groupEl) updateTransactionsSummary(groupEl.closest('[data-categories]'));
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
