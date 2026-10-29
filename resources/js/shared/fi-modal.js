/**
 * Tutup modal x-fi.modal mode detail & konfirmasi tanpa tombol "Tutup":
 * - klik area gelap di luar panel modal
 * - tombol Escape (hanya overlay paling atas, mis. tidak saat pratinjau
 *   dokumen terbuka di atas modal detail)
 *
 * Hanya berlaku untuk modal ber-atribut data-fi-modal-dismissible (dipasang
 * x-fi.modal pada mode detail/konfirmasi). Modal form sengaja tidak ikut agar
 * isian tidak hilang karena salah klik.
 */

/* global closeModal */

/**
 * Overlay (fixed inset-0) yang sedang tampil paling atas (urutan DOM terakhir).
 *
 * @return {HTMLElement|null}
 */
function getTopmostOverlay() {
    const overlays = Array.from(document.querySelectorAll('.fixed.inset-0[id]'))
        .filter((el) => !el.classList.contains('hidden') && getComputedStyle(el).display !== 'none');

    return overlays.length ? overlays[overlays.length - 1] : null;
}

document.addEventListener('click', function (e) {
    const modal = e.target;
    if (modal instanceof HTMLElement && modal.hasAttribute('data-fi-modal-dismissible')) {
        closeModal(modal.id);
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || e.defaultPrevented) return;

    const top = getTopmostOverlay();
    if (top && top.hasAttribute('data-fi-modal-dismissible')) {
        closeModal(top.id);
    }
});
