/**
 * Laporan Keuangan Proyek — Halaman Form Tambah Transaksi / Edit (gaya Filament)
 *
 * Pengganti modal Tambah & modal Edit gabungan pada halaman daftar
 * (logika dipindah dari index.js, tampilan blok dibuat ulang):
 * - Struktur transaksi dinamis (mirip Detail Pekerjaan RAB): pilih kategori →
 *   blok "Bon" bernomor otomatis (Bon No. 1, No. 2, ...), isi keterangan &
 *   jumlah. Label jumlah menyesuaikan tipe kategori (Pemasukan/Pengeluaran).
 *   Tambah: satu grup kosong (atau transaksi dari old() saat gagal simpan).
 *   Edit: terisi otomatis dari transaksi existing (grup tertutup).
 * - Transaksi otomatis (Bukti Pembayaran, Payroll, Kasbon Divisi) terkunci:
 *   tidak bisa dihapus dari sini.
 * - Rekapitulasi otomatis (Total Pemasukan, Total Pengeluaran, Saldo) dan
 *   peringatan "pemasukan baru melebihi sisa pembayaran" (tombol Simpan
 *   dinonaktifkan).
 * - Format input currency (Rupiah)
 * - Penanganan submit form (cegah double submit) & reset saat kembali (bfcache)
 */

// ============================================================
// IKON (Heroicons outline, sama dengan x-fi.icon)
// ============================================================

const icon = (path, cls) =>
    `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="${cls}"><path stroke-linecap="round" stroke-linejoin="round" d="${path}"/></svg>`;

const ICON = {
    trash: 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
    chevronDown: 'm19.5 8.25-7.5 7.5-7.5-7.5',
    plus: 'M12 4.5v15m7.5-7.5h-15',
    lock: 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
    info: 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
    warning: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
    paperClip: 'm18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13',
};

/** Tanda wajib isi pada label (sama dengan x-fi.field required). */
const REQUIRED = '<sup class="ms-0.5 font-medium text-red-600">*</sup>';

const LABEL_CLASS = 'text-sm font-medium leading-6 text-gray-950';

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
 * Format angka ke Rupiah (Rp X.XXX).
 * @param {number} value
 * @returns {string}
 */
function formatRupiahId(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

/**
 * Ambil elemen kontainer transaksi dari id atau elemen.
 * @param {string|HTMLElement} containerId - ID atau elemen kontainer.
 * @returns {HTMLElement|null}
 */
function getTransactionContainer(containerId) {
    return typeof containerId === 'string' ? document.getElementById(containerId) : containerId;
}

/**
 * Jumlah (angka) pada satu blok bon.
 * @param {HTMLElement} block
 * @returns {number}
 */
function blockAmount(block) {
    const amountInput = block.querySelector('.expense-amount-input');
    return parseInt((amountInput ? amountInput.value : '').replace(/[^\d]/g, ''), 10) || 0;
}

/**
 * Tipe kategori (INCOME/EXPENSE) satu blok bon.
 * @param {HTMLElement} block
 * @returns {string}
 */
function blockType(block) {
    const catHidden = block.querySelector('.transaction-category-hidden');
    return catHidden ? catHidden.dataset.type : '';
}

/**
 * Total pemasukan (uang masuk / kategori INCOME) pada kontainer transaksi.
 * Baris informasi (data-informational) tidak dihitung.
 *
 * @param {HTMLElement} container - Kontainer transaksi.
 * @returns {number}
 */
function computeTotalIncome(container) {
    let totalIncome = 0;

    container.querySelectorAll('.bon-block').forEach(function (block) {
        if (block.dataset.informational === '1') return;
        if (blockType(block) === 'INCOME') totalIncome += blockAmount(block);
    });

    return totalIncome;
}

/**
 * Total pemasukan dari transaksi EXISTING (punya hidden `items[N][id]`).
 *
 * Sisa Pembayaran rekap (`data-remaining-amount`) sudah mengurangi pemasukan
 * yang sudah tercatat di database, sehingga pemasukan existing tidak boleh
 * dihitung lagi saat membandingkan dengan sisa (menghindari hitung ganda).
 *
 * @param {HTMLElement} container - Kontainer transaksi.
 * @returns {number}
 */
function computeExistingIncome(container) {
    let existingIncome = 0;

    container.querySelectorAll('.bon-block').forEach(function (block) {
        if (block.dataset.informational === '1') return;
        if (!block.querySelector('input[name^="items["][name$="][id]"]')) return;
        if (blockType(block) === 'INCOME') existingIncome += blockAmount(block);
    });

    return existingIncome;
}

/**
 * Nonaktifkan/aktifkan tombol Simpan form sesuai kondisi pemasukan baru
 * melebihi sisa pembayaran.
 *
 * @param {HTMLElement} container - Kontainer transaksi.
 * @param {boolean} disabled
 */
function setRecapSubmitDisabled(container, disabled) {
    const form = container.closest('form');
    const btn = form ? form.querySelector('button[type="submit"]') : null;
    if (!btn) return;

    btn.disabled = disabled;
    btn.classList.toggle('opacity-50', disabled);
    btn.classList.toggle('cursor-not-allowed', disabled);
}

/**
 * Menampilkan/menyembunyikan peringatan "pemasukan melebihi sisa pembayaran".
 *
 * Yang dibandingkan adalah PEMASUKAN BARU (total pemasukan pada form dikurangi
 * pemasukan existing) terhadap Sisa Pembayaran rekap (`data-remaining-amount`),
 * karena sisa pembayaran sudah memperhitungkan pemasukan yang sudah tercatat.
 *
 * Elemen peringatan memakai ID turunan `{containerId}-incomeWarning`. Nilai
 * sisa pembayaran juga ditampilkan di Ringkasan ([data-summary="remaining"]).
 *
 * @param {string|HTMLElement} containerId - ID atau elemen kontainer transaksi.
 */
function updateRecapIncomeWarning(containerId) {
    const container = getTransactionContainer(containerId);
    if (!container || !container.id) return;

    const remaining = parseInt(container.dataset.remainingAmount, 10);

    document.querySelectorAll('[data-summary="remaining"]').forEach(function (el) {
        el.textContent = isNaN(remaining) ? '-' : formatRupiahId(remaining);
    });

    const warningEl = document.getElementById(container.id + '-incomeWarning');
    if (!warningEl) return;

    if (isNaN(remaining)) {
        warningEl.classList.add('hidden');
        setRecapSubmitDisabled(container, false);
        return;
    }

    const totalIncome = computeTotalIncome(container);
    const existingIncome = computeExistingIncome(container);
    const newIncome = totalIncome - existingIncome;

    const exceeds = newIncome > remaining;

    setRecapSubmitDisabled(container, exceeds);

    if (exceeds) {
        warningEl.innerHTML = '<span class="flex gap-x-2">' + icon(ICON.warning, 'mt-0.5 h-5 w-5 shrink-0') +
            '<span>Pemasukan baru <strong>' + formatRupiahId(newIncome) + '</strong> melebihi Sisa Pembayaran proyek <strong>' +
            formatRupiahId(remaining) + '</strong>. Jumlah pemasukan tidak boleh melebihi sisa pembayaran.</span></span>';
        warningEl.classList.remove('hidden');
    } else {
        warningEl.classList.add('hidden');
    }
}

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
 * Bangun HTML option kategori untuk select dalam grup.
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
 * @param {Object} [data] Data transaksi existing (edit) / old input (tambah).
 * @param {number} [index] Indeks baris transaksi pada array `items`.
 * @returns {string}
 */
function bonBlockHtml(categories, data, index) {
    data = data || {};
    index = index == null ? 0 : index;

    // Item yang dibuat otomatis oleh sistem tidak boleh dihapus dari modul ini:
    // - dari bukti pembayaran (payment_proof_id terisi) → hapus lewat modul Bukti Pembayaran
    // - dari payroll (auto_source = 'payroll', baris "Upah Kerja periode ...")
    // - dari kasbon divisi (auto_source = 'kasbon', baris "Kasbon Divisi ...")
    const isProofProtected = !!data.payment_proof_id;
    const isAutoProtected = !isProofProtected && (data.auto_source === 'payroll' || data.auto_source === 'kasbon');
    const isProtected = isProofProtected || isAutoProtected;
    const isInformational = !!data.is_informational;
    const amountLocked = isInformational || isProofProtected;

    const protectedLabel = isProofProtected
        ? 'Dari Bukti Pembayaran'
        : (data.auto_source === 'payroll'
            ? 'Dari Payroll'
            : (data.auto_source === 'kasbon' ? 'Dari Kasbon Divisi' : ''));

    const protectedHint = isProofProtected
        ? 'Transaksi otomatis dari Bukti Pembayaran (tanggal, jumlah & file mengikuti bukti pembayaran). Hapus bukti pembayarannya di modul Bukti Pembayaran untuk menghapus transaksi ini.'
        : (data.auto_source === 'payroll'
            ? 'Transaksi otomatis dari payroll. Hapus payrollnya di modul Payroll untuk menghapus transaksi ini.'
            : 'Transaksi otomatis dari kasbon divisi. Hapus kasbonnya di modul Kasbon untuk menghapus transaksi ini.');

    const actionButton = isProtected
        ? '<span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600" title="Transaksi otomatis, hapus lewat modul sumbernya">' +
            icon(ICON.lock, 'h-3.5 w-3.5') + ' ' + protectedLabel + '</span>'
        : '<button type="button" onclick="removeBonBlock(this)" class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-medium text-red-600 transition duration-75 hover:bg-red-50" title="Hapus keterangan">' +
            icon(ICON.trash, 'h-4 w-4') + ' Hapus</button>';

    const protectedNotice = isProtected
        ? '<p class="mt-4 flex gap-x-2 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">' +
            icon(ICON.info, 'mt-0.5 h-4 w-4 shrink-0 text-primary') + '<span>' + protectedHint + '</span></p>'
        : '';

    const idHidden = data.id
        ? '<input type="hidden" name="items[' + index + '][id]" value="' + escapeHtml(data.id) + '">'
        : '';

    const catHidden = '<input type="hidden" class="transaction-category-hidden" ' +
        'name="items[' + index + '][transaction_category_id]" value="' + escapeHtml(data.transaction_category_id || '') + '" ' +
        'data-type="' + escapeHtml(data.category_type || '') + '">';

    // Link bukti dibuka sebagai pratinjau di dalam halaman (window.openFilePreview), bukan tab baru
    const proofNotice = (data.proof_url && data.proof_file_name)
        ? '<p class="flex items-center gap-1 text-sm text-gray-500">' + icon(ICON.paperClip, 'h-4 w-4 shrink-0') +
            'Bukti saat ini: <a href="' + escapeHtml(data.proof_url) + '" data-proof-preview data-download-name="' +
            escapeHtml(data.proof_file_name) + '" class="truncate font-medium text-primary hover:underline">' +
            escapeHtml(data.proof_file_name) + '</a></p>'
        : '';

    const infoBadge = isInformational
        ? '<span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-primary ring-1 ring-inset ring-primary/10" title="Baris informasi — tidak memengaruhi total laporan">' +
            icon(ICON.info, 'h-3.5 w-3.5') + ' Info</span>'
        : '';

    return `
        <div class="bon-block ${isProtected ? 'bon-block-protected ' : ''}rounded-lg bg-white p-4 ring-1 ring-gray-950/10"${isProtected ? ' data-protected-source="' + (isProofProtected ? 'proof' : data.auto_source) + '"' : ''}${isInformational ? ' data-informational="1"' : ''}>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <span class="bon-number text-sm font-semibold text-gray-950">Bon No. 1</span>
                <span class="flex flex-wrap items-center gap-2">
                    ${infoBadge}
                    ${actionButton}
                </span>
            </div>

            ${idHidden}
            ${catHidden}

            <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-2">
                <div class="grid content-start gap-y-2">
                    <label class="${LABEL_CLASS}">Tanggal${REQUIRED}</label>
                    <div class="fi-datepicker">
                        <input type="date" data-fi-datepicker name="items[${index}][transaction_date]" class="fi-control"
                            value="${escapeHtml(data.transaction_date || '')}" required
                            ${isProofProtected ? 'readonly' : ''}
                            oninvalid="this.setCustomValidity('Tanggal tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                    </div>
                </div>

                <div class="grid content-start gap-y-2">
                    <label class="amount-label ${LABEL_CLASS}">Jumlah Pengeluaran${REQUIRED}</label>
                    <div class="flex w-full overflow-hidden rounded-lg shadow-sm ring-1 ring-gray-950/10 transition duration-75 ${amountLocked ? 'bg-gray-50' : 'bg-white focus-within:ring-2 focus-within:ring-primary'}">
                        <div class="flex shrink-0 items-center border-e border-gray-200 pe-2 ps-3">
                            <span class="whitespace-nowrap text-sm text-gray-500">Rp</span>
                        </div>
                        <input type="text" inputmode="numeric" name="items[${index}][expense_amount]"
                            class="expense-amount-input block w-full min-w-0 border-none bg-transparent py-1.5 pe-3 ps-3 text-base outline-none placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6 ${amountLocked ? 'cursor-not-allowed text-gray-500' : 'text-gray-950'}"
                            placeholder="Contoh: 50000" value="${escapeHtml(formatNumber(data.amount))}" required min="0"
                            ${amountLocked ? 'readonly' : ''}
                            oninvalid="this.setCustomValidity('Jumlah tidak boleh kosong')" oninput="this.setCustomValidity('')">
                    </div>
                    ${isInformational
                        ? '<p class="text-sm text-gray-500">Jumlah ini otomatis dari kasbon divisi dan hanya bersifat informasi (tidak memengaruhi total laporan).</p>'
                        : ''}
                </div>

                <div class="grid content-start gap-y-2 sm:col-span-2">
                    <label class="description-label ${LABEL_CLASS}">Keterangan${REQUIRED}</label>
                    <div class="flex w-full rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus-within:ring-2 focus-within:ring-primary">
                        <textarea name="items[${index}][description]" rows="2" maxlength="1000"
                            class="block w-full resize-y border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6"
                            placeholder="Contoh: Kasbon Transport Tukang"
                            oninvalid="this.setCustomValidity('Keterangan tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">${escapeHtml(data.description || '')}</textarea>
                    </div>
                </div>

                <div class="grid content-start gap-y-2">
                    <label class="${LABEL_CLASS}">Keterangan Bon</label>
                    <input type="text" name="items[${index}][keterangan_bon]" class="fi-control"
                        value="${escapeHtml(data.keterangan_bon || '')}"
                        placeholder="Contoh: Bon Pembelian Material" maxlength="255">
                </div>

                <div class="grid content-start gap-y-2">
                    <label class="${LABEL_CLASS}">Bukti Pembayaran</label>
                    <input type="file" name="items[${index}][proof_file]"
                        accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
                        class="block w-full rounded-lg bg-white text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/10 file:me-3 file:cursor-pointer file:rounded-s-lg file:border-0 file:border-e file:border-gray-200 file:bg-gray-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:bg-gray-50 disabled:opacity-70"
                        ${isProofProtected ? 'disabled' : ''}>
                    <p class="text-sm text-gray-500">Opsional. Format gambar: JPG, PNG, GIF, WEBP, BMP. Maksimal 5 MB.
                        Kosongkan jika tidak ingin mengubah file.</p>
                    ${proofNotice}
                </div>
            </div>

            ${protectedNotice}
        </div>
    `;
}

/**
 * Bangun HTML satu grup kategori (header + select kategori + kontainer bons).
 *
 * Kategori dipilih sekali per grup; seluruh blok bon di dalamnya mewarisi
 * kategori tersebut lewat hidden `items[index][transaction_category_id]`,
 * sehingga satu kategori bisa memiliki banyak keterangan.
 *
 * @param {Array<{id:number,name:string,type:string}>} categories
 * @param {Object} [data] Data grup (transaction_category_id, items, collapsed).
 * @returns {string}
 */
function transactionCategoryGroupHtml(categories, data) {
    data = data || {};

    // Grup existing (dimuat dari transaksi tersimpan pada halaman Edit) dimuat
    // dalam keadaan TERTUTUP — hanya header yang tampil. Grup baru (Tambah
    // Kategori) & grup hasil old input langsung terbuka agar bisa segera diisi.
    const collapsed = !!data.collapsed;

    return `
        <div class="transaction-category-group overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/10">
            <div class="flex cursor-pointer select-none items-center justify-between gap-3 bg-gray-50 px-4 py-3"
                onclick="toggleTransactionCategoryGroup(this)" role="button" tabindex="0" aria-expanded="${collapsed ? 'false' : 'true'}"
                onkeydown="if (event.target === this && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); toggleTransactionCategoryGroup(this); }">
                <span class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="transaction-category-number text-sm font-semibold text-gray-950">Kategori No. 1</span>
                    <span class="transaction-category-summary truncate text-sm text-gray-500"></span>
                </span>
                <span class="flex shrink-0 items-center gap-1">
                    <button type="button" onclick="event.stopPropagation(); removeTransactionCategoryGroup(this)"
                        class="delete-category-group-btn inline-flex items-center gap-1 rounded-lg px-2 py-1 text-sm font-medium text-red-600 transition duration-75 hover:bg-red-50"
                        title="Hapus kategori beserta semua keterangannya">
                        ${icon(ICON.trash, 'h-4 w-4')}
                        <span class="hidden sm:inline">Hapus Kategori</span>
                    </button>
                    ${icon(ICON.chevronDown, 'category-group-chevron h-5 w-5 text-gray-400 transition-transform duration-200' + (collapsed ? '' : ' rotate-180'))}
                </span>
            </div>

            <div class="category-group-body grid gap-4 border-t border-gray-200 p-4 ${collapsed ? 'hidden' : ''}">
                <div class="grid content-start gap-y-2">
                    <label class="${LABEL_CLASS}">Kategori${REQUIRED}</label>
                    <div class="relative flex w-full rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 transition duration-75 focus-within:ring-2 focus-within:ring-primary">
                        <select class="transaction-category-select block w-full min-w-0 cursor-pointer appearance-none border-none bg-transparent py-1.5 pe-9 ps-3 text-base text-gray-950 outline-none sm:text-sm sm:leading-6"
                            required data-fi-select="search" aria-label="Kategori"
                            oninvalid="this.setCustomValidity('Kategori tidak boleh kosong')"
                            oninput="this.setCustomValidity('')">
                            ${categoryOptionsHtml(categories, data.transaction_category_id)}
                        </select>
                        ${icon(ICON.chevronDown, 'pointer-events-none absolute end-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400')}
                    </div>
                </div>

                <div class="bon-blocks grid gap-4"></div>

                <button type="button" onclick="addBonBlock(this)"
                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-dashed border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 transition duration-75 hover:border-primary hover:text-primary">
                    ${icon(ICON.plus, 'h-5 w-5')}
                    Tambah Keterangan
                </button>
            </div>
        </div>
    `;
}

/**
 * Membuka/menutup (collapse/expand) isi grup kategori transaksi.
 *
 * @param {HTMLElement} header - Elemen header grup kategori (berisi .category-group-chevron).
 * @param {boolean} [forceOpen] true → selalu buka.
 */
function toggleTransactionCategoryGroup(header, forceOpen) {
    const groupEl = header ? header.closest('.transaction-category-group') : null;
    const body = groupEl ? groupEl.querySelector('.category-group-body') : null;
    const chevron = header ? header.querySelector('.category-group-chevron') : null;
    if (!body) return;

    const open = forceOpen === true ? true : body.classList.contains('hidden');
    body.classList.toggle('hidden', !open);
    if (chevron) chevron.classList.toggle('rotate-180', open);
    header.setAttribute('aria-expanded', open ? 'true' : 'false');
}
window.toggleTransactionCategoryGroup = toggleTransactionCategoryGroup;

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
 * @param {Object} [data] Data transaksi (untuk blok existing / old input).
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
        updateCategoryGroupDeleteButton(groupEl);
        updateTransactionsSummary(container);
    }
}
window.removeBonBlock = removeBonBlock;

/**
 * Menambahkan satu grup kategori baru (bernomor otomatis) ke kontainer.
 * Setiap grup berisi select kategori + blok bon (minimal satu blok kosong).
 *
 * @param {string|HTMLElement} containerId - ID atau elemen kontainer transaksi.
 * @param {Object} [data] Data grup (transaction_category_id, items, collapsed).
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
    updateCategoryGroupDeleteButton(groupEl);
    updateTransactionsSummary(container);
}
window.addTransactionCategoryGroup = addTransactionCategoryGroup;

/**
 * Membangun pesan toast untuk grup yang berisi blok transaksi otomatis.
 *
 * @param {Set<string>} sources - Kumpulan sumber proteksi ('proof', 'payroll', 'kasbon').
 * @returns {string}
 */
function buildProtectedGroupMessage(sources) {
    const labels = [];
    if (sources.has('proof')) labels.push('Bukti Pembayaran');
    if (sources.has('payroll')) labels.push('Payroll');
    if (sources.has('kasbon')) labels.push('Kasbon Divisi');

    const where = labels.join(', ');
    return 'Kategori ini berisi transaksi otomatis dari ' + where + ' yang tidak dapat dihapus. ' +
        'Hapus sumber transaksinya terlebih dahulu pada modul terkait.';
}

/**
 * Hapus satu grup kategori beserta semua blok bon-nya.
 *
 * Grup yang berisi blok "Bon" otomatis (bon-block-protected) tidak dapat
 * dihapus — transaksi tersebut hanya bisa hilang bila sumbernya dihapus di
 * modul Bukti Pembayaran, Payroll, atau Kasbon.
 *
 * @param {HTMLElement} button - Tombol hapus pada grup kategori.
 */
function removeTransactionCategoryGroup(button) {
    const groupEl = button.closest('.transaction-category-group');
    const container = groupEl ? groupEl.closest('[data-categories]') : null;

    const protectedBlocks = groupEl ? groupEl.querySelectorAll('.bon-block-protected') : [];
    if (protectedBlocks.length) {
        const sources = new Set();
        protectedBlocks.forEach(function (block) {
            sources.add(block.dataset.protectedSource || 'proof');
        });

        const message = buildProtectedGroupMessage(sources);
        if (typeof window.showToast === 'function') {
            window.showToast(message, 'warning');
        } else {
            alert(message);
        }
        return;
    }

    if (groupEl) groupEl.remove();
    if (container) {
        renumberTransactionBonBlocks(container);
        updateTransactionsSummary(container);
    }
}
window.removeTransactionCategoryGroup = removeTransactionCategoryGroup;

/**
 * Menyinkronkan tampilan tombol "Hapus Kategori" berdasarkan isi grup.
 *
 * Jika grup mengandung blok "Bon" otomatis, tombol dinonaktifkan secara
 * visual karena grup tidak boleh dihapus.
 *
 * @param {HTMLElement} groupEl - Grup kategori terkait.
 */
function updateCategoryGroupDeleteButton(groupEl) {
    if (!groupEl) return;
    const hasProtected = !!groupEl.querySelector('.bon-block-protected');
    const btn = groupEl.querySelector('.delete-category-group-btn');
    if (!btn) return;

    if (hasProtected) {
        const sources = new Set();
        groupEl.querySelectorAll('.bon-block-protected').forEach(function (block) {
            sources.add(block.dataset.protectedSource || 'proof');
        });
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.title = 'Kategori berisi transaksi otomatis (' + buildProtectedGroupMessage(sources) + ')';
    } else {
        btn.classList.remove('opacity-50', 'cursor-not-allowed');
        btn.title = 'Hapus kategori beserta semua keterangannya';
    }
}

/**
 * Memperbarui nomor tampilan tiap grup kategori ("Kategori No. X"), blok bon
 * di dalamnya ("Bon No. Y"), dan ringkasan header grup (nama kategori ·
 * jumlah bon — terlihat saat grup tertutup).
 *
 * @param {HTMLElement} container - Kontainer daftar transaksi.
 */
function renumberTransactionBonBlocks(container) {
    if (!container) return;

    const groups = container.querySelectorAll('.transaction-category-group');

    groups.forEach(function (groupEl, catIndex) {
        const catNumberEl = groupEl.querySelector('.transaction-category-number');
        if (catNumberEl) catNumberEl.textContent = 'Kategori No. ' + (catIndex + 1);

        const blocks = groupEl.querySelectorAll('.bon-block');
        blocks.forEach(function (block, bonIndex) {
            const bonNumberEl = block.querySelector('.bon-number');
            if (bonNumberEl) bonNumberEl.textContent = 'Bon No. ' + (bonIndex + 1);
        });

        const summaryEl = groupEl.querySelector('.transaction-category-summary');
        const select = groupEl.querySelector('.transaction-category-select');
        if (summaryEl) {
            const option = select && select.value ? select.options[select.selectedIndex] : null;
            summaryEl.textContent = (option ? option.textContent.trim() + ' · ' : '') + blocks.length + ' bon';
        }
    });

    // Petunjuk "Belum ada transaksi" (Edit) hanya tampil saat belum ada grup
    const hint = container.parentElement.querySelector('[id^="emptyTransactionsHint-"]');
    if (hint) hint.classList.toggle('hidden', groups.length > 0);
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
    const labelText = (isIncome ? 'Jumlah Pemasukan' : 'Jumlah Pengeluaran') + REQUIRED;

    groupEl.querySelectorAll('.transaction-category-hidden').forEach(function (hidden) {
        hidden.value = categoryId;
        hidden.dataset.type = selected ? (selected.dataset.type || '') : '';
    });

    groupEl.querySelectorAll('.amount-label').forEach(function (label) {
        label.innerHTML = labelText;
    });

    groupEl.querySelectorAll('.bon-block').forEach(function (block) {
        // Keterangan "(otomatis)" hanya untuk transaksi yang dibuat otomatis oleh
        // sistem (bukti pembayaran, payroll, kasbon divisi). Transaksi manual
        // (apapun kategorinya, termasuk UANG MASUK) keterangannya diisi user → wajib.
        const isProtected = block.classList.contains('bon-block-protected');
        const description = block.querySelector('textarea[name$="[description]"]');
        const label = block.querySelector('.description-label');

        if (label) {
            label.innerHTML = isProtected
                ? 'Keterangan <span class="font-normal text-gray-500">(otomatis)</span>'
                : 'Keterangan' + REQUIRED;
        }

        if (description) {
            if (isProtected) description.removeAttribute('required');
            else description.setAttribute('required', 'required');
        }
    });

    renumberTransactionBonBlocks(groupEl.closest('[data-categories]'));
}

/**
 * Menghitung ulang rekapitulasi (Total Pemasukan, Total Pengeluaran, Saldo).
 *
 * Elemen rekap memakai ID turunan dari kontainer:
 * `{containerId}-totalIncome`, `{containerId}-totalExpense`, `{containerId}-balance`.
 *
 * @param {string|HTMLElement} containerId - ID atau elemen kontainer transaksi.
 */
function updateTransactionsSummary(containerId) {
    const container = getTransactionContainer(containerId);
    if (!container || !container.id) return;

    const totalIncome = computeTotalIncome(container);
    let totalExpense = 0;

    container.querySelectorAll('.bon-block').forEach(function (block) {
        if (block.dataset.informational === '1') return;
        if (blockType(block) !== 'INCOME') totalExpense += blockAmount(block);
    });

    const baseId = container.id;
    const incomeEl = document.getElementById(baseId + '-totalIncome');
    const expenseEl = document.getElementById(baseId + '-totalExpense');
    const balanceEl = document.getElementById(baseId + '-balance');
    const balance = totalIncome - totalExpense;

    if (incomeEl) incomeEl.textContent = formatRupiahId(totalIncome);
    if (expenseEl) expenseEl.textContent = formatRupiahId(totalExpense);
    if (balanceEl) {
        balanceEl.textContent = formatRupiahId(balance);
        balanceEl.classList.toggle('text-red-600', balance < 0);
        balanceEl.classList.toggle('text-gray-950', balance >= 0);
    }

    updateRecapIncomeWarning(container);
}

/**
 * Kelompokkan daftar transaksi per kategori (urutan kemunculan dipertahankan).
 *
 * @param {Array<Object>} items
 * @returns {Array<{transaction_category_id: *, items: Array<Object>}>}
 */
function groupByCategory(items) {
    const groups = [];
    const byKey = {};

    items.forEach(function (item) {
        const key = item.transaction_category_id || 'none';
        if (!byKey[key]) {
            byKey[key] = { transaction_category_id: item.transaction_category_id, items: [] };
            groups.push(byKey[key]);
        }
        byKey[key].items.push(item);
    });

    return groups;
}

/**
 * Parse JSON dari data attribute (array kosong bila tidak valid).
 *
 * @param {string|undefined} raw
 * @returns {Array<Object>}
 */
function parseItems(raw) {
    try {
        const parsed = JSON.parse(raw || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
}

// ============================================================
// PRATINJAU BUKTI TRANSAKSI (TANPA TAB BARU)
// ============================================================

/**
 * Link "Bukti saat ini" pada blok bon (dibuat dinamis oleh JS) dibuka sebagai
 * pratinjau file di dalam halaman memakai window.openFilePreview
 * (shared/document-preview.js), bukan tab baru.
 */
document.addEventListener('click', function (e) {
    const link = e.target.closest('a[data-proof-preview]');
    if (!link || typeof window.openFilePreview !== 'function') return;

    e.preventDefault();
    window.openFilePreview(link.href, {
        title: 'Bukti — ' + (link.dataset.downloadName || 'Transaksi'),
        downloadName: link.dataset.downloadName || '',
    });
});

// ============================================================
// INISIALISASI
// ============================================================

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('pfr-form');
    if (!form) return;

    // ============================================================
    // STRUKTUR TRANSAKSI — isi awal kontainer
    // - Edit: transaksi existing, dikelompokkan per kategori (grup tertutup)
    // - Tambah: transaksi dari old input (gagal simpan), atau satu grup kosong
    // ============================================================

    form.querySelectorAll('[data-categories]').forEach(function (container) {
        if (container.hasAttribute('data-existing-items')) {
            groupByCategory(parseItems(container.dataset.existingItems)).forEach(function (group) {
                addTransactionCategoryGroup(container, Object.assign(group, { collapsed: true }));
            });
        } else {
            const groups = groupByCategory(parseItems(container.dataset.oldItems));
            if (groups.length) {
                groups.forEach(function (group) { addTransactionCategoryGroup(container, group); });
            } else {
                addTransactionCategoryGroup(container);
            }
        }

        updateTransactionsSummary(container);
    });

    // ============================================================
    // TAMBAH — SISA PEMBAYARAN REKAP TERPILIH
    // ============================================================

    const addRecapSelect = form.querySelector('select[name="project_recap_id"]');
    const addContainer = document.getElementById('transactionsContainer');

    function syncAddRemaining() {
        if (!addRecapSelect || !addContainer) return;

        const selected = addRecapSelect.options[addRecapSelect.selectedIndex];
        const raw = (selected && selected.dataset.remainingAmount != null)
            ? parseInt(selected.dataset.remainingAmount, 10)
            : NaN;
        addContainer.dataset.remainingAmount = isNaN(raw) ? '' : raw;

        updateTransactionsSummary(addContainer);
    }

    if (addRecapSelect) {
        addRecapSelect.addEventListener('change', syncAddRemaining);
        syncAddRemaining();
    }

    // ============================================================
    // DELEGATION — ganti kategori & input jumlah (grup dibuat dinamis)
    // ============================================================

    form.addEventListener('change', function (e) {
        if (e.target.matches('.transaction-category-select')) {
            const groupEl = e.target.closest('.transaction-category-group');
            syncBonCategoryFields(groupEl);
            if (groupEl) updateTransactionsSummary(groupEl.closest('[data-categories]'));
        }
    });

    form.addEventListener('input', function (e) {
        if (e.target.matches('.expense-amount-input')) {
            formatCurrencyInput(e.target);
            updateTransactionsSummary(e.target.closest('[data-categories]'));
        }
    });

    // ============================================================
    // FORMAT INPUT TOTAL RAB (Edit)
    // ============================================================

    form.querySelectorAll('.total-rab-input').forEach(function (input) {
        if (input.value) formatCurrencyInput(input);
        input.addEventListener('input', function () {
            formatCurrencyInput(this);
        });
    });

    // ============================================================
    // VALIDASI — field wajib di grup yang tertutup → buka grupnya dulu
    // agar browser bisa menampilkan pesan validasi pada field tersebut.
    // ============================================================

    form.addEventListener('invalid', function (e) {
        const body = e.target.closest('.category-group-body.hidden');
        if (body) {
            toggleTransactionCategoryGroup(body.previousElementSibling, true);
        }
    }, true);

    // ============================================================
    // PENANGANAN SUBMIT FORM (cegah double submit)
    // ============================================================

    form.addEventListener('submit', function (e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : '';

        if (!handleFormSubmit(submitBtn, originalText)) {
            e.preventDefault();
            return false;
        }
    });

    // ============================================================
    // RESET STATE SUBMIT SAAT HALAMAN DIMUAT ULANG (tombol kembali)
    // ============================================================

    window.addEventListener('pageshow', function () {
        resetFormSubmitState();
        // resetFormSubmitState mengaktifkan semua tombol submit → terapkan lagi
        // status "pemasukan melebihi sisa pembayaran"
        form.querySelectorAll('[data-categories]').forEach(updateRecapIncomeWarning);
    });
});
