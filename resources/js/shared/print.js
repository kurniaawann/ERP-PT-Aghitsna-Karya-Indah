/**
 * Shared Print Utilities
 *
 * - Dropdown Print Laporan handler (id: printDropdownButton / printDropdownMenu)
 * - Global download link handler untuk link export/print PDF & Excel
 * - sharedPrintSelected: export data terpilih via AJAX menjadi file PDF
 */

/**
 * Mengambil token CSRF dari halaman.
 *
 * Alur:
 * 1. Cari input tersembunyi bernama "_token"; jika ada dan berisi nilai, pakai nilainya.
 * 2. Jika tidak ada, cari meta tag "csrf-token" dan ambil atribut content.
 * 3. Fallback: string kosong.
 *
 * @returns {string}  Token CSRF yang ditemukan, atau '' bila tidak ada.
 */
function getCsrfToken() {
    const input = document.querySelector('input[name="_token"]');
    if (input && input.value) return input.value;
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

/* ==========================================
 * PRINT SELECTED (Export Dipilih)
 * ========================================== */

/**
 * Export data terpilih (checkbox) via AJAX menjadi file PDF/Excel.
 *
 * Alur:
 * 1. Kumpulkan semua checkbox tercentang sesuai checkboxSelector.
 * 2. Jika tidak ada yang dipilih, tampilkan alert emptyMessage dan kembalikan false.
 * 3. Jika triggerBtn sedang dalam proses (dataset.downloading === 'true'),
 *    kembalikan false untuk mencegah klik ganda.
 * 4. Tandai triggerBtn sebagai sedang download dan tampilkan loading.
 * 5. Susun FormData berisi token CSRF dan semua nilai ids[].
 * 6. Kirim POST ke route dengan header X-Requested-With: XMLHttpRequest.
 * 7. Jika response tidak OK, lempar error.
 * 8. Ubah response menjadi Blob, tentukan nama file via getFilenameFromResponse(),
 *    buat object URL, simulasikan klik pada elemen <a> temp untuk memicu
 *    download, lalu bersihkan elemen temp dan revoke object URL.
 * 9. Bila error, catat di console.
 * 10. Di finally: reset tanda downloading dan matikan mode loading tombol.
 *
 * @param  {string}      [route]           URL endpoint export.
 * @param  {HTMLElement} [triggerBtn]      Tombol pemicu yang menampilkan loading.
 * @param  {string}      [checkboxSelector] Selector checkbox yang dipakai untuk mengumpulkan ids[].
 * @param  {string}      [emptyMessage]    Pesan alert bila tidak ada data terpilih.
 * @returns {boolean}  true bila proses diteruskan (fetch dimulai), false bila dibatalkan.
 */
function sharedPrintSelected(route, triggerBtn = null, checkboxSelector = 'input[name="ids[]"]:checked', emptyMessage = 'Tidak ada data yang dipilih!') {
    const checkedCheckboxes = document.querySelectorAll(checkboxSelector);

    if (checkedCheckboxes.length === 0) {
        alert(emptyMessage);
        return false;
    }

    if (triggerBtn && triggerBtn.dataset.downloading === 'true') return false;

    if (triggerBtn) {
        triggerBtn.dataset.downloading = 'true';
    }

    setButtonLoading(triggerBtn, true, 'Memproses...');

    const formData = new FormData();
    formData.append('_token', getCsrfToken());

    Array.from(checkedCheckboxes).forEach(checkbox => {
        formData.append('ids[]', checkbox.value);
    });

    fetch(route, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(async (response) => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
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
    })
    .catch(error => {
        console.error('Download failed:', error);
    })
    .finally(() => {
        if (triggerBtn) {
            triggerBtn.dataset.downloading = 'false';
        }
        setButtonLoading(triggerBtn, false);
    });

    return true;
}

/**
 * Ekspos sharedPrintSelected ke global window untuk dipanggil dari tombol
 * "Cetak/Export Terpilih" pada halaman listing di Blade.
 *
 * @returns {void}
 */
window.sharedPrintSelected = sharedPrintSelected;

/* ==========================================
 * DROPDOWN PRINT LAPORAN
 * ========================================== */

/**
 * Inisialisasi dropdown "Print Laporan" saat DOM siap.
 *
 * Alur:
 * 1. Ambil tombol (printDropdownButton) dan menu (printDropdownMenu) berdasarkan id.
 * 2. Jika keduanya ada:
 *    - Klik tombol: toggle kelas 'hidden' pada menu (buka/tutup) dan hentikan
 *      propagasi agar klik di dalam menu tidak menutupnya.
 *    - Klik dokumen di luar tombol & menu: tutup menu (tambahkan 'hidden').
 *    - Klik di dalam menu: stopPropagation agar menu tidak tertutup.
 *
 * @returns {void}
 */
document.addEventListener('DOMContentLoaded', function () {
    const printDropdownButton = document.getElementById('printDropdownButton');
    const printDropdownMenu = document.getElementById('printDropdownMenu');

    if (printDropdownButton && printDropdownMenu) {
        printDropdownButton.addEventListener('click', function (e) {
            e.stopPropagation();
            printDropdownMenu.classList.toggle('hidden');
        });

        // Tutup dropdown saat mengklik di luar
        document.addEventListener('click', function (e) {
            if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                printDropdownMenu.classList.add('hidden');
            }
        });

        // Cegah dropdown tertutup saat mengklik di dalam
        printDropdownMenu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }
});

/* ==========================================
 * GLOBAL DOWNLOAD LINK HANDLER
 * ========================================== */

/**
 * Global handler untuk semua link export/print PDF & Excel.
 *
 * Alur:
 * 1. Saat DOM siap, pilih semua <a> yang href-nya mengandung pola
 *    /export/pdf, /export/excel, /print/pdf, /print/excel, /export-pdf,
 *    atau /export-excel.
 * 2. Untuk setiap link, pasang event click:
 *    - preventDefault dan stopPropagation agar navigasi default tidak terjadi.
 *    - Jika link berada di dalam dropdown print, tutup dropdown tersebut.
 *    - Panggil handleDownload(href link, elemen link, 'Downloading...') yang
 *      memicu unduhan via AJAX sekaligus menampilkan loading pada link.
 *
 * @returns {void}
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('a[href*="/export/pdf"], a[href*="/export/excel"], a[href*="/print/pdf"], a[href*="/print/excel"], a[href*="/export-pdf"], a[href*="/export-excel"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            // Tutup dropdown jika link berada di dalam dropdown print
            const dropdown = this.closest('#printDropdownMenu');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
            handleDownload(this.href, this, 'Downloading...');
        });
    });
});
