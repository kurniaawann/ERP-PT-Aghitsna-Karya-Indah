/**
 * Dropdown pilihan bergaya Filament untuk <select data-fi-select>
 * (mis. filter "Semua Bulan" / "Semua Tahun" — x-fi.month-select, x-fi.year-select).
 *
 * - <select> asli TETAP dipakai sebagai pembawa nilai (name, id, onchange,
 *   listener halaman), hanya dibuat transparan di bawah tombol pemicu
 *   .fi-select-trigger yang menampilkan label opsi terpilih.
 * - Klik tombol pemicu → panel daftar opsi (✓ pada opsi terpilih) dipasang di
 *   <body> dengan posisi `fixed` agar tidak terpotong pembungkus ber-overflow.
 * - Saat opsi dipilih, nilai <select> diubah lalu event `input` & `change`
 *   di-dispatch, sehingga onchange="this.form.requestSubmit()" maupun listener
 *   JS halaman (mis. #month-select / #year-select) tetap berjalan.
 *
 * Keyboard: ↑/↓ (atau Enter/Spasi) buka & pindah opsi, Home/End, ketik huruf
 * awal untuk lompat, Enter pilih, Escape tutup.
 */

const SELECTOR = 'select[data-fi-select]';

const CHECK_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>';

const valueDescriptor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
const bound = new WeakSet();

let panel = null;
let active = null; // { select, trigger, wrapper }

// ==========================================
// SELECT & TOMBOL PEMICU
// ==========================================

/**
 * Pasang dropdown pada satu <select> (sekali per elemen).
 *
 * @param {HTMLSelectElement} select
 * @returns {void}
 */
function enhance(select) {
    if (bound.has(select)) return;
    bound.add(select);

    const trigger = document.createElement('button');
    trigger.type = 'button';
    // Padding kiri mengikuti <select> (ps-2 bila ada ikon prefix)
    trigger.className = `fi-select-trigger ${select.classList.contains('ps-2') ? 'ps-2' : 'ps-3'}`;
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.innerHTML = '<span class="fi-select-text"></span>';
    select.insertAdjacentElement('afterend', trigger);

    select.classList.add('fi-select-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    // select.value = '...' dari JS halaman → perbarui teks tombol
    Object.defineProperty(select, 'value', {
        configurable: true,
        get() {
            return valueDescriptor.get.call(this);
        },
        set(v) {
            valueDescriptor.set.call(this, v);
            refresh(this);
        },
    });

    select.addEventListener('change', () => refresh(select));
    select.form?.addEventListener('reset', () => setTimeout(() => refresh(select)));

    // select.disabled diubah JS halaman (mis. tipe diskon/DP nonaktif saat belum ada item) → tombol ikut
    new MutationObserver(() => refresh(select)).observe(select, { attributes: true, attributeFilter: ['disabled'] });

    refresh(select);
}

function triggerOf(select) {
    return select.parentElement?.querySelector(':scope > .fi-select-trigger');
}

/**
 * Sinkronkan tombol pemicu dengan opsi terpilih & status <select>.
 *
 * @param {HTMLSelectElement} select
 * @returns {void}
 */
function refresh(select) {
    const trigger = triggerOf(select);
    if (!trigger) return;

    const option = select.options[select.selectedIndex];
    const text = option ? option.textContent.trim() : '';
    trigger.querySelector('.fi-select-text').textContent = text;
    trigger.disabled = select.disabled;

    const label = select.getAttribute('aria-label') || select.labels?.[0]?.textContent.replace('*', '').trim();
    trigger.setAttribute('aria-label', `${label ? label + ': ' : ''}${text}`);
}

// ==========================================
// PANEL OPSI
// ==========================================

/**
 * Buat elemen panel (sekali) beserta event delegasinya.
 *
 * @returns {HTMLElement}
 */
function ensurePanel() {
    if (panel) return panel;

    panel = document.createElement('div');
    panel.className = 'fi-select-panel hidden';
    panel.setAttribute('role', 'listbox');

    panel.addEventListener('click', function (e) {
        const option = e.target.closest('.fi-select-option');
        if (option && active) choose(+option.dataset.index);
    });

    panel.addEventListener('keydown', onPanelKeydown);

    // Fokus keluar panel (Tab) → tutup
    panel.addEventListener('focusout', function (e) {
        if (active && e.relatedTarget && !panel.contains(e.relatedTarget) && e.relatedTarget !== active.trigger) {
            close();
        }
    });

    document.body.appendChild(panel);
    return panel;
}

/**
 * Render daftar opsi dari <select> aktif.
 *
 * @returns {void}
 */
function renderOptions() {
    const { select } = active;
    panel.setAttribute('aria-label', select.getAttribute('aria-label') || 'Pilih');
    panel.innerHTML = Array.from(select.options).map((option, index) => {
        const selected = index === select.selectedIndex;
        const label = option.textContent.trim().replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);
        return `<button type="button" role="option" class="fi-select-option${selected ? ' is-selected' : ''}"`
            + ` data-index="${index}" tabindex="-1" aria-selected="${selected}"${option.disabled ? ' disabled' : ''}>`
            + `<span class="truncate">${label}</span>${selected ? CHECK_SVG : ''}</button>`;
    }).join('');
}

function optionButtons() {
    return Array.from(panel.querySelectorAll('.fi-select-option:not([disabled])'));
}

/**
 * Fokus ke opsi tertentu & pastikan terlihat di area scroll panel.
 *
 * @param {HTMLElement|undefined} button
 * @returns {void}
 */
function focusOption(button) {
    if (!button) return;
    button.focus({ preventScroll: true });
    button.scrollIntoView({ block: 'nearest' });
}

/**
 * Navigasi keyboard di daftar opsi.
 *
 * @param {KeyboardEvent} e
 * @returns {void}
 */
function onPanelKeydown(e) {
    if (!active) return;

    if (e.key === 'Escape') {
        e.preventDefault();
        e.stopPropagation();
        close(true);
        return;
    }

    if (e.key === 'Tab') {
        close();
        return;
    }

    const buttons = optionButtons();
    const current = buttons.indexOf(document.activeElement);

    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        const next = current + (e.key === 'ArrowDown' ? 1 : -1);
        focusOption(buttons[Math.max(0, Math.min(buttons.length - 1, next))]);
    } else if (e.key === 'Home' || e.key === 'End') {
        e.preventDefault();
        focusOption(e.key === 'Home' ? buttons[0] : buttons[buttons.length - 1]);
    } else if (e.key.length === 1 && /\S/.test(e.key)) {
        // Lompat ke opsi berikutnya yang diawali huruf/angka yang diketik
        const key = e.key.toLowerCase();
        const ordered = buttons.slice(current + 1).concat(buttons.slice(0, current + 1));
        focusOption(ordered.find(b => b.textContent.trim().toLowerCase().startsWith(key)));
    }
}

/**
 * Posisikan panel di bawah tombol pemicu (lebar minimal = lebar field),
 * atau di atasnya bila ruang bawah tidak cukup.
 *
 * @returns {void}
 */
function positionPanel() {
    const gap = 4;
    const margin = 8;
    const rect = active.wrapper.getBoundingClientRect();

    panel.style.minWidth = rect.width + 'px';
    const width = panel.offsetWidth;
    const height = panel.offsetHeight;

    const left = Math.max(margin, Math.min(rect.left, window.innerWidth - width - margin));
    let top = rect.bottom + gap;
    if (top + height > window.innerHeight - margin) {
        top = rect.top - height - gap >= margin
            ? rect.top - height - gap
            : Math.max(margin, window.innerHeight - height - margin);
    }

    panel.style.left = left + 'px';
    panel.style.top = top + 'px';
}

/**
 * Buka panel untuk tombol pemicu tertentu.
 *
 * @param {HTMLButtonElement} trigger
 * @returns {void}
 */
function open(trigger) {
    const select = trigger.parentElement.querySelector(':scope > .fi-select-native');
    if (!select) return;

    refresh(select);
    if (trigger.disabled) return;

    close();
    active = { select, trigger, wrapper: trigger.parentElement };

    ensurePanel();
    renderOptions();

    panel.style.visibility = 'hidden';
    panel.classList.remove('hidden');
    positionPanel();
    panel.style.visibility = '';

    trigger.setAttribute('aria-expanded', 'true');
    active.wrapper.classList.add('fi-select-open');

    focusOption(panel.querySelector('.fi-select-option.is-selected:not([disabled])') || optionButtons()[0]);
}

/**
 * Pilih opsi → ubah nilai <select>, jalankan event, tutup panel.
 *
 * @param {number} index indeks opsi pada <select>
 * @returns {void}
 */
function choose(index) {
    const { select } = active;
    const changed = select.selectedIndex !== index;

    close(true);

    if (changed) {
        select.selectedIndex = index;
        refresh(select);
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

/**
 * Tutup panel yang sedang terbuka.
 *
 * @param {boolean} [restoreFocus] Kembalikan fokus ke tombol pemicu.
 * @returns {void}
 */
function close(restoreFocus = false) {
    if (!active) return;

    const { trigger, wrapper } = active;
    active = null;
    panel.classList.add('hidden');
    trigger.setAttribute('aria-expanded', 'false');
    wrapper.classList.remove('fi-select-open');

    if (restoreFocus) trigger.focus();
}

// ==========================================
// EVENT GLOBAL & INISIALISASI
// ==========================================

document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.fi-select-trigger');
    if (trigger) {
        if (active && active.trigger === trigger) close();
        else open(trigger);
        return;
    }

    // Klik label milik <select> → fokus ke tombol pemicu
    const label = e.target.closest('label');
    if (label?.control && bound.has(label.control)) {
        e.preventDefault();
        triggerOf(label.control)?.focus();
    }
});

document.addEventListener('keydown', function (e) {
    if (active && e.key === 'Escape') {
        close(true);
        return;
    }

    const trigger = e.target.closest?.('.fi-select-trigger');
    if (trigger && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
        e.preventDefault();
        open(trigger);
    }
});

// Klik di luar panel & tombol pemicu → tutup
document.addEventListener('mousedown', function (e) {
    if (active && !panel.contains(e.target) && !active.trigger.contains(e.target)) close();
});

// Scroll halaman/modal → ikuti posisi tombol; tutup bila tombol tak terlihat lagi
document.addEventListener('scroll', function (e) {
    if (!active || panel.contains(e.target)) return;
    if (!active.trigger.offsetParent) close();
    else positionPanel();
}, true);

window.addEventListener('resize', () => close());
window.addEventListener('modalClosed', () => close());

function init() {
    document.querySelectorAll(SELECTOR).forEach(enhance);

    new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes.forEach(function (node) {
                if (node.nodeType !== 1) return;
                if (node.matches(SELECTOR)) enhance(node);
                node.querySelectorAll(SELECTOR).forEach(enhance);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
