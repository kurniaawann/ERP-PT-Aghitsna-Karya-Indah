/* global openModal, closeModal, showToast, handleFormSubmit, resetFormSubmitState */

//  ================================================================
//
//  INVOICE SEMEN — Index (Finance)
//
//  Logika interaktif untuk modul Invoice Semen:
//  - Modal Tambah & Edit: tambah/hapus proyek & baris data semen,
//    dropdown pencarian data semen, auto-fill tanggal/qty/harga/jumlah,
//    penomoran ulang per proyek, preview total, dan serialize proyek
//    ke dalam hidden input JSON (`projects`).
//  - Modal Edit diisi via AJAX (GET /semen-invoice/{id}/edit).
//  - Bulk delete & select-all pada tabel.
//
//  ================================================================

//  ----------------------------------------------------------------
//  Konfigurasi scope untuk modal Tambah dan Edit.
//  Kedua modal memakai kelas DOM yang sama; elemen root dibedakan
//  lewat pemilih (selector) masing-masing.
//  ----------------------------------------------------------------
const ADD = {
    projectTemplate: '#semen-project-template',
    rowTemplate: '#semen-row-template',
    projectList: '#semen-project-list',
    projectsJson: '#semen-projects-json',
    totalPreview: '#invoice-total-preview',
    addProjectBtn: '#add-project-btn',
};

const EDIT = {
    projectTemplate: '#edit-semen-project-template',
    rowTemplate: '#edit-semen-row-template',
    projectList: '#edit-semen-project-list',
    projectsJson: '#edit-semen-projects-json',
    totalPreview: '#edit-invoice-total-preview',
    addProjectBtn: '#add-project-btn-edit',
};

//  ----------------------------------------------------------------
//  Utilitas kecil
//  ----------------------------------------------------------------

/** Format angka menjadi string ribuan Indonesia, mis. "1.500.000". */
function formatNumber(value) {
    return (Number(value) || 0).toLocaleString('id-ID');
}

/** Ambil template proyek sesuai scope. */
function getProjectTemplate(cfg) {
    return document.getElementById(cfg.projectTemplate.replace('#', ''));
}

/** Ambil template baris sesuai scope. */
function getRowTemplate(cfg) {
    return document.getElementById(cfg.rowTemplate.replace('#', ''));
}

/** Ambil kontainer daftar proyek sesuai scope. */
function getProjectList(cfg) {
    return document.getElementById(cfg.projectList.replace('#', ''));
}

//  ----------------------------------------------------------------
//  Data Semen — Sumber Dinamis dari Tabel `cements`
//  ----------------------------------------------------------------

/** Endpoint AJAX yang membaca Data Semen langsung dari tabel `cements`. */
const CEMENTS_DATA_URL = '/semen-invoice/cements-data';

/** Cache seluruh Data Semen hasil AJAX (dipakai untuk prefill saat edit). */
let cementsCache = null;

/** Promise fetch seluruh Data Semen agar tidak ada request ganda. */
let cementsFetchPromise = null;

/**
 * Fetch Data Semen dari tabel `cements` via AJAX.
 *
 * @param {string} [query] Kata kunci pencarian (no, proyek, nama).
 * @returns {Promise<Array<object>>} Array Data Semen.
 */
function fetchCements(query) {
    const params = new URLSearchParams();
    if (query) params.set('search', query);

    const url = `${CEMENTS_DATA_URL}${params.toString() ? `?${params}` : ''}`;

    return fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    })
        .then((response) => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then((data) => (Array.isArray(data) ? data : []));
}

/**
 * Ambil seluruh Data Semen dari database (dengan cache).
 * Dipakai untuk prefill baris saat modal edit diisi.
 *
 * @returns {Promise<Array<object>>}
 */
function loadAllCements() {
    if (cementsCache) return Promise.resolve(cementsCache);

    if (!cementsFetchPromise) {
        cementsFetchPromise = fetchCements('')
            .then((data) => {
                cementsCache = data;
                return data;
            })
            .finally(() => {
                cementsFetchPromise = null;
            });
    }

    return cementsFetchPromise;
}

/**
 * Render opsi data semen ke dalam dropdown dari hasil AJAX.
 * Opsi placeholder "-- Pilih Data Semen --" selalu tampil di atas.
 *
 * @param {HTMLElement} dropdown Elemen .cement-dropdown.
 * @param {Array<object>} cements  Array Data Semen dari tabel `cements`.
 */
function renderCementOptions(dropdown, cements) {
    if (!Array.isArray(cements)) return;

    const optionsDiv = dropdown.querySelector('.cement-options');
    if (!optionsDiv) return;

    optionsDiv.querySelectorAll('.cement-option').forEach((o) => o.remove());

    cements.forEach((c) => {
        const option = document.createElement('div');
        option.className = 'p-2 hover:bg-primary-light cursor-pointer border-b border-border-light cement-option';
        option.dataset.value = c.no || '';
        option.dataset.tanggal = c.tanggal || '';
        option.dataset.name = c.name || '';
        option.dataset.namaProyek = c.nama_proyek || '';
        option.dataset.jumlah = c.jumlah || '0';
        option.dataset.harga = c.harga || '0';
        option.dataset.satuan = c.satuan || '';
        option.dataset.search = String(c.no + ' ' + (c.nama_proyek || '') + ' ' + (c.name || '')).toLowerCase();

        const line1 = document.createElement('div');
        line1.className = 'font-medium text-text-heading text-xs';
        line1.textContent = `${c.no}`;

        const line2 = document.createElement('div');
        line2.className = 'text-xs text-text-secondary mt-0.5';
        line2.textContent = `${c.tanggal ? new Date(c.tanggal).toLocaleDateString('id-ID') : ''} • ${c.nama_proyek || '-'} • ${c.jumlah} ${c.satuan || ''} • Rp ${Number(c.harga || 0).toLocaleString('id-ID')}`;

        option.appendChild(line1);
        option.appendChild(line2);
        optionsDiv.appendChild(option);
    });
}

/**
 * Buka dropdown data semen: ambil data segar dari tabel `cements`
 * lalu render dan filter sesuai kata kunci saat ini.
 *
 * @param {HTMLElement} dropdown   Elemen .cement-dropdown.
 * @param {HTMLInputElement} searchInput Input pencarian baris.
 * @param {string} [query]  Kata kunci yang dikirim ke server (opsional).
 */
function openCementDropdown(dropdown, searchInput, query) {
    fetchCements(query || '')
        .then((data) => {
            renderCementOptions(dropdown, data);
            filterCementOptions(dropdown, searchInput?.value || '');
            dropdown.classList.remove('hidden');
        })
        .catch(() => {
            renderCementOptions(dropdown, []);
            filterCementOptions(dropdown, searchInput?.value || '');
            dropdown.classList.remove('hidden');
        });
}

//  ----------------------------------------------------------------
//  Manipulasi Proyek & Baris
//  ----------------------------------------------------------------

/**
 * Tambah satu blok proyek ke dalam scope.
 *
 * @param {module:scope} cfg  Konfigurasi scope (ADD / EDIT).
 * @param {object|null}  data Data proyek saat edit (nama_proyek, payment_account_id, items).
 * @returns {HTMLElement} Elemen proyek yang baru dibuat.
 */
function addProject(cfg, data) {
    const list = getProjectList(cfg);
    const template = getProjectTemplate(cfg);
    if (!template || !list) return null;

    const projectEl = template.content.cloneNode(true).firstElementChild;

    if (data) {
        const namaInput = projectEl.querySelector('.semen-nama-proyek');
        if (namaInput) namaInput.value = data.nama_proyek || '';

        const pengurusInput = projectEl.querySelector('.semen-pengurus');
        if (pengurusInput) pengurusInput.value = data.pengurus_proyek || '';

        const accountSelect = projectEl.querySelector('.semen-payment-account');
        if (accountSelect && data.payment_account_id) {
            accountSelect.value = data.payment_account_id;
        }
    }

    list.appendChild(projectEl);

    bindProjectEvents(projectEl, cfg);

    if (data && Array.isArray(data.items)) {
        data.items.forEach((item) => addRow(projectEl, cfg, item));
    } else {
        addRow(projectEl, cfg, null);
    }

    renumberProjects(cfg);
    syncJson(cfg);

    return projectEl;
}

/**
 * Tambah satu baris data semen ke dalam blok proyek.
 *
 * @param {HTMLElement} projectEl Elemen proyek.
 * @param {module:scope} cfg      Konfigurasi scope.
 * @param {object|null}  data     Data item saat edit (data_no, tanggal, nama_barang, qty, harga).
 * @returns {HTMLElement|null} Elemen baris yang baru dibuat.
 */
function addRow(projectEl, cfg, data) {
    const rowsBody = projectEl.querySelector('.semen-rows');
    const template = getRowTemplate(cfg);
    if (!rowsBody || !template) return null;

    const rowEl = template.content.cloneNode(true).firstElementChild;

    if (data) {
        const cement = (cementsCache || []).find((c) => c.no === data.data_no);

        const searchInput = rowEl.querySelector('.cement-search-input');
        if (searchInput) {
            searchInput.value = cement ? cement.no : (data.data_no || '');
        }

        const tanggal = rowEl.querySelector('.semen-tanggal');
        if (tanggal) tanggal.value = data.tanggal ? String(data.tanggal).split('T')[0] : '';

        const namaBarang = rowEl.querySelector('.semen-nama-barang');
        if (namaBarang) namaBarang.value = data.nama_barang || 'SEMEN';

        const qty = rowEl.querySelector('.semen-qty');
        const harga = rowEl.querySelector('.semen-harga');
        const jumlah = rowEl.querySelector('.semen-jumlah');

        if (cement) {
            if (harga) harga.value = cement.harga;
            if (qty) qty.value = data.qty || 0;
            if (jumlah) jumlah.value = formatNumber(data.qty * cement.harga);
        } else {
            const h = data.harga || 0;
            if (harga) harga.value = h;
            if (qty) qty.value = data.qty || 0;
            if (jumlah) jumlah.value = formatNumber(data.qty * h);
        }

        // Simpan data_no (no data semen) sebagai dataset untuk referensi.
        rowEl.dataset.cementNo = cement ? cement.no : (data.data_no || '');
    } else {
        // Baris baru kosong: tanggal mengikuti tanggal invoice (biarkan kosong).
        const tanggal = rowEl.querySelector('.semen-tanggal');
        if (tanggal) tanggal.value = '';
    }

    rowsBody.appendChild(rowEl);

    bindRowEvents(rowEl, cfg);

    renumberRows(projectEl);
    syncJson(cfg);

    return rowEl;
}

/**
 * Ikat event pada sebuah blok proyek (tambah baris, hapus proyek,
 * dan perubahan input proyek).
 */
function bindProjectEvents(projectEl, cfg) {
    const addRowBtn = projectEl.querySelector('.add-row-btn');
    if (addRowBtn) {
        addRowBtn.addEventListener('click', () => addRow(projectEl, cfg, null));
    }

    const removeBtn = projectEl.querySelector('.remove-project-btn');
    if (removeBtn) {
        removeBtn.addEventListener('click', () => {
            projectEl.remove();
            renumberProjects(cfg);
            syncJson(cfg);
        });
    }

    projectEl.querySelectorAll('.semen-nama-proyek, .semen-pengurus, .semen-payment-account').forEach((input) => {
        input.addEventListener('change', () => syncJson(cfg));
        input.addEventListener('input', () => syncJson(cfg));
    });
}

/**
 * Ikat event pada sebuah baris data semen:
 * - Dropdown cari data semen (focus, input filter, pilih opsi).
 * - Hapus baris.
 */
function bindRowEvents(rowEl, cfg) {
    const searchInput = rowEl.querySelector('.cement-search-input');
    const dropdown = rowEl.querySelector('.cement-dropdown');

    // Saat focus/input: ambil data semen segar dari tabel `cements` (AJAX)
    // agar daftar selalu mutakhir meskipun data ditambahkan belakangan.
    if (searchInput && dropdown) {
        let inputTimer = null;

        // Buka dropdown juga saat mousedown (sebelum event click global berjalan).
        searchInput.addEventListener('mousedown', () => {
            filterCementOptions(dropdown, searchInput.value);
            dropdown.classList.remove('hidden');
        });

        searchInput.addEventListener('focus', () => {
            openCementDropdown(dropdown, searchInput);
        });

        // Pencarian dinamis: debounce singkat agar tidak membebani server.
        searchInput.addEventListener('input', () => {
            clearTimeout(inputTimer);
            inputTimer = setTimeout(() => {
                openCementDropdown(dropdown, searchInput, searchInput.value.trim());
            }, 250);
        });

        // Klik opsi data semen: pilih & auto-fill.
        dropdown.addEventListener('click', (e) => {
            const option = e.target.closest('.cement-option');
            if (!option) return;

            selectCementOption(rowEl, option, cfg);
            dropdown.classList.add('hidden');
            searchInput.blur();
        });
    }

    const removeRowBtn = rowEl.querySelector('.remove-row-btn');
    if (removeRowBtn) {
        removeRowBtn.addEventListener('click', () => {
            rowEl.remove();
            const projectEl = rowEl.closest('.semen-project');
            if (projectEl) renumberRows(projectEl);
            syncJson(cfg);
        });
    }

    // Dukung pengisian manual qty (hitung ulang jumlah).
    const qty = rowEl.querySelector('.semen-qty');
    const harga = rowEl.querySelector('.semen-harga');
    if (qty) {
        qty.addEventListener('input', () => {
            const h = parseInt(harga ? harga.value : '0', 10) || 0;
            const jumlah = rowEl.querySelector('.semen-jumlah');
            if (jumlah) jumlah.value = formatNumber((parseInt(qty.value, 10) || 0) * h);
            syncJson(cfg);
        });
    }
}

/**
 * Pilih sebuah opsi data semen pada sebuah baris → auto-fill
 * tanggal, nama barang (SEMEN), qty, harga (hidden) & jumlah.
 */
function selectCementOption(rowEl, option, cfg) {
    const searchInput = rowEl.querySelector('.cement-search-input');
    const tanggal = rowEl.querySelector('.semen-tanggal');
    const namaBarang = rowEl.querySelector('.semen-nama-barang');
    const qty = rowEl.querySelector('.semen-qty');
    const harga = rowEl.querySelector('.semen-harga');
    const jumlah = rowEl.querySelector('.semen-jumlah');

    const cementData = option.dataset;
    const qtyVal = parseInt(cementData.jumlah, 10) || 0;
    const hargaVal = parseInt(cementData.harga, 10) || 0;

    if (searchInput) searchInput.value = cementData.value || '';
    rowEl.dataset.cementNo = cementData.value || '';

    if (tanggal) tanggal.value = cementData.tanggal || '';
    if (namaBarang) namaBarang.value = 'SEMEN';
    if (qty) qty.value = qtyVal;
    if (harga) harga.value = hargaVal;
    if (jumlah) jumlah.value = formatNumber(qtyVal * hargaVal);

    // Auto-fill nama pengurus proyek dari Data Semen yang dipilih.
    const projectEl = rowEl.closest('.semen-project');
    if (projectEl) {
        const pengurus = projectEl.querySelector('.semen-pengurus');
        if (pengurus && !pengurus.value.trim()) {
            pengurus.value = cementData.name || '';
        }
    }

    syncJson(cfg);
}

//  ----------------------------------------------------------------
//  Filter Dropdown Data Semen
//  ----------------------------------------------------------------

/**
 * Menampilkan/menyembunyikan opsi data semen berdasarkan kata kunci.
 * Opsi placeholder "-- Pilih Data Semen --" selalu tampil di atas.
 */
function filterCementOptions(dropdown, query) {
    const keyword = String(query || '').toLowerCase().trim();
    let matchCount = 0;

    dropdown.querySelectorAll('.cement-option').forEach((option) => {
        const searchText = String(option.dataset.search || '').toLowerCase();
        const match = !keyword || searchText.includes(keyword);
        option.classList.toggle('hidden', !match);
        if (match) matchCount += 1;
    });

    const noResults = dropdown.querySelector('.cement-no-results');
    if (noResults) noResults.classList.toggle('hidden', matchCount > 0);
}

//  ----------------------------------------------------------------
//  Penomoran & Preview
//  ----------------------------------------------------------------

/** Nomori ulang tampilan proyek di dalam scope (1, 2, 3, ...). */
function renumberProjects(cfg) {
    const projects = getProjectList(cfg);
    if (!projects) return;

    projects.querySelectorAll(':scope > .semen-project').forEach((project, index) => {
        const counter = project.querySelector('.semen-project-counter');
        if (counter) counter.textContent = index + 1;
    });
}

/** Nomori ulang tampilan baris di dalam satu proyek (mulai 1 lagi). */
function renumberRows(projectEl) {
    projectEl.querySelectorAll('.semen-rows .semen-row').forEach((row, index) => {
        const cell = row.querySelector('.semen-row-no');
        if (cell) cell.textContent = index + 1;
    });
}

/**
 * Serialize proyek & baris dalam scope ke hidden input JSON `projects`
 * dan perbarui preview total. Hanya proyek lengkap (nama proyek,
 * rekening, minimal 1 item dengan qty > 0) yang disertakan.
 */
function syncJson(cfg) {
    const list = getProjectList(cfg);
    const jsonInput = document.getElementById(cfg.projectsJson.replace('#', ''));
    if (!list || !jsonInput) return;

    const projects = [];
    let total = 0;

    list.querySelectorAll(':scope > .semen-project').forEach((project) => {
        const namaProyek = (project.querySelector('.semen-nama-proyek')?.value || '').trim();
        const pengurusProyek = (project.querySelector('.semen-pengurus')?.value || '').trim();
        const paymentAccountId = project.querySelector('.semen-payment-account')?.value || '';

        const items = [];
        project.querySelectorAll('.semen-rows .semen-row').forEach((row) => {
            const qty = parseInt(row.querySelector('.semen-qty')?.value || '0', 10) || 0;
            const harga = parseInt(row.querySelector('.semen-harga')?.value || '0', 10) || 0;

            if (qty < 1) return;

            const jumlah = qty * harga;
            total += jumlah;

            items.push({
                no: items.length + 1,
                data_no: row.dataset.cementNo || null,
                tanggal: row.querySelector('.semen-tanggal')?.value || null,
                nama_barang: (row.querySelector('.semen-nama-barang')?.value || 'SEMEN').trim() || 'SEMEN',
                qty,
                harga,
                jumlah,
            });
        });

        if (namaProyek && paymentAccountId && items.length) {
            projects.push({
                nama_proyek: namaProyek,
                pengurus_proyek: pengurusProyek,
                payment_account_id: paymentAccountId,
                items,
            });
        }
    });

    jsonInput.value = JSON.stringify(projects);

    const preview = document.getElementById(cfg.totalPreview.replace('#', ''));
    if (preview) preview.textContent = `Rp ${formatNumber(total)}`;
}

//  ----------------------------------------------------------------
//  Modal Edit (AJAX)
//  ----------------------------------------------------------------

/**
 * Buka modal edit dengan memuat data invoice via AJAX,
 * lalu mengisi form single-modal edit.
 *
 * @param {string} invoiceNumber Nomor invoice yang akan diedit.
 */
function openEditSemenModal(invoiceNumber) {
    const encoded = encodeURIComponent(invoiceNumber);

    fetch(`/semen-invoice/${encoded}/edit`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    })
        .then((response) => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then((data) => {
            const invoice = data.invoice || {};
            const projects = Array.isArray(data.projects) ? data.projects : [];

            const display = document.getElementById('edit-invoice-number-display');
            const hiddenNumber = document.getElementById('edit-invoice-number-hidden');
            const dateInput = document.getElementById('edit-invoice-date');
            const submitForm = document.getElementById('editSemenForm');

            if (display) display.value = invoice.invoice_number || '';
            if (hiddenNumber) hiddenNumber.value = invoice.invoice_number || '';
            if (dateInput) dateInput.value = invoice.invoice_date ? String(invoice.invoice_date).split('T')[0] : '';
            if (submitForm) submitForm.action = `/semen-invoice/${encoded}`;

            const list = getProjectList(EDIT);
            if (list) list.innerHTML = '';

            if (projects.length === 0) {
                addProject(EDIT, null);
            } else {
                projects.forEach((project) => addProject(EDIT, project));
            }

            openModal('editModal');
        })
        .catch(() => {
            showToast('Gagal memuat data invoice untuk diedit.', 'error');
        });
}

window.openEditSemenModal = openEditSemenModal;

//  ----------------------------------------------------------------
//  Bulk Delete & Select All
//  ----------------------------------------------------------------

/**
 * Reset tombol konfirmasi hapus ke kondisi semula.
 */
function resetDeleteButton() {
    const btn = document.getElementById('confirm-btn-deleteModal');
    if (!btn) return;
    btn.innerHTML = 'Ya, Hapus';
    btn.disabled = false;
    btn.classList.remove('opacity-70', 'cursor-not-allowed');
}

/**
 * Submit bulk delete via AJAX.
 */
function submitDeleteForm() {
    const checkboxes = document.querySelectorAll('input[name="selected_invoices[]"]:checked');

    if (checkboxes.length === 0) {
        showToast('Pilih minimal satu invoice untuk dihapus', 'error');
        return;
    }

    const deleteBtn = document.getElementById('confirm-btn-deleteModal');
    if (deleteBtn) {
        deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
        deleteBtn.disabled = true;
        deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    const form = document.getElementById('deleteForm');
    if (!form) {
        resetDeleteButton();
        return;
    }

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            closeModal('deleteModal');
            resetDeleteButton();

            if (data.success) {
                showToast(data.message || 'Invoice berhasil dihapus.', 'success');
                setTimeout(() => window.location.reload(), 800);
            } else {
                showToast(data.message || 'Gagal menghapus invoice.', 'error');
            }
        })
        .catch(() => {
            closeModal('deleteModal');
            resetDeleteButton();
            showToast('Terjadi kesalahan saat menghapus invoice.', 'error');
        });
}

window.submitDeleteForm = submitDeleteForm;

//  ----------------------------------------------------------------
//  Inisialisasi & Form Submit
//  ----------------------------------------------------------------

function initSemenModal(cfg) {
    const addBtn = document.getElementById(cfg.addProjectBtn.replace('#', ''));
    if (addBtn) {
        addBtn.addEventListener('click', () => addProject(cfg, null));
    }

    const list = getProjectList(cfg);
    if (list && list.querySelectorAll(':scope > .semen-project').length === 0) {
        addProject(cfg, null);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Preload seluruh Data Semen dari tabel `cements` (untuk prefill edit).
    loadAllCements();

    initSemenModal(ADD);
    initSemenModal(EDIT);

    // Tutup semua dropdown data semen saat klik di luar baris/wrapper-nya.
    // Wrapper (cement-search-wrap) membungkus input pencarian + dropdown,
    // sehingga mengklik keduanya tidak menutup dropdown.
    document.addEventListener('click', (e) => {
        const wrap = e.target.closest('.cement-search-wrap');
        document.querySelectorAll('.cement-dropdown:not(.hidden)').forEach((dd) => {
            if (!wrap || !wrap.contains(dd)) {
                dd.classList.add('hidden');
            }
        });
    });

    // Select all pada tabel.
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="selected_invoices[]"]');
    const deleteButton = document.getElementById('delete-button');

    function updateDeleteButton() {
        const checked = document.querySelectorAll('input[name="selected_invoices[]"]:checked').length;
        if (deleteButton) deleteButton.disabled = checked === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach((cb) => { cb.checked = this.checked; });
            updateDeleteButton();
        });
    }

    checkboxes.forEach((cb) => {
        cb.addEventListener('change', () => {
            const allChecked = Array.from(checkboxes).every((x) => x.checked);
            const someChecked = Array.from(checkboxes).some((x) => x.checked);
            if (selectAll) {
                selectAll.checked = allChecked;
                selectAll.indeterminate = !allChecked && someChecked;
            }
            updateDeleteButton();
        });
    });

    updateDeleteButton();

    // Submit Tambah: pastikan projects JSON terset sebelum submit.
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            syncJson(ADD);
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Submit Edit: pastikan projects JSON terset sebelum submit.
    const editForm = document.getElementById('editSemenForm');
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            syncJson(EDIT);
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Reset state submit saat halaman dimuat ulang.
    window.addEventListener('pageshow', () => resetFormSubmitState());
});