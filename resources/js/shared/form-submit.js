/**
 * Shared Form Submit Helper
 *
 * Utilitas bersama untuk:
 * - Mencegah double-submit pada form (anti double-submit) dengan flag global
 *   formSubmitState dan indikator loading pada tombol submit.
 * - Download file via AJAX (GET) atau submit form via AJAX (POST) dengan
 *   tombol pemicu yang menampilkan spinner dan dinonaktifkan selama proses.
 *
 * Fungsi yang diekspos ke window: handleFormSubmit, resetFormSubmitState,
 * getFilenameFromResponse, setButtonLoading, handleDownload, handleFormDownload.
 */

/**
 * State global untuk mencegah double-submit.
 *
 * isSubmitting bernilai true sejak form pertama kali disubmit sampai
 * resetFormSubmitState() dipanggil (biasanya dari callback JS setelah submit).
 */
const formSubmitState = {
    isSubmitting: false,
};

/**
 * Menangani submit form dengan mencegah double-submit dan menampilkan
 * indikator loading pada tombol submit.
 *
 * Alur:
 * 1. Jika formSubmitState.isSubmitting sudah true, langsung kembalikan false
 *    (blokir submit ganda yang datang dari tombol lain / pemicu kedua).
 * 2. Set isSubmitting = true untuk mengunci seluruh form.
 * 3. Jika submitBtn disediakan: simpan HTML asli tombol ke dataset.originalHtml
 *    (hanya sekali) agar bisa dikembalikan nanti, nonaktifkan tombol, ganti
 *    isinya dengan spinner + loadingText, lalu beri kelas opacity/cursor.
 * 4. Kembalikan true agar form diteruskan ke submit normal.
 *
 * @param  {HTMLElement}  [submitBtn]     Tombol submit yang menampilkan loading.
 * @param  {string}       [originalText]  Teks/HTML asli tombol yang disimpan (fallback: innerHTML tombol).
 * @param  {string}       [loadingText]   Teks loading yang ditampilkan (default: 'Menyimpan...').
 * @returns {boolean}  false bila sudah dalam proses submit, true bila diproses.
 */
function handleFormSubmit(submitBtn, originalText, loadingText = 'Menyimpan...') {
    if (formSubmitState.isSubmitting) return false;

    formSubmitState.isSubmitting = true;

    if (submitBtn) {
        if (!submitBtn.dataset.originalHtml) {
            submitBtn.dataset.originalHtml = originalText || submitBtn.innerHTML;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    return true;
}

/**
 * Reset state anti double-submit dan kembalikan semua tombol submit ke
 * kondisi semula.
 *
 * Alur:
 * 1. Set formSubmitState.isSubmitting = false agar form bisa disubmit lagi.
 * 2. Iterasi semua button[type="submit"] di dokumen.
 * 3. Untuk tiap tombol: aktifkan kembali, hapus kelas opacity/cursor, dan
 *    pulihkan innerHTML dari dataset.originalHtml (jika tersimpan).
 *
 * @returns {void}
 */
function resetFormSubmitState() {
    formSubmitState.isSubmitting = false;

    document.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = false;
        button.classList.remove('opacity-70', 'cursor-not-allowed');

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    });
}

/**
 * Ekspos handleFormSubmit ke global window untuk dipanggil dari event handler
 * (mis. atribut onclick) pada halaman Blade.
 *
 * @returns {void}
 */
window.handleFormSubmit = handleFormSubmit;

/**
 * Ekspos resetFormSubmitState ke global window untuk dipanggil setelah
 * operasi submit selesai (mis. pada callback validasi/SweetAlert).
 *
 * @returns {void}
 */
window.resetFormSubmitState = resetFormSubmitState;

// ─── Helper Download dengan Indikator Loading ─────────────────────────────────

/**
 * Menentukan nama file dari header Content-Disposition response, atau
 * menurunkan dari nama akhir URL bila header tidak tersedia.
 *
 * Alur:
 * 1. Baca header Content-Disposition.
 * 2. Jika ada, ekstrak nilai filename menggunakan regex.
 * 3. Jika tidak ada, ambil segmen terakhir pathname URL sebagai nama file.
 * 4. Fallback terakhir: 'download'.
 *
 * @param  {Response}  response  Response dari fetch().
 * @returns {string}  Nama file yang diusulkan untuk atribut download.
 */
function getFilenameFromResponse(response) {
    const disposition = response.headers.get('Content-Disposition');
    if (disposition) {
        const match = disposition.match(/filename[^;=\n]*=["']?([^"'\n]*)["']?/);
        if (match) return match[1];
    }
    const url = new URL(response.url);
    const pathname = url.pathname;
    return pathname.substring(pathname.lastIndexOf('/') + 1) || 'download';
}

/**
 * Mengatur tampilan tombol saat proses download berlangsung.
 *
 * Alur:
 * - Saat loading=true: simpan innerHTML asli (sekali saja), nonaktifkan tombol,
 *   tampilkan spinner + loadingText, dan beri kelas yang mengaburkan tombol.
 * - Saat loading=false: aktifkan kembali tombol, pulihkan innerHTML asli,
 *   hapus kelas loading, dan bersihkan dataset.originalHtml.
 *
 * @param  {HTMLElement}  [btn]         Tombol pemicu yang diatur tampilannya.
 * @param  {boolean}      loading       true untuk masuk mode loading, false untuk mengakhiri.
 * @param  {string}       [loadingText] Teks yang ditampilkan saat loading (default: 'Downloading...').
 * @returns {void}
 */
function setButtonLoading(btn, loading, loadingText = 'Downloading...') {
    if (!btn) return;
    if (loading) {
        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
        btn.classList.add('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
        btn.classList.remove('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
        delete btn.dataset.originalHtml;
    }
}

/**
 * Unduh file dari URL via AJAX (GET) dengan indikator loading pada tombol.
 *
 * Alur:
 * 1. Jika triggerBtn sedang dalam proses (dataset.downloading === 'true'),
 *    langsung return untuk mencegah klik ganda.
 * 2. Tandai triggerBtn sebagai sedang download dan tampilkan loading.
 * 3. Fetch URL dengan header X-Requested-With: XMLHttpRequest.
 * 4. Jika response tidak OK, lempar error.
 * 5. Ubah response menjadi Blob, tentukan nama file via getFilenameFromResponse(),
 *    buat object URL, lalu simulasikan klik pada elemen <a> temp untuk memicu
 *    download; bersihkan elemen temp dan revoke object URL.
 * 6. Bila error, catat di console.
 * 7. Di finally: reset tanda downloading dan matikan mode loading tombol.
 *
 * @param  {string}      url          URL endpoint file yang akan diunduh.
 * @param  {HTMLElement} [triggerBtn] Tombol pemicu yang menampilkan loading.
 * @param  {string}      [loadingText] Teks loading pada tombol (default: 'Downloading...').
 * @returns {Promise<void>}
 */
async function handleDownload(url, triggerBtn, loadingText = 'Downloading...') {
    if (triggerBtn && triggerBtn.dataset.downloading === 'true') return;

    if (triggerBtn) {
        triggerBtn.dataset.downloading = 'true';
    }

    setButtonLoading(triggerBtn, true, loadingText);

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const blob = await response.blob();
        const filename = getFilenameFromResponse(response);
        const objectUrl = window.URL.createObjectURL(blob);

        const tempAnchor = document.createElement('a');
        tempAnchor.href = objectUrl;
        tempAnchor.download = filename;
        document.body.appendChild(tempAnchor);
        tempAnchor.click();
        tempAnchor.remove();
        window.URL.revokeObjectURL(objectUrl);
    } catch (error) {
        console.error('Download failed:', error);
    } finally {
        if (triggerBtn) {
            triggerBtn.dataset.downloading = 'false';
        }
        setButtonLoading(triggerBtn, false);
    }
}

/**
 * Submit form via AJAX (POST) lalu unduh respons berupa file (Blob) dengan
 * indikator loading pada tombol pemicu.
 *
 * Alur:
 * 1. Jika triggerBtn sedang dalam proses (dataset.downloading === 'true'),
 *    langsung return untuk mencegah klik ganda.
 * 2. Tandai triggerBtn sebagai sedang download dan tampilkan loading.
 * 3. Resolusi form: jika formOrId berupa string, cari via getElementById;
 *    jika tidak ditemukan, lempar error.
 * 4. Bungkus data form ke FormData.
 * 5. Fetch form.action (POST) dengan body FormData dan header X-Requested-With.
 * 6. Jika response tidak OK, lempar error.
 * 7. Ubah response menjadi Blob, tentukan nama file via getFilenameFromResponse(),
 *    buat object URL, simulasikan klik pada elemen <a> temp untuk memicu
 *    download, lalu bersihkan elemen temp dan revoke object URL.
 * 8. Bila error, catat di console.
 * 9. Di finally: reset tanda downloading dan matikan mode loading tombol.
 *
 * @param  {string|HTMLFormElement}  formOrId     ID form (string) atau elemen form itu sendiri.
 * @param  {HTMLElement}             [triggerBtn] Tombol pemicu yang menampilkan loading.
 * @param  {string}                  [loadingText] Teks loading pada tombol (default: 'Memproses...').
 * @returns {Promise<void>}
 */
async function handleFormDownload(formOrId, triggerBtn, loadingText = 'Memproses...') {
    if (triggerBtn && triggerBtn.dataset.downloading === 'true') return;

    if (triggerBtn) {
        triggerBtn.dataset.downloading = 'true';
    }

    setButtonLoading(triggerBtn, true, loadingText);

    try {
        const form = typeof formOrId === 'string' ? document.getElementById(formOrId) : formOrId;
        if (!form) throw new Error('Form not found');

        const formData = new FormData(form);

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const blob = await response.blob();
        const filename = getFilenameFromResponse(response);
        const objectUrl = window.URL.createObjectURL(blob);

        const tempAnchor = document.createElement('a');
        tempAnchor.href = objectUrl;
        tempAnchor.download = filename;
        document.body.appendChild(tempAnchor);
        tempAnchor.click();
        tempAnchor.remove();
        window.URL.revokeObjectURL(objectUrl);
    } catch (error) {
        console.error('Download failed:', error);
    } finally {
        if (triggerBtn) {
            triggerBtn.dataset.downloading = 'false';
        }
        setButtonLoading(triggerBtn, false);
    }
}

/**
 * Ekspos getFilenameFromResponse ke global window.
 *
 * @returns {void}
 */
window.getFilenameFromResponse = getFilenameFromResponse;

/**
 * Ekspos handleDownload ke global window untuk dipakai oleh handler klik
 * global pada link export/download (lihat shared/print.js).
 *
 * @returns {void}
 */
window.handleDownload = handleDownload;

/**
 * Ekspos handleFormDownload ke global window untuk dipakai dari event handler
 * tombol download berbasis form di Blade.
 *
 * @returns {void}
 */
window.handleFormDownload = handleFormDownload;

/**
 * Ekspos setButtonLoading ke global window untuk mengontrol tampilan loading
 * tombol dari kode lain.
 *
 * @returns {void}
 */
window.setButtonLoading = setButtonLoading;
