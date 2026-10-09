/**
 * Lengkapi Tanda Tangan Nota - Shared Module
 *
 * Revisi klien (Super Admin & Admin): nota punya DUA tanda tangan seperti di
 * PDF — Penerima/Tanda Terima (kiri, nama diketik) dan Hormat Kami (kanan,
 * dari Data Penandatangan). Keduanya opsional saat nota dibuat, tetapi nota
 * yang belum lengkap wajib dilengkapi sebelum di-download.
 *
 * - Link PDF ber-atribut `data-nota-sign="<id nota>"` (tombol PDF per baris,
 *   link nota di Reimbursement) atau `data-nota-sign="all"` (Export Semua,
 *   ikut filter pada query URL) dicegat di fase capture window — sebelum
 *   pencegat pratinjau (document-preview.js) — lalu modal #notaSignModal dibuka.
 * - Modal memuat rincian nota yang belum lengkap (NotaController::unsigned).
 *   Setiap nota dirender dari <template id="nota-sign-row-template"> yang berisi
 *   partial form Tambah Nota (Penerima/Tanda Terima, Hormat Kami, Divisi —
 *   satu kolom) sehingga tampilannya sama. Bagian yang sudah terisi dikunci.
 * - Simpan: POST ke NotaController::sign (signatures[id][...]), penanda
 *   "belum lengkap" di halaman dibersihkan, lalu pratinjau PDF dibuka.
 *
 * Ekspos ke window:
 * - requireNotaSignature({ ids, scope, query, onSigned }) — dipakai Export
 *   Dipilih di halaman nota.
 * - confirmNotaSign() — tombol "Simpan & Print" pada modal.
 */

/** @type {{ids?: string[]|null, scope?: string|null, query?: Object, onSigned: Function}|null} */
let pendingSign = null;

/** Kelas untuk isian yang sudah terisi (dikunci). */
const LOCKED_CLASSES = ['bg-surface-hover', 'text-text-secondary', 'cursor-not-allowed'];

/**
 * Elemen-elemen modal tanda tangan.
 */
function signModalParts() {
    const modal = document.getElementById('notaSignModal');
    const find = (selector) => (modal ? modal.querySelector(selector) : null);

    return {
        modal: modal,
        config: document.getElementById('nota-sign-config'),
        template: document.getElementById('nota-sign-row-template'),
        message: find('.nota-sign-message'),
        loading: find('.nota-sign-loading'),
        actions: find('.nota-sign-actions'),
        rows: find('.nota-sign-rows'),
        error: find('.nota-sign-error'),
        button: document.getElementById('confirm-btn-notaSignModal'),
    };
}

/**
 * Tambahkan cakupan permintaan (ids[] atau scope=all + filter) ke FormData.
 *
 * @param {FormData} formData
 * @param {Object} request
 */
function appendScope(formData, request) {
    if (request.scope === 'all') {
        formData.append('scope', 'all');
        Object.entries(request.query || {}).forEach(([key, value]) => {
            if (value) formData.append(key, value);
        });
    } else {
        (request.ids || []).forEach((id) => formData.append('ids[]', id));
    }
}

/**
 * Hilangkan penanda "belum lengkap" untuk nota yang baru dilengkapi.
 *
 * @param {string[]|null} ids  null = semua nota di halaman (scope all)
 */
function markSigned(ids) {
    const match = (value) => ids === null || ids.indexOf(value) !== -1;

    document.querySelectorAll('[data-nota-sign]').forEach(function (el) {
        const value = el.getAttribute('data-nota-sign');
        if (value === 'all' ? ids === null : match(value)) {
            el.removeAttribute('data-nota-sign');
        }
    });
    document.querySelectorAll('input[name="ids[]"][data-unsigned]').forEach(function (checkbox) {
        if (match(checkbox.value)) checkbox.removeAttribute('data-unsigned');
    });
    document.querySelectorAll('[data-unsigned-badge]').forEach(function (badge) {
        if (match(badge.getAttribute('data-unsigned-badge'))) badge.remove();
    });
}

/**
 * Isi nilai searchable-select (hidden value + label tampil), opsional dikunci.
 *
 * @param {HTMLElement|null} wrapper  .searchable-select-wrapper
 * @param {string|null} value
 * @param {string|null} label
 * @param {boolean} locked
 */
function setSearchable(wrapper, value, label, locked) {
    if (!wrapper) return;

    const hidden = wrapper.querySelector('.searchable-select-hidden');
    const input = wrapper.querySelector('.searchable-select-input');
    if (hidden) hidden.value = value || '';
    if (input) {
        input.value = label || '';
        if (locked) {
            input.disabled = true;
            input.classList.add(...LOCKED_CLASSES);
        }
    }
}

/**
 * Render satu kotak tanda tangan per nota dari template form Tambah Nota.
 *
 * @param {Array<Object>} notas  Hasil NotaController::unsigned
 */
function renderSignForm(notas) {
    const parts = signModalParts();
    if (!parts.template || !parts.rows) return;

    if (parts.message) {
        parts.message.textContent = notas.length === 1
            ? 'Nota ' + notas[0].id_nota + ' belum lengkap tanda tangannya.'
            : notas.length + ' nota belum lengkap tanda tangannya.';
    }

    parts.rows.innerHTML = '';

    notas.forEach(function (nota, index) {
        const row = document.createElement('div');
        row.className = 'nota-sign-row';
        row.dataset.id = nota.id_nota;
        row.dataset.needsSigner = nota.needs_signer ? '1' : '0';
        row.dataset.needsReceiver = nota.needs_receiver ? '1' : '0';
        row.innerHTML = parts.template.innerHTML.split('__ROW__').join(String(index));
        parts.rows.appendChild(row);

        const title = row.querySelector('.nota-signature-title');
        if (title) {
            title.textContent = 'Tanda Tangan Nota (2) — ' + nota.id_nota + (nota.kepada ? ' · Kepada: ' + nota.kepada : '');
        }

        const receiverLabel = row.querySelector('.nota-signature-receiver-label');
        if (receiverLabel) receiverLabel.textContent = nota.tipe === 'proyek' ? 'Tanda Terima' : 'Penerima';

        const receiver = row.querySelector('input[name="penerima"]');
        if (receiver) {
            receiver.value = nota.penerima || '';
            receiver.dataset.kepada = nota.kepada || '';
            if (!nota.needs_receiver) {
                receiver.readOnly = true;
                receiver.classList.remove('bg-surface-base');
                receiver.classList.add(...LOCKED_CLASSES);
                receiver.title = 'Sudah terisi';
            }
        }

        const signerWrap = row.querySelector('input[name="petinggi_id"]')?.closest('.searchable-select-wrapper');
        const divisiWrap = row.querySelector('input[name="divisi"]')?.closest('.searchable-select-wrapper');
        if (!nota.needs_signer) {
            setSearchable(signerWrap, nota.signer_id ? String(nota.signer_id) : '', nota.signer_name, true);
            setSearchable(divisiWrap, nota.divisi, nota.divisi || '-', true);
        }
    });

    if (typeof window.initSearchableSelects === 'function') {
        window.initSearchableSelects(parts.rows);
    }

    if (parts.actions) parts.actions.classList.toggle('hidden', notas.length < 2);
}

/**
 * Buka modal lengkapi tanda tangan.
 *
 * @param {{ids?: string[]|null, scope?: string|null, query?: Object, onSigned: Function}} request
 */
async function requireNotaSignature(request) {
    const parts = signModalParts();
    if (!parts.modal || !parts.config) {
        request.onSigned();
        return;
    }

    pendingSign = request;
    parts.error.classList.add('hidden');
    parts.loading.classList.remove('hidden');
    parts.actions.classList.add('hidden');
    parts.rows.innerHTML = '';
    if (parts.button) parts.button.disabled = true;
    window.openModal('notaSignModal');

    try {
        const formData = new FormData();
        formData.append('_token', parts.config.dataset.csrf || '');
        appendScope(formData, request);

        const response = await fetch(parts.config.dataset.unsignedUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);

        const notas = (await response.json()).data || [];

        if (pendingSign !== request) return;

        if (notas.length === 0) {
            // Sudah lengkap (mis. dilengkapi di tab lain) → langsung pratinjau
            pendingSign = null;
            markSigned(request.scope === 'all' ? null : (request.ids || []));
            window.closeModal('notaSignModal');
            request.onSigned();
            return;
        }

        renderSignForm(notas);
    } catch (e) {
        parts.error.textContent = 'Data nota gagal dimuat. Silakan coba lagi.';
        parts.error.classList.remove('hidden');
    } finally {
        parts.loading.classList.add('hidden');
        if (parts.button) parts.button.disabled = false;
    }
}

window.requireNotaSignature = requireNotaSignature;

window.confirmNotaSign = async function () {
    const parts = signModalParts();
    if (!pendingSign || !parts.config) return;

    const formData = new FormData();
    formData.append('_token', parts.config.dataset.csrf || '');

    // Validasi tiap nota: bagian yang kosong wajib diisi
    const rows = Array.from(parts.rows.querySelectorAll('.nota-sign-row'));
    for (const row of rows) {
        const id = row.dataset.id;
        const key = 'signatures[' + id + ']';

        if (row.dataset.needsReceiver === '1') {
            const receiver = row.querySelector('input[name="penerima"]');
            if (!receiver.value.trim()) {
                receiver.setCustomValidity('Nama penerima wajib diisi');
                receiver.reportValidity();
                return;
            }
            formData.append(key + '[penerima]', receiver.value.trim());
        }

        if (row.dataset.needsSigner === '1') {
            const hidden = row.querySelector('input[name="petinggi_id"]');
            const search = hidden ? hidden.closest('.searchable-select-wrapper').querySelector('.searchable-select-input') : null;
            if (!hidden || !hidden.value) {
                if (search) {
                    search.setCustomValidity('Penanda tangan Hormat Kami wajib dipilih');
                    search.reportValidity();
                }
                return;
            }
            formData.append(key + '[petinggi_id]', hidden.value);

            const divisi = row.querySelector('input[name="divisi"]');
            if (divisi && divisi.value) formData.append(key + '[divisi]', divisi.value);
        }
    }
    appendScope(formData, pendingSign);

    const originalText = parts.button ? parts.button.innerHTML : '';
    if (parts.button) {
        parts.button.disabled = true;
        parts.button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
    }

    try {
        const response = await fetch(parts.config.dataset.signUrl, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            const firstError = data.errors ? Object.values(data.errors)[0] : null;
            throw new Error((firstError && firstError[0]) || data.message || 'Gagal menyimpan tanda tangan.');
        }

        const request = pendingSign;
        pendingSign = null;
        markSigned(request.scope === 'all' ? null : (request.ids || []));
        window.closeModal('notaSignModal');
        request.onSigned();
    } catch (e) {
        parts.error.textContent = e.message;
        parts.error.classList.remove('hidden');
    } finally {
        if (parts.button) {
            parts.button.disabled = false;
            parts.button.innerHTML = originalText;
        }
    }
};

// Bantuan isi cepat (lebih dari satu nota)
document.addEventListener('click', function (e) {
    const rows = Array.from(document.querySelectorAll('#notaSignModal .nota-sign-row'));

    // Penerima kosong = "Kepada" nota masing-masing
    if (e.target.closest('.nota-sign-copy-kepada')) {
        rows.forEach(function (row) {
            const receiver = row.querySelector('input[name="penerima"]');
            if (receiver && !receiver.readOnly && !receiver.value.trim() && receiver.dataset.kepada) {
                receiver.value = receiver.dataset.kepada;
                receiver.setCustomValidity('');
            }
        });
    }

    // Hormat Kami (+ divisi) kosong = pilihan nota pertama yang sudah terisi
    if (e.target.closest('.nota-sign-copy-signer')) {
        const source = rows.find(function (row) {
            const hidden = row.querySelector('input[name="petinggi_id"]');
            return hidden && hidden.value;
        });
        if (!source) return;

        const pick = (row, name) => row.querySelector('input[name="' + name + '"]').closest('.searchable-select-wrapper');
        const sourceSigner = pick(source, 'petinggi_id');
        const sourceDivisi = pick(source, 'divisi');

        rows.forEach(function (row) {
            if (row === source || row.dataset.needsSigner !== '1') return;
            const signer = pick(row, 'petinggi_id');
            if (signer.querySelector('.searchable-select-hidden').value) return;

            setSearchable(signer, sourceSigner.querySelector('.searchable-select-hidden').value,
                sourceSigner.querySelector('.searchable-select-input').value, false);
            setSearchable(pick(row, 'divisi'), sourceDivisi.querySelector('.searchable-select-hidden').value,
                sourceDivisi.querySelector('.searchable-select-input').value, false);
            signer.querySelector('.searchable-select-input').setCustomValidity('');
        });
    }
});

window.addEventListener('click', function (e) {
    const link = e.target.closest('a[data-nota-sign]');
    if (!link || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey) return;

    // defaultPrevented → document-preview.js tidak membuka pratinjau sebelum lengkap
    e.preventDefault();

    const value = link.getAttribute('data-nota-sign');
    const url = new URL(link.href, window.location.origin);
    const title = link.getAttribute('data-preview-title') || 'Pratinjau Nota';

    requireNotaSignature({
        ids: value === 'all' ? null : [value],
        scope: value === 'all' ? 'all' : null,
        query: Object.fromEntries(url.searchParams.entries()),
        onSigned: function () {
            window.openDocumentPreview(url.toString(), { title: title });
        },
    });
}, true);
