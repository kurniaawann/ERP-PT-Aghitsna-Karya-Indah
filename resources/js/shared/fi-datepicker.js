/**
 * Date picker bergaya Filament untuk input tanggal.
 *
 * - <input type="date"> asli TETAP dipakai sebagai pembawa nilai (name,
 *   value "Y-m-d", required, min/max, oninvalid/oninput tetap jalan), hanya
 *   dibuat transparan di bawah tombol pemicu .fi-datepicker-trigger yang
 *   menampilkan tanggal berformat Indonesia, mis. "27 Okt 2026".
 * - Klik tombol pemicu → panel kalender (bulan, tahun, grid hari, "Hari ini",
 *   "Hapus" bila tidak wajib) dipasang di <body> dengan posisi `fixed` agar
 *   tidak terpotong modal / tabel ber-overflow.
 * - Saat tanggal dipilih, event `input` & `change` di-dispatch pada input asli
 *   agar listener halaman ikut berjalan. Nilai yang di-set lewat JS
 *   (input.value = ...) atau form.reset() ikut memperbarui tampilan.
 *
 * Diterapkan otomatis pada (termasuk elemen yang ditambahkan belakangan):
 * - input[type=date][data-fi-datepicker] → x-fi.input type="date"
 * - .fi-repeater input[type=date]        → baris repeater (mis. Data Semen)
 *
 * Keyboard: panah (± hari / minggu), PageUp/PageDown (± bulan), Home/End
 * (awal/akhir minggu), Enter/Spasi pilih, Escape tutup.
 */

const SELECTOR = 'input[type="date"][data-fi-datepicker], .fi-repeater input[type="date"]';

const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']; // minggu dimulai Senin

const svg = (path, cls = '') =>
    `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" class="${cls}"><path stroke-linecap="round" stroke-linejoin="round" d="${path}"/></svg>`;

const CALENDAR_SVG = svg('M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z');
const CHEVRON_LEFT_SVG = svg('M15.75 19.5 8.25 12l7.5-7.5');
const CHEVRON_RIGHT_SVG = svg('m8.25 4.5 7.5 7.5-7.5 7.5');

const valueDescriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
const bound = new WeakSet();

let panel = null;
let active = null; // { input, trigger, year, month, focused: Date }

// ==========================================
// HELPER TANGGAL
// ==========================================

const pad = n => String(n).padStart(2, '0');

/** @returns {Date} tanggal (tanpa jam) */
function makeDate(year, month, day) {
    const d = new Date(2000, 0, 1);
    d.setFullYear(year, month, day);
    return d;
}

function toIso(d) {
    return `${String(d.getFullYear()).padStart(4, '0')}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/** @returns {Date|null} hasil parse "Y-m-d", null bila kosong/tidak valid */
function parseIso(value) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
    return m ? makeDate(+m[1], +m[2] - 1, +m[3]) : null;
}

function today() {
    const now = new Date();
    return makeDate(now.getFullYear(), now.getMonth(), now.getDate());
}

function sameDay(a, b) {
    return !!a && !!b && toIso(a) === toIso(b);
}

function formatDisplay(d) {
    return `${d.getDate()} ${MONTHS_SHORT[d.getMonth()]} ${d.getFullYear()}`;
}

function rawValue(input) {
    return valueDescriptor.get.call(input);
}

function isOutOfRange(input, d) {
    const min = parseIso(input.min);
    const max = parseIso(input.max);
    return (min && d < min) || (max && d > max);
}

// ==========================================
// INPUT & TOMBOL PEMICU
// ==========================================

/**
 * Pasang date picker pada satu input tanggal (sekali per elemen input).
 * Pembungkus .fi-datepicker dibuat bila belum ada (mis. baris repeater
 * yang dibuat JS); tombol pemicu dibuat bila belum ada (hasil cloneNode
 * memakai ulang tombolnya).
 *
 * @param {HTMLInputElement} input
 * @returns {void}
 */
function enhance(input) {
    if (bound.has(input)) return;
    bound.add(input);

    let host = input.parentElement;
    if (!host || !host.classList.contains('fi-datepicker')) {
        host = document.createElement('div');
        host.className = 'fi-datepicker';
        input.parentNode.insertBefore(host, input);
        host.appendChild(input);
    }

    if (!host.querySelector(':scope > .fi-datepicker-trigger')) {
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'fi-datepicker-trigger';
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.innerHTML = `${CALENDAR_SVG}<span class="fi-datepicker-text"></span>`;
        host.appendChild(trigger);
    }

    input.classList.add('fi-datepicker-native');
    input.tabIndex = -1;
    input.setAttribute('aria-hidden', 'true');

    // input.value = '...' dari JS halaman → perbarui teks tombol
    Object.defineProperty(input, 'value', {
        configurable: true,
        get() {
            return valueDescriptor.get.call(this);
        },
        set(v) {
            valueDescriptor.set.call(this, v);
            refresh(this);
        },
    });

    input.addEventListener('invalid', () => host.classList.add('is-invalid'));
    input.form?.addEventListener('reset', () => setTimeout(() => refresh(input)));

    refresh(input);
}

/**
 * Sinkronkan tombol pemicu dengan nilai & status input asli.
 *
 * @param {HTMLInputElement} input
 * @returns {void}
 */
function refresh(input) {
    const host = input.parentElement;
    const trigger = host?.querySelector(':scope > .fi-datepicker-trigger');
    if (!trigger) return;

    const date = parseIso(rawValue(input));
    const text = trigger.querySelector('.fi-datepicker-text');
    text.textContent = date ? formatDisplay(date) : (input.getAttribute('placeholder') || 'Pilih tanggal');
    text.classList.toggle('is-placeholder', !date);

    trigger.disabled = input.disabled || input.readOnly;

    const label = input.labels?.[0]?.textContent.replace('*', '').trim();
    trigger.setAttribute('aria-label', `${label ? label + ': ' : ''}${date ? formatDisplay(date) : 'belum dipilih'}`);

    if (date) host.classList.remove('is-invalid');
}

/**
 * Isi nilai input asli lalu jalankan event input & change.
 *
 * @param {HTMLInputElement} input
 * @param {string} value "Y-m-d" atau '' (kosongkan)
 * @returns {void}
 */
function setValue(input, value) {
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

// ==========================================
// PANEL KALENDER
// ==========================================

/**
 * Buat elemen panel (sekali) beserta event delegasinya.
 *
 * @returns {HTMLElement}
 */
function ensurePanel() {
    if (panel) return panel;

    panel = document.createElement('div');
    panel.className = 'fi-datepicker-panel hidden';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Pilih tanggal');

    panel.addEventListener('click', function (e) {
        if (!active) return;

        const nav = e.target.closest('[data-nav]');
        if (nav) {
            shiftMonth(+nav.dataset.nav);
            return;
        }

        const day = e.target.closest('.fi-datepicker-day');
        if (day && !day.disabled) {
            choose(parseIso(day.dataset.date));
            return;
        }

        if (e.target.closest('[data-today]')) {
            const t = today();
            if (!isOutOfRange(active.input, t)) choose(t);
            return;
        }

        if (e.target.closest('[data-clear]')) {
            setValue(active.input, '');
            close(true);
        }
    });

    panel.addEventListener('change', function (e) {
        if (!active || !e.target.matches('.fi-datepicker-month')) return;
        goTo(active.year, +e.target.value, false);
    });

    panel.addEventListener('input', function (e) {
        if (!active || !e.target.matches('.fi-datepicker-year')) return;
        const year = +e.target.value;
        if (/^\d{4}$/.test(e.target.value) && year >= 1000) goTo(year, active.month, false);
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
 * Isi kerangka panel: header (bulan sebelumnya, pilih bulan, tahun, bulan
 * berikutnya), nama hari, wadah grid hari, footer.
 *
 * @returns {void}
 */
function buildPanel() {
    const { input } = active;
    const monthOptions = MONTHS.map((name, i) => `<option value="${i}">${name}</option>`).join('');
    const weekdays = WEEKDAYS.map(d => `<span>${d}</span>`).join('');

    panel.innerHTML = `
        <div class="fi-datepicker-header">
            <button type="button" class="fi-datepicker-nav" data-nav="-1" aria-label="Bulan sebelumnya">${CHEVRON_LEFT_SVG}</button>
            <div class="fi-datepicker-heading">
                <select class="fi-datepicker-month" aria-label="Bulan">${monthOptions}</select>
                <input type="number" class="fi-datepicker-year" aria-label="Tahun" min="1000" max="9999" inputmode="numeric">
            </div>
            <button type="button" class="fi-datepicker-nav" data-nav="1" aria-label="Bulan berikutnya">${CHEVRON_RIGHT_SVG}</button>
        </div>
        <div class="fi-datepicker-weekdays" aria-hidden="true">${weekdays}</div>
        <div class="fi-datepicker-days"></div>
        <div class="fi-datepicker-footer">
            <button type="button" class="fi-datepicker-link" data-today>Hari ini</button>
            ${input.required ? '' : '<button type="button" class="fi-datepicker-link is-gray" data-clear>Hapus</button>'}
        </div>`;
}

/**
 * Render grid hari untuk bulan aktif + sinkronkan pilihan bulan & tahun.
 *
 * @returns {void}
 */
function renderDays() {
    const { input, year, month, focused } = active;
    const selected = parseIso(rawValue(input));
    const now = today();
    const offset = (makeDate(year, month, 1).getDay() + 6) % 7; // Senin = 0
    const daysInMonth = makeDate(year, month + 1, 0).getDate();

    let cells = '<span></span>'.repeat(offset);
    for (let day = 1; day <= daysInMonth; day++) {
        const d = makeDate(year, month, day);
        const classes = ['fi-datepicker-day'];
        if (sameDay(d, selected)) classes.push('is-selected');
        else if (sameDay(d, now)) classes.push('is-today');

        cells += `<button type="button" class="${classes.join(' ')}" data-date="${toIso(d)}"`
            + ` tabindex="${sameDay(d, focused) ? 0 : -1}" aria-label="${day} ${MONTHS[month]} ${year}"`
            + `${sameDay(d, selected) ? ' aria-pressed="true"' : ''}${isOutOfRange(input, d) ? ' disabled' : ''}>${day}</button>`;
    }

    // Selalu 6 baris minggu (42 sel) agar tinggi panel tetap saat ganti bulan
    cells += '<span></span>'.repeat(42 - offset - daysInMonth);

    panel.querySelector('.fi-datepicker-days').innerHTML = cells;
    panel.querySelector('.fi-datepicker-month').value = String(month);

    const yearInput = panel.querySelector('.fi-datepicker-year');
    if (document.activeElement !== yearInput) yearInput.value = String(year);
}

/**
 * Pindah ke bulan/tahun tertentu; tanggal fokus ikut (dibatasi akhir bulan).
 *
 * @param {number} year
 * @param {number} month 0–11 (boleh di luar rentang, dinormalisasi)
 * @param {boolean} focusDay pindahkan fokus keyboard ke grid hari
 * @param {number} [day] tanggal fokus (default: tanggal fokus sekarang)
 * @returns {void}
 */
function goTo(year, month, focusDay, day = active.focused.getDate()) {
    const first = makeDate(year, month, 1);
    const lastDay = makeDate(first.getFullYear(), first.getMonth() + 1, 0).getDate();

    active.year = first.getFullYear();
    active.month = first.getMonth();
    active.focused = makeDate(active.year, active.month, Math.min(day, lastDay));

    renderDays();
    positionPanel();
    if (focusDay) focusFocusedDay();
}

function shiftMonth(delta) {
    goTo(active.year, active.month + delta, false);
}

function focusFocusedDay() {
    panel.querySelector(`.fi-datepicker-day[data-date="${toIso(active.focused)}"]`)?.focus();
}

/**
 * Navigasi keyboard di grid hari & Escape di seluruh panel.
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

    if (!e.target.closest('.fi-datepicker-day')) return;

    const f = active.focused;
    const weekday = (f.getDay() + 6) % 7;
    const moves = {
        ArrowLeft: () => makeDate(f.getFullYear(), f.getMonth(), f.getDate() - 1),
        ArrowRight: () => makeDate(f.getFullYear(), f.getMonth(), f.getDate() + 1),
        ArrowUp: () => makeDate(f.getFullYear(), f.getMonth(), f.getDate() - 7),
        ArrowDown: () => makeDate(f.getFullYear(), f.getMonth(), f.getDate() + 7),
        Home: () => makeDate(f.getFullYear(), f.getMonth(), f.getDate() - weekday),
        End: () => makeDate(f.getFullYear(), f.getMonth(), f.getDate() + 6 - weekday),
    };

    if (moves[e.key]) {
        e.preventDefault();
        const next = moves[e.key]();
        goTo(next.getFullYear(), next.getMonth(), true, next.getDate());
    } else if (e.key === 'PageUp' || e.key === 'PageDown') {
        e.preventDefault();
        goTo(f.getFullYear(), f.getMonth() + (e.key === 'PageUp' ? -1 : 1), true);
    }
}

/**
 * Posisikan panel di bawah tombol pemicu (rata kiri), atau di atasnya bila
 * ruang bawah tidak cukup. Bila keduanya tidak cukup (layar pendek), panel
 * digeser agar tetap utuh di dalam layar.
 *
 * @returns {void}
 */
function positionPanel() {
    const gap = 4;
    const margin = 8;
    const rect = active.trigger.getBoundingClientRect();
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
    const input = trigger.parentElement.querySelector(':scope > .fi-datepicker-native');
    if (!input) return;

    refresh(input);
    if (trigger.disabled) return;

    close();

    const selected = parseIso(rawValue(input));
    const base = selected || today();
    active = { input, trigger, year: base.getFullYear(), month: base.getMonth(), focused: base };

    ensurePanel();
    buildPanel();
    renderDays();

    panel.style.visibility = 'hidden';
    panel.classList.remove('hidden');
    positionPanel();
    panel.style.visibility = '';

    trigger.setAttribute('aria-expanded', 'true');
    focusFocusedDay();
}

/**
 * Pilih tanggal → isi input asli, tutup panel, kembalikan fokus.
 *
 * @param {Date} date
 * @returns {void}
 */
function choose(date) {
    setValue(active.input, toIso(date));
    close(true);
}

/**
 * Tutup panel yang sedang terbuka.
 *
 * @param {boolean} [restoreFocus] Kembalikan fokus ke tombol pemicu.
 * @returns {void}
 */
function close(restoreFocus = false) {
    if (!active) return;

    const { trigger } = active;
    active = null;
    panel.classList.add('hidden');
    trigger.setAttribute('aria-expanded', 'false');

    if (restoreFocus) trigger.focus();
}

// ==========================================
// EVENT GLOBAL & INISIALISASI
// ==========================================

document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.fi-datepicker-trigger');
    if (trigger) {
        if (active && active.trigger === trigger) close();
        else open(trigger);
        return;
    }

    // Klik label milik input tanggal → fokus ke tombol pemicu (bukan input transparan)
    const label = e.target.closest('label');
    if (label?.control && bound.has(label.control)) {
        e.preventDefault();
        label.control.parentElement.querySelector(':scope > .fi-datepicker-trigger')?.focus();
    }
});

document.addEventListener('keydown', function (e) {
    if (active && e.key === 'Escape') {
        close(true);
        return;
    }

    const trigger = e.target.closest?.('.fi-datepicker-trigger');
    if (trigger && e.key === 'ArrowDown') {
        e.preventDefault();
        open(trigger);
    }
});

// Klik di luar panel & tombol pemicu → tutup
document.addEventListener('mousedown', function (e) {
    if (active && !panel.contains(e.target) && !active.trigger.contains(e.target)) close();
});

// Scroll (mis. isi modal) → ikuti posisi tombol; tutup bila tombol tak terlihat lagi
document.addEventListener('scroll', function (e) {
    if (!active || panel.contains(e.target)) return;
    if (!active.trigger.offsetParent) close();
    else positionPanel();
}, true);

window.addEventListener('resize', () => close());
window.addEventListener('modalClosed', () => close());

// Modal dibuka → sinkronkan ulang tampilan (nilai/disabled yang diubah JS halaman)
window.addEventListener('modalOpened', function (e) {
    document.getElementById(e.detail)?.querySelectorAll('.fi-datepicker-native').forEach(refresh);
});

function init() {
    document.querySelectorAll(SELECTOR).forEach(enhance);

    // Input tanggal yang ditambahkan belakangan (mis. baris repeater dari JS)
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
