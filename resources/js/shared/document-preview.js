/**
 * Pratinjau Dokumen & File - Shared Module
 *
 * Revisi klien: semua file yang bisa diunduh (PDF/Excel/Word dari menu
 * invoice, dokumen, laporan, dsb.) ditampilkan dulu sebagai PRATINJAU di
 * dalam halaman (modal), baru diunduh saat pengguna menekan "Download".
 * Tidak ada tab baru yang dibuka.
 *
 * Cara kerja:
 * - Link export (pola URL /print/pdf, /export/excel, /export-pdf, dst.) atau
 *   link ber-atribut `data-preview` otomatis dicegat → modal pratinjau.
 *   Server mengembalikan versi pratinjau saat URL diberi `preview=1`
 *   (middleware PreviewDownload: PDF inline, Excel → HTML).
 * - Link bukti/file (gambar, PDF statis) & link ber-target `_blank` yang
 *   menuju /storage → modal pratinjau file.
 * - Link `_blank` lain dibuka di tab yang sama.
 * - Tambahkan `data-no-preview` pada link untuk melewati pencegatan.
 *
 * Ekspos ke window:
 * - openDocumentPreview(source, options)
 *     source: URL (GET) atau { url, method: 'POST', formData }
 *     options: { title }
 * - openFilePreview(url, { title, downloadName })
 * - closeDocumentPreview()
 */

const EXPORT_URL_PATTERN = /\/(print|export)[/-](pdf|excel|word)(?=[/?#]|$)/i;
const FILE_URL_PATTERN = /\/storage\/|\.(png|jpe?g|gif|webp|bmp|svg|pdf)(\?|#|$)/i;
const IMAGE_URL_PATTERN = /\.(png|jpe?g|gif|webp|bmp|svg)(\?|#|$)/i;

let modal = null;
let currentDownload = null;
let currentObjectUrl = null;

/**
 * Tambahkan/ubah query parameter pada URL.
 *
 * @param  {string} url
 * @param  {string} key
 * @param  {string} value
 * @return {string}
 */
function withParam(url, key, value) {
    const parsed = new URL(url, window.location.origin);
    parsed.searchParams.set(key, value);
    return parsed.toString();
}

/**
 * Ambil nama file dari header Content-Disposition.
 *
 * @param  {Response} response
 * @param  {string}   fallback
 * @return {string}
 */
function filenameFromResponse(response, fallback) {
    const disposition = response.headers.get('Content-Disposition') || '';
    const utf8 = disposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8) return decodeURIComponent(utf8[1].replace(/"/g, ''));
    const plain = disposition.match(/filename="?([^";]+)"?/i);
    return plain ? plain[1] : fallback;
}

/**
 * Unduh Blob sebagai file (tanpa membuka tab baru).
 *
 * @param {Blob}   blob
 * @param {string} filename
 */
function saveBlob(blob, filename) {
    const objectUrl = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = objectUrl;
    anchor.download = filename || 'dokumen';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
}

/**
 * Unduh URL langsung di tab yang sama (server mengirim attachment).
 *
 * @param {string} url
 * @param {string} downloadName
 */
function downloadUrl(url, downloadName = '') {
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.setAttribute('download', downloadName);
    anchor.setAttribute('data-no-preview', '');
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
}

/**
 * Bangun elemen modal sekali saja.
 *
 * @return {HTMLElement}
 */
function ensureModal() {
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'documentPreviewModal';
    modal.className = 'hidden fixed inset-0 z-[70] bg-black/60 items-center justify-center p-2 sm:p-6';
    modal.innerHTML =
        '<div class="bg-surface-base rounded-xl shadow-xl w-full max-w-6xl h-[92vh] flex flex-col overflow-hidden">' +
            '<div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-border-light">' +
                '<h2 class="dp-title text-base sm:text-lg font-semibold text-text-heading truncate">Pratinjau</h2>' +
                '<div class="flex items-center gap-2 shrink-0">' +
                    '<button type="button" class="dp-back flex items-center gap-2 bg-button-cancel hover:bg-button-cancel-hover text-text-primary px-3 py-2 rounded-lg text-sm">' +
                        '<i class="fa-solid fa-arrow-left"></i><span>Kembali</span>' +
                    '</button>' +
                    '<button type="button" class="dp-download flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-3 py-2 rounded-lg text-sm">' +
                        '<i class="fa-solid fa-download"></i><span>Download</span>' +
                    '</button>' +
                '</div>' +
            '</div>' +
            '<div class="relative flex-1 bg-surface-secondary">' +
                '<div class="dp-loading absolute inset-0 flex items-center justify-center text-text-secondary text-sm">' +
                    '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Memuat pratinjau...' +
                '</div>' +
                '<iframe class="dp-frame hidden w-full h-full border-0 bg-white" title="Pratinjau dokumen"></iframe>' +
                '<div class="dp-image-wrap hidden w-full h-full overflow-auto items-center justify-center p-4">' +
                    '<img class="dp-image max-w-full max-h-full object-contain shadow" alt="Pratinjau">' +
                '</div>' +
            '</div>' +
        '</div>';
    document.body.appendChild(modal);

    modal.querySelector('.dp-back').addEventListener('click', closeDocumentPreview);
    modal.querySelector('.dp-download').addEventListener('click', function () {
        if (currentDownload) currentDownload(this);
    });
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeDocumentPreview();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeDocumentPreview();
    });

    const frame = modal.querySelector('.dp-frame');
    frame.addEventListener('load', () => {
        if (frame.getAttribute('src')) {
            modal.querySelector('.dp-loading').classList.add('hidden');
            frame.classList.remove('hidden');
        }
    });

    return modal;
}

/**
 * Tampilkan modal dalam keadaan "memuat".
 *
 * @param {string} title
 */
function showModal(title) {
    const el = ensureModal();
    el.querySelector('.dp-title').textContent = title || 'Pratinjau';
    el.querySelector('.dp-loading').classList.remove('hidden');
    el.querySelector('.dp-loading').innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Memuat pratinjau...';
    el.querySelector('.dp-frame').classList.add('hidden');
    el.querySelector('.dp-image-wrap').classList.add('hidden');
    el.querySelector('.dp-image-wrap').classList.remove('flex');
    el.classList.remove('hidden');
    el.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

/**
 * Tampilkan pesan error di area pratinjau.
 *
 * @param {string} message
 */
function showError(message) {
    const loading = ensureModal().querySelector('.dp-loading');
    loading.classList.remove('hidden');
    loading.innerHTML = '<span class="text-error"><i class="fa-solid fa-circle-exclamation mr-2"></i>' + message + '</span>';
}

/**
 * Tutup modal pratinjau & bersihkan sumber daya.
 */
function closeDocumentPreview() {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    const frame = modal.querySelector('.dp-frame');
    frame.removeAttribute('src');
    frame.classList.add('hidden');
    modal.querySelector('.dp-image').removeAttribute('src');
    if (currentObjectUrl) {
        URL.revokeObjectURL(currentObjectUrl);
        currentObjectUrl = null;
    }
    currentDownload = null;
    document.body.style.overflow = '';
}

/**
 * Buka pratinjau dokumen hasil export.
 *
 * @param {string|{url: string, method?: string, formData?: FormData}} source
 * @param {{title?: string}} options
 */
function openDocumentPreview(source, options = {}) {
    const request = typeof source === 'string' ? { url: source } : source;
    const method = (request.method || 'GET').toUpperCase();
    showModal(options.title || 'Pratinjau Dokumen');

    if (method === 'GET') {
        // Pratinjau dimuat langsung oleh iframe (server: preview=1)
        modal.querySelector('.dp-frame').setAttribute('src', withParam(request.url, 'preview', '1'));
        currentDownload = () => downloadUrl(request.url);
        return;
    }

    // POST (mis. Export Dipilih): ambil pratinjau via fetch lalu tampilkan sebagai blob
    const send = (preview) => fetch(preview ? withParam(request.url, 'preview', '1') : request.url, {
        method,
        body: request.formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).then((response) => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response;
    });

    send(true)
        .then((response) => response.blob())
        .then((blob) => {
            currentObjectUrl = URL.createObjectURL(blob);
            modal.querySelector('.dp-frame').setAttribute('src', currentObjectUrl);
        })
        .catch(() => showError('Pratinjau gagal dimuat. Silakan coba lagi.'));

    currentDownload = (button) => {
        button.disabled = true;
        send(false)
            .then(async (response) => saveBlob(await response.blob(), filenameFromResponse(response, 'dokumen')))
            .catch(() => alert('Download gagal. Silakan coba lagi.'))
            .finally(() => { button.disabled = false; });
    };
}

/**
 * Buka pratinjau file statis (gambar bukti / PDF di storage).
 *
 * @param {string} url
 * @param {{title?: string, downloadName?: string}} options
 */
function openFilePreview(url, options = {}) {
    showModal(options.title || 'Pratinjau File');
    const downloadName = options.downloadName || decodeURIComponent((url.split('?')[0].split('/').pop()) || 'file');

    if (IMAGE_URL_PATTERN.test(url)) {
        const wrap = modal.querySelector('.dp-image-wrap');
        const img = modal.querySelector('.dp-image');
        img.onload = () => {
            modal.querySelector('.dp-loading').classList.add('hidden');
            wrap.classList.remove('hidden');
            wrap.classList.add('flex');
        };
        img.onerror = () => showError('Gambar tidak dapat dimuat.');
        img.setAttribute('src', url);
    } else {
        modal.querySelector('.dp-frame').setAttribute('src', url);
    }

    currentDownload = () => downloadUrl(url, downloadName);
}

/**
 * Cegat klik link: export → pratinjau dokumen, file/_blank → pratinjau file
 * atau buka di tab yang sama.
 */
document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href]');
    if (!link || link.hasAttribute('data-no-preview') || e.defaultPrevented) return;
    if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

    let url;
    try {
        url = new URL(href, window.location.origin);
    } catch (err) {
        return;
    }
    if (url.origin !== window.location.origin) return;

    const title = (link.getAttribute('data-preview-title') || link.textContent || '').trim().replace(/\s+/g, ' ');

    if (link.hasAttribute('data-preview') || EXPORT_URL_PATTERN.test(url.pathname)) {
        e.preventDefault();
        openDocumentPreview(url.toString(), { title: title || 'Pratinjau Dokumen' });
        return;
    }

    if (link.hasAttribute('data-file-preview')) {
        e.preventDefault();
        openFilePreview(url.toString(), { title: title || 'Pratinjau File' });
        return;
    }

    if (link.getAttribute('target') === '_blank') {
        e.preventDefault();
        if (link.hasAttribute('data-file-preview') || FILE_URL_PATTERN.test(url.pathname)) {
            openFilePreview(url.toString(), { title: title || 'Pratinjau File' });
        } else {
            window.location.href = url.toString();
        }
    }
}, true);

window.openDocumentPreview = openDocumentPreview;
window.openFilePreview = openFilePreview;
window.closeDocumentPreview = closeDocumentPreview;
