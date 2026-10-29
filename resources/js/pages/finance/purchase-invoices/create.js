/**
 * Faktur Pembelian - Halaman Tambah JavaScript
 *
 * Modul ini menangani:
 * - Kartu faktur dinamis (tambah dari <template id="purchaseInvoiceRowTemplate">,
 *   hapus; minimal 1 kartu) dengan indeks invoices[..] yang selalu unik
 * - Tutup/buka kartu (judul kartu menampilkan ringkasan saat ditutup)
 * - "Sama dengan Faktur ke-1" (salin semua kolom dari kartu pertama)
 * - Format harga jual & hitung PPN per kartu, ringkasan total
 * - Kartu tertutup yang berisi field tidak valid dibuka otomatis saat simpan
 * - Loading state saat submit (anti double-submit)
 */

/* global parseCurrencyInput, formatRupiah, handleFormSubmit, resetFormSubmitState */

// ==========================================
// HELPER
// ==========================================

/**
 * Parse input desimal (mendukung koma sebagai pemisah desimal).
 *
 * @param  {string|number} value
 * @return {number}
 */
function parseDecimalInput(value) {
    const rawValue = String(value ?? '').trim();
    if (!rawValue) return 0;
    return parseFloat(rawValue.replace(',', '.')) || 0;
}

/**
 * Format angka sebagai ribuan Indonesia tanpa "Rp" (prefix "Rp" sudah ada
 * di field).
 *
 * @param  {number} value
 * @return {string}
 */
function formatNumber(value) {
    return (Number(value) || 0).toLocaleString('id-ID');
}

/**
 * @return {HTMLElement|null}
 */
function getPurchaseInvoiceContainer() {
    return document.getElementById('purchaseInvoicesContainer');
}

/**
 * @return {HTMLElement[]}
 */
function getPurchaseInvoiceCards() {
    const container = getPurchaseInvoiceContainer();
    return container ? Array.from(container.querySelectorAll('.purchase-invoice-card')) : [];
}

/**
 * Nilai harga jual, PPN, dan total satu kartu.
 *
 * @param  {HTMLElement} card
 * @return {{selling: number, ppn: number, total: number}}
 */
function readCardAmounts(card) {
    const selling = parseCurrencyInput(card.querySelector('.pi-selling-price').value) || 0;
    const percentage = parseDecimalInput(card.querySelector('.pi-ppn-percentage').value);
    const ppn = Math.round((selling * percentage) / 100);

    return { selling: selling, ppn: ppn, total: selling + ppn };
}

// ==========================================
// PPN & RINGKASAN
// ==========================================

/**
 * Hitung PPN satu kartu dan perbarui ringkasan di judul kartu.
 *
 * @param  {HTMLElement} card
 * @return {void}
 */
function calculatePpnForCard(card) {
    if (!card) return;

    const amounts = readCardAmounts(card);
    card.querySelector('.pi-ppn-tax').value = formatNumber(amounts.ppn);

    const item = card.querySelector('.pi-item').value.trim();
    const parts = [item, amounts.total > 0 ? formatRupiah(amounts.total) : ''].filter(Boolean);
    card.querySelector('.card-summary').textContent = parts.length ? '· ' + parts.join(' · ') : '';
}

/**
 * Hitung ulang ringkasan semua faktur (kartu Ringkasan).
 *
 * @return {void}
 */
function updateSummary() {
    const cards = getPurchaseInvoiceCards();
    let selling = 0;
    let ppn = 0;

    cards.forEach(function (card) {
        const amounts = readCardAmounts(card);
        selling += amounts.selling;
        ppn += amounts.ppn;
    });

    const set = function (key, text) {
        document.querySelectorAll('[data-summary="' + key + '"]').forEach(function (el) { el.textContent = text; });
    };

    set('count', cards.length.toLocaleString('id-ID'));
    set('selling', formatRupiah(selling));
    set('ppn', formatRupiah(ppn));
    set('total', formatRupiah(selling + ppn));
}

// ==========================================
// KARTU FAKTUR DINAMIS (MULTIPLE RECORD)
// ==========================================

// Indeks invoices[..] selalu naik (tidak memakai jumlah kartu) agar kartu baru
// tidak menimpa indeks kartu lain setelah ada kartu yang dihapus.
let nextIndex = 0;

/**
 * Perbarui nomor urut kartu, checkbox "Sama dengan Faktur ke-1" (hanya kartu
 * ke-2 dst), dan tombol hapus (hanya bila lebih dari 1 kartu).
 *
 * @return {void}
 */
function updateInvoiceCardStates() {
    const cards = getPurchaseInvoiceCards();

    cards.forEach(function (card, index) {
        card.querySelector('.card-number').textContent = index + 1;

        const copyWrapper = card.querySelector('.pi-copy-wrapper');
        copyWrapper.classList.toggle('hidden', index === 0);
        copyWrapper.classList.toggle('flex', index !== 0);
        if (index === 0) card.querySelector('.pi-copy-from-first').checked = false;

        card.querySelector('.purchase-invoice-remove').classList.toggle('invisible', cards.length <= 1);
    });

    updateSummary();
}

/**
 * Buka / tutup body kartu.
 *
 * @param  {HTMLElement} card
 * @param  {boolean} open
 * @return {void}
 */
function setCardOpen(card, open) {
    card.querySelector('.card-body').classList.toggle('hidden', !open);
    card.querySelector('.card-chevron').classList.toggle('-rotate-90', !open);
}

/**
 * Tambah kartu faktur baru lalu fokus ke field pertama yang perlu diisi.
 *
 * @return {void}
 */
function addInvoiceCard() {
    const container = getPurchaseInvoiceContainer();
    const template = document.getElementById('purchaseInvoiceRowTemplate');
    if (!container || !template) return;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(nextIndex++));

    const card = wrapper.firstElementChild;
    if (!card) return;

    container.appendChild(card);
    calculatePpnForCard(card);
    updateInvoiceCardStates();
    setCardOpen(card, true);

    card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    card.querySelector('.pi-material').focus({ preventScroll: true });
}

/**
 * Tutup/buka kartu faktur ketika judul kartu diklik.
 *
 * @param  {HTMLElement} header  Elemen .card-header
 * @return {void}
 */
function toggleInvoiceCard(header) {
    const card = header && header.closest('.purchase-invoice-card');
    if (!card) return;

    setCardOpen(card, card.querySelector('.card-body').classList.contains('hidden'));
}

/**
 * Hapus satu kartu faktur (minimal 1 kartu tersisa).
 *
 * @param  {Event} event
 * @param  {HTMLElement} button  Tombol hapus di judul kartu
 * @return {void}
 */
function removeInvoiceCard(event, button) {
    // Tombol berada di dalam judul kartu yang bisa diklik (tutup/buka)
    if (event) event.stopPropagation();

    const card = button.closest('.purchase-invoice-card');
    if (!card || getPurchaseInvoiceCards().length <= 1) return;

    card.remove();
    updateInvoiceCardStates();
}

/**
 * Salin seluruh kolom dari faktur pertama ke kartu yang mencentang
 * "Sama dengan Faktur ke-1".
 *
 * @param  {HTMLInputElement} checkbox  Checkbox .pi-copy-from-first
 * @return {void}
 */
function handleCopyFromFirst(checkbox) {
    const targetCard = checkbox.closest('.purchase-invoice-card');
    const firstCard = getPurchaseInvoiceCards()[0];
    if (!targetCard || !checkbox.checked || !firstCard || firstCard === targetCard) return;

    [
        '.pi-date',
        '.pi-material',
        '.pi-npwp',
        '.pi-tax-code',
        '.pi-item',
        '.pi-selling-price',
        '.pi-ppn-percentage',
        '.pi-notes',
    ].forEach(function (selector) {
        const source = firstCard.querySelector(selector);
        const target = targetCard.querySelector(selector);
        if (source && target) {
            target.value = source.value;
            target.setCustomValidity('');
        }
    });

    calculatePpnForCard(targetCard);
    updateSummary();
}

window.addInvoiceCard = addInvoiceCard;
window.toggleInvoiceCard = toggleInvoiceCard;
window.removeInvoiceCard = removeInvoiceCard;
window.handleCopyFromFirst = handleCopyFromFirst;

// ==========================================
// DOM READY
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('purchase-invoice-create-form');
    const container = getPurchaseInvoiceContainer();
    if (!form || !container) return;

    // Lanjutkan indeks setelah indeks terbesar kartu yang sudah ada (input lama)
    container.querySelectorAll('[name^="invoices["]').forEach(function (input) {
        const match = input.name.match(/^invoices\[(\d+)\]/);
        if (match) nextIndex = Math.max(nextIndex, parseInt(match[1], 10) + 1);
    });

    // Format harga & hitung PPN (delegasi, berlaku juga untuk kartu baru)
    container.addEventListener('input', function (event) {
        const target = event.target;
        const card = target.closest('.purchase-invoice-card');
        if (!card) return;

        if (target.classList.contains('pi-selling-price')) {
            const digits = target.value.replace(/[^\d]/g, '');
            target.value = digits === '' ? '' : formatNumber(parseInt(digits, 10));
        }

        if (target.matches('.pi-selling-price, .pi-ppn-percentage, .pi-item')) {
            calculatePpnForCard(card);
            updateSummary();
        }
    });

    // Field tidak valid di kartu yang tertutup → buka kartunya agar browser
    // bisa menampilkan pesan & fokus ke field tersebut
    form.addEventListener('invalid', function (event) {
        const card = event.target.closest('.purchase-invoice-card');
        if (card) setCardOpen(card, true);
    }, true);

    form.addEventListener('submit', function (e) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (!handleFormSubmit(submitBtn, submitBtn ? submitBtn.innerHTML : '')) {
            e.preventDefault();
            return false;
        }
    });

    // Kembali lewat tombol Back (bfcache) → tombol Simpan aktif lagi
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) resetFormSubmitState();
    });

    getPurchaseInvoiceCards().forEach(calculatePpnForCard);
    updateInvoiceCardStates();
});
