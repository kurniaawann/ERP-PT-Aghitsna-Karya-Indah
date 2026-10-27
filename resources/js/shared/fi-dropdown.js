/**
 * Dropdown Aksi bergaya Filament (komponen x-fi.dropdown).
 *
 * - Klik tombol [data-fi-dropdown-trigger] → buka/tutup panel
 *   [data-fi-dropdown-panel] milik dropdown tersebut (hanya satu terbuka).
 * - Saat terbuka, panel dipindah ke <body> dan diposisikan `fixed` di bawah
 *   tombol (atau di atasnya bila ruang bawah tidak cukup), supaya tidak
 *   terpotong pembungkus tabel ber-overflow / tertutup kolom sticky.
 * - Panel tertutup saat item di dalamnya diklik, klik di luar, Escape,
 *   scroll, atau ukuran jendela berubah. Panel dikembalikan ke tempat asalnya.
 */

let active = null; // { dropdown, trigger, panel }

/**
 * Tutup dropdown yang sedang terbuka.
 *
 * @param {boolean} [restoreFocus] Kembalikan fokus ke tombol pemicu.
 * @returns {void}
 */
function closeDropdown(restoreFocus = false) {
    if (!active) return;

    const { dropdown, trigger, panel } = active;
    panel.classList.add('hidden');
    panel.style.top = '';
    panel.style.left = '';
    dropdown.appendChild(panel);
    trigger.setAttribute('aria-expanded', 'false');

    if (restoreFocus) trigger.focus();
    active = null;
}

/**
 * Posisikan panel relatif terhadap tombol pemicu (rata kanan tombol).
 *
 * @param {HTMLElement} trigger
 * @param {HTMLElement} panel
 * @returns {void}
 */
function positionPanel(trigger, panel) {
    const gap = 4;
    const margin = 8;
    const rect = trigger.getBoundingClientRect();
    const width = panel.offsetWidth;
    const height = panel.offsetHeight;

    let left = rect.right - width;
    left = Math.max(margin, Math.min(left, window.innerWidth - width - margin));

    let top = rect.bottom + gap;
    if (top + height > window.innerHeight - margin && rect.top - height - gap >= margin) {
        top = rect.top - height - gap;
    }

    panel.style.left = left + 'px';
    panel.style.top = top + 'px';
}

/**
 * Buka dropdown milik tombol pemicu.
 *
 * @param {HTMLElement} trigger
 * @returns {void}
 */
function openDropdown(trigger) {
    const dropdown = trigger.closest('[data-fi-dropdown]');
    const panel = dropdown ? dropdown.querySelector('[data-fi-dropdown-panel]') : null;
    if (!panel) return;

    document.body.appendChild(panel);
    panel.style.visibility = 'hidden';
    panel.classList.remove('hidden');
    positionPanel(trigger, panel);
    panel.style.visibility = '';

    trigger.setAttribute('aria-expanded', 'true');
    active = { dropdown, trigger, panel };
}

document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-fi-dropdown-trigger]');

    if (trigger) {
        const isSame = active && active.trigger === trigger;
        closeDropdown();
        if (!isSame) openDropdown(trigger);
        return;
    }

    if (!active) return;

    // Klik item (link/tombol) di dalam panel: aksinya sudah jalan (onclick /
    // pratinjau dokumen), tinggal tutup menu. Klik area kosong panel: biarkan.
    if (active.panel.contains(e.target)) {
        if (e.target.closest('a, button')) closeDropdown();
        return;
    }

    closeDropdown();
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && active) closeDropdown(true);
});

window.addEventListener('scroll', function () {
    closeDropdown();
}, true);

window.addEventListener('resize', function () {
    closeDropdown();
});
