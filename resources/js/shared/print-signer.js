/**
 * Penandatangan Laporan - Shared Module
 *
 * Revisi klien: Laporan Pengeluaran (super admin) / Kas Kantor (admin) wajib
 * memilih SATU penandatangan (Data Penandatangan, seperti invoice) setiap
 * kali dicetak ke PDF/Excel.
 *
 * Link export ber-atribut `data-require-signer` (dari
 * <x-buttons.print-dropdown :requireSigner="true">) dicegat di fase capture
 * window — sebelum pencegat pratinjau (document-preview.js) — lalu modal
 * #printSignerModal dibuka. Setelah penandatangan dipilih, URL export diberi
 * `signer_id` dan pratinjau dokumen dibuka; tombol Download di pratinjau
 * memakai URL yang sama sehingga file unduhan ikut memuat tanda tangan.
 *
 * Ekspos ke window:
 * - confirmPrintSigner() — dipanggil tombol "Lanjut Print" pada modal.
 */

/** @type {HTMLAnchorElement|null} Link export yang menunggu pilihan penandatangan */
let pendingLink = null;

/**
 * Buka modal penandatangan untuk link export.
 *
 * @param {HTMLAnchorElement} link
 */
function openSignerModal(link) {
    const modal = document.getElementById('printSignerModal');
    if (!modal || typeof window.openModal !== 'function') return;

    pendingLink = link;

    const format = modal.querySelector('.print-signer-format');
    if (format) format.textContent = link.dataset.signerFormat ? '(' + link.dataset.signerFormat + ')' : '';

    const select = document.getElementById('print-signer-select');
    if (select) select.setCustomValidity('');

    window.openModal('printSignerModal');
    if (select && !select.disabled) select.focus();
}

/**
 * Validasi pilihan penandatangan lalu buka pratinjau dokumen dengan signer_id.
 */
window.confirmPrintSigner = function () {
    const select = document.getElementById('print-signer-select');
    if (!pendingLink || !select) return;

    if (!select.value) {
        select.setCustomValidity(select.disabled
            ? 'Tambahkan Data Penandatangan terlebih dahulu'
            : 'Penandatangan wajib dipilih');
        select.reportValidity();
        return;
    }

    const url = new URL(pendingLink.href, window.location.origin);
    url.searchParams.set('signer_id', select.value);

    const linkText = (pendingLink.textContent || '').trim().replace(/\s+/g, ' ');
    const title = pendingLink.getAttribute('data-preview-title')
        || (linkText ? 'Pratinjau ' + linkText.replace(/^Export\s+/i, '') : 'Pratinjau Dokumen');

    pendingLink = null;
    window.closeModal('printSignerModal');
    window.openDocumentPreview(url.toString(), { title: title });
};

window.addEventListener('click', function (e) {
    const link = e.target.closest('a[data-require-signer]');
    if (!link || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey) return;

    // defaultPrevented → document-preview.js tidak membuka pratinjau tanpa penandatangan
    e.preventDefault();
    openSignerModal(link);
}, true);
