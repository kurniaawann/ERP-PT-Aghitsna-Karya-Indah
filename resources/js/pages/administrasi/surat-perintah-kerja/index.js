/**
 * Surat Perintah Kerja (SPK) - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Auto-generate nomor SPK dari server
 * - Select all checkbox
 * - Bulk delete button state
 * - Manajemen grup item (No/Kode > beberapa Keterangan)
 * - Perhitungan Jumlah = Volume x Harga & Total Pekerjaan
 * - Loading indicator pada tombol aksi
 */

/* ==========================================
 * HELPER: Submit Form Hapus dengan Loading
 * ========================================== */
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
 * AUTO-GENERATE NOMOR SPK
 * ========================================== */

/**
 * Mengambil nomor SPK berikutnya dari server lalu mengisi field #nomorSpk.
 * Nomor sebenarnya ditentukan server saat penyimpanan; nilai di sini hanya
 * preview untuk memudahkan pengguna.
 */
function loadNextNomor() {
    const field = document.getElementById('nomorSpk');
    if (!field) return;

    const route = field.dataset.nextNomorRoute;
    if (!route) return;

    fetch(route, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.nextNomor) {
                field.value = data.nextNomor;
            }
        })
        .catch(() => {
            field.value = '';
        });
}

/* ==========================================
 * PERHITUNGAN JUMLAH (VOLUME x HARGA)
 * ========================================== */

/**
 * Menghitung Jumlah pada satu baris keterangan berdasarkan Volume x Harga.
 * Jumlah ditampilkan sebagai Rupiah tanpa desimal.
 *
 * @param {HTMLElement} qtyInput     Input volume
 * @param {HTMLElement} priceInput   Input harga (format Rupiah)
 * @param {HTMLElement} amountInput  Input jumlah (readonly, format Rupiah)
 */
function computeItemAmount(qtyInput, priceInput, amountInput) {
    const qty = parseFloat(qtyInput.value) || 0;
    const price = parseCurrencyInput(priceInput.value);
    const amount = Math.round(qty * price);
    amountInput.value = amount ? amount.toLocaleString('id-ID') : '';
}

/**
 * Menghitung Total Pekerjaan pada sebuah modal (jumlah semua .form-amount)
 * lalu menampilkannya pada elemen .total-display milik modal tersebut.
 *
 * @param {HTMLElement} container  Kontainer grup items (mis. #groupsContainer-addModal)
 */
function recalculateModal(container) {
    if (!container) return;
    const modal = container.closest('[id^="addModal"], [id^="editModal-"]');
    if (!modal) return;

    let total = 0;
    modal.querySelectorAll('.form-amount').forEach(function (amountInput) {
        total += parseCurrencyInput(amountInput.value);
    });

    const display = modal.querySelector('.total-display');
    if (display) {
        display.textContent = 'Rp ' + Math.round(total).toLocaleString('id-ID');
    }
}

/**
 * Pasang listener pada volume & harga untuk menghitung jumlah + total.
 * Menggunakan event delegation pada document agar menangani baris detail
 * baru yang ditambahkan secara dinamis.
 */
function attachAmountListeners() {
    document.addEventListener('input', function (e) {
        const el = e.target;
        if (el.classList.contains('form-qty') || el.classList.contains('form-price')) {
            if (el.classList.contains('form-price')) {
                formatCurrencyInput(el);
            }
            const row = el.closest('.detail-row');
            if (!row) return;
            const qtyInput = row.querySelector('.form-qty');
            const priceInput = row.querySelector('.form-price');
            const amountInput = row.querySelector('.form-amount');
            if (qtyInput && priceInput && amountInput) {
                computeItemAmount(qtyInput, priceInput, amountInput);
                recalculateModal(row.closest('[id^="groupsContainer-"]'));
            }
        }
    });
}

/* ==========================================
 * MANAJEMEN GRUP ITEM (No/Kode)
 * ========================================== */

/**
 * Membuat markup HTML untuk satu baris keterangan (detail) dari sebuah grup.
 *
 * @param {number} gi  Indeks grup (No/Kode)
 * @returns {string} HTML baris keterangan
 */
function detailRowHtml(gi) {
    return `
        <div class="detail-row border border-border-strong rounded-lg p-3">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs font-semibold text-text-label">Keterangan <span class="text-error">*</span></span>
                <button type="button" onclick="removeDetailRow(this)" style="display: none;"
                    class="delete-detail-btn bg-btn-delete hover:bg-btn-delete-hover text-white px-2.5 py-1.5 rounded-lg transition-all duration-200"
                    title="Hapus Keterangan">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
            <textarea name="detail_keterangan[${gi}][]" rows="2" required
                class="w-full border border-border-strong rounded px-3 py-2 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                placeholder="Uraian pekerjaan..."></textarea>

            <div class="mt-2">
                <label class="block text-xs font-semibold text-text-label mb-1">Volume <span class="text-error">*</span></label>
                <input type="number" name="detail_volume[${gi}][]" step="any" min="0"
                    class="form-qty w-full border border-border-strong rounded px-2 py-2 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="0" required>
            </div>
            <div class="mt-2">
                <label class="block text-xs font-semibold text-text-label mb-1">Satuan</label>
                <input type="text" name="detail_satuan[${gi}][]"
                    class="w-full border border-border-strong rounded px-2 py-2 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="bh / m2 / lsn">
            </div>
            <div class="mt-2">
                <label class="block text-xs font-semibold text-text-label mb-1">Harga <span class="text-error">*</span></label>
                <input type="text" inputmode="numeric" name="detail_harga[${gi}][]"
                    class="form-price w-full border border-border-strong rounded px-2 py-2 text-sm text-right text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                    placeholder="Rp 0" required oninput="formatCurrencyInput(this)">
            </div>
            <div class="mt-2">
                <label class="block text-xs font-semibold text-text-label mb-1">Jumlah</label>
                <input type="text" name="detail_jumlah[${gi}][]" readonly
                    class="form-amount w-full border border-border-strong rounded px-2 py-2 text-sm text-right text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all bg-surface-hover"
                    placeholder="Rp 0">
            </div>
        </div>
    `;
}

/**
 * Membuat markup HTML untuk satu grup No/Kode (berisi satu baris keterangan awal).
 *
 * @param {number} gi  Indeks grup
 * @returns {string} HTML grup item
 */
function groupRowHtml(gi) {
    return `
        <div class="group-row bg-surface-base border-2 border-border-strong rounded p-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-semibold text-text-label mb-1.5">No</label>
                    <input type="number" name="no[${gi}]"
                        class="group-no w-full border border-border-strong rounded px-3 py-2.5 text-sm text-center text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        value="${gi + 1}" min="1" required readonly>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-text-label mb-1.5">Kode</label>
                    <input type="text" name="kode[${gi}]"
                        class="group-kode w-full border border-border-strong rounded px-3 py-2.5 text-sm text-text-input focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                        placeholder="Kode pekerjaan" maxlength="100">
                </div>
            </div>

            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-text-label">Keterangan</span>
                <button type="button" onclick="addDetailRow(this)"
                    class="bg-primary hover:bg-primary-hover text-white px-3 py-2 rounded text-xs font-medium shadow-sm transition-all duration-200">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah Keterangan
                </button>
            </div>

            <div class="details-container space-y-2">
                ${detailRowHtml(gi)}
            </div>

            <button type="button" onclick="removeGroupRow(this)" style="display: none;"
                class="delete-group-btn w-full bg-btn-delete hover:bg-btn-delete-hover text-white px-3 py-2.5 rounded text-sm font-medium shadow-sm transition-all duration-200 flex items-center justify-center gap-2 mt-3">
                <i class="fa-solid fa-trash"></i>
                <span>Hapus No/Kode</span>
            </button>
        </div>
    `;
}

/**
 * Menambahkan grup No/Kode baru ke dalam container.
 *
 * @param {string} modalId - ID modal (addModal atau editModal-{nomor})
 */
function addGroupRow(modalId) {
    const container = document.getElementById('groupsContainer-' + modalId);
    if (!container) return;

    const gi = container.querySelectorAll('.group-row').length;
    container.insertAdjacentHTML('beforeend', groupRowHtml(gi));

    reindexGroups(container);
    updateGroupDeleteVisibility(container);
    recalculateModal(container);
}

/**
 * Menambahkan baris keterangan baru pada grup yang berisi tombol ini.
 *
 * @param {HTMLElement} button - Tombol "Tambah Keterangan"
 */
function addDetailRow(button) {
    const groupRow = button.closest('.group-row');
    if (!groupRow) return;
    const details = groupRow.querySelector('.details-container');
    if (!details) return;

    const container = groupRow.closest('[id^="groupsContainer-"]');
    const gi = Array.from(container.querySelectorAll('.group-row')).indexOf(groupRow);

    details.insertAdjacentHTML('beforeend', detailRowHtml(gi));

    reindexGroups(container);
    updateDetailDeleteVisibility(groupRow);
    recalculateModal(container);
}

/**
 * Menghapus baris keterangan dari sebuah grup.
 *
 * @param {HTMLElement} button - Tombol hapus keterangan
 */
function removeDetailRow(button) {
    const row = button.closest('.detail-row');
    if (!row) return;
    const groupRow = row.closest('.group-row');
    const container = groupRow ? groupRow.closest('[id^="groupsContainer-"]') : null;

    row.remove();
    if (groupRow) {
        updateDetailDeleteVisibility(groupRow);
    }
    if (container) {
        reindexGroups(container);
        recalculateModal(container);
    }
}

/**
 * Menghapus satu grup No/Kode beserta seluruh keterangannya.
 *
 * @param {HTMLElement} button - Tombol hapus grup
 */
function removeGroupRow(button) {
    const groupRow = button.closest('.group-row');
    if (!groupRow) return;
    const container = groupRow.closest('[id^="groupsContainer-"]');

    groupRow.remove();
    if (container) {
        reindexGroups(container);
        updateGroupDeleteVisibility(container);
        recalculateModal(container);
    }
}

/**
 * Menormalisasi ulang indeks & nomor seluruh grup pada container.
 *
 * Alur:
 * 1. Untuk tiap .group-row (urutan DOM), tetapkan No = posisi + 1.
 * 2. Perbarui atribut name input grup (no[gi], kode[gi]).
 * 3. Perbarui atribut name seluruh baris keterangan di dalamnya
 *    (detail_keterangan[gi][], dst.).
 *
 * @param {HTMLElement} container  Kontainer grup items
 */
function reindexGroups(container) {
    if (!container) return;
    container.querySelectorAll('.group-row').forEach(function (groupRow, gi) {
        const noInput = groupRow.querySelector('.group-no');
        if (noInput) {
            noInput.value = gi + 1;
            noInput.name = 'no[' + gi + ']';
        }
        const kodeInput = groupRow.querySelector('.group-kode');
        if (kodeInput) {
            kodeInput.name = 'kode[' + gi + ']';
        }

        groupRow.querySelectorAll('.detail-row').forEach(function (detailRow) {
            [
                'detail_keterangan',
                'detail_volume',
                'detail_satuan',
                'detail_harga',
                'detail_jumlah',
            ].forEach(function (field) {
                const input = detailRow.querySelector('[name^="' + field + '"]');
                if (input) {
                    input.name = field + '[' + gi + '][]';
                }
            });
        });
    });
}

/**
 * Memperbarui visibilitas tombol hapus grup (hanya jika lebih dari 1 grup).
 *
 * @param {HTMLElement} container  Kontainer grup items
 */
function updateGroupDeleteVisibility(container) {
    if (!container) return;
    const groupRows = container.querySelectorAll('.group-row');
    groupRows.forEach(function (row) {
        const btn = row.querySelector('.delete-group-btn');
        if (btn) {
            btn.style.display = groupRows.length > 1 ? 'flex' : 'none';
        }
    });
}

/**
 * Memperbarui visibilitas tombol hapus keterangan (hanya jika > 1 baris).
 *
 * @param {HTMLElement} groupRow  Baris grup yang berisi daftar keterangan
 */
function updateDetailDeleteVisibility(groupRow) {
    if (!groupRow) return;
    const atLeastThree = false;
    const details = groupRow.querySelectorAll('.detail-row');
    details.forEach(function (row) {
        const btn = row.querySelector('.delete-detail-btn');
        if (btn) {
            btn.style.display = details.length > 1 ? 'flex' : 'none';
        }
    });
    void atLeastThree;
}

/* ==========================================
 * STATUS TOMBOL (SELECT ALL & DELETE)
 * ========================================== */

/**
 * Memperbarui status tombol hapus berdasarkan checkbox yang dipilih.
 */
function updateButtonStates() {
    const deleteButton = document.getElementById('delete-button');
    const checkedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    const count = checkedCheckboxes.length;

    if (deleteButton) {
        if (count > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
}

/* ==========================================
 * SANITASI INPUT RUPIAH SEBELUM SUBMIT
 * ========================================== */

/**
 * Mengubah input harga berformat Rupiah ("1.500.000") menjadi angka murni
 * ("1500000") sebelum form dikirim agar validasi numeric & penyimpanan
 * server berjalan benar.
 *
 * @param {HTMLFormElement} form  Elemen form yang akan disanitasi
 */
function sanitizeCurrencyInputs(form) {
    if (!form) return;
    form.querySelectorAll('.form-price, .form-amount').forEach(function (input) {
        input.value = String(parseCurrencyInput(input.value));
    });
}

/* ==========================================
 * INISIALISASI HALAMAN
 * ========================================== */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Auto-generate Nomor SPK ──────────────────────────────────
    loadNextNomor();

    // ─── Perhitungan Jumlah & Total ──────────────────────────────
    attachAmountListeners();

    // ─── Checkbox Pilih Semua ─────────────────────────────────────
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

    // ─── Inisialisasi Visibilitas Tombol Hapus & Total ───────────
    document.querySelectorAll('[id^="groupsContainer-"]').forEach(function (container) {
        reindexGroups(container);
        updateGroupDeleteVisibility(container);
        container.querySelectorAll('.group-row').forEach(function (groupRow) {
            updateDetailDeleteVisibility(groupRow);
        });
        recalculateModal(container);
    });

    // ─── Form Submit dengan Loading Indicator: Modal Tambah ─────
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            sanitizeCurrencyInputs(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (!handleFormSubmit(submitBtn, originalText, 'Menyimpan...')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // ─── Form Submit dengan Loading Indicator: Modal Edit ────────
    document.querySelectorAll('[id^="editModal-"] form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            sanitizeCurrencyInputs(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (!handleFormSubmit(submitBtn, originalText, 'Memperbarui...')) {
                e.preventDefault();
                return false;
            }
        });
    });

    // ─── Form Hapus: Cegah Double Submit ─────────────────────────
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

window.addEventListener('pageshow', function () {
    resetFormSubmitState();
});

window.addGroupRow = addGroupRow;
window.addDetailRow = addDetailRow;
window.removeGroupRow = removeGroupRow;
window.removeDetailRow = removeDetailRow;
