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
 * Export data terpilih (checkbox) → tampilkan PRATINJAU dulu, lalu Download.
 *
 * Alur:
 * 1. Kumpulkan semua checkbox tercentang sesuai checkboxSelector.
 * 2. Jika tidak ada yang dipilih, tampilkan alert emptyMessage dan kembalikan false.
 * 3. Susun FormData berisi token CSRF dan semua nilai ids[].
 * 4. Buka modal pratinjau (openDocumentPreview, POST): server mengirim versi
 *    pratinjau (preview=1); tombol Download di modal mengirim ulang POST tanpa
 *    preview dan menyimpan file hasilnya.
 *
 * @param  {string}      [route]           URL endpoint export.
 * @param  {HTMLElement} [triggerBtn]      Tombol pemicu (menutup dropdown bila ada).
 * @param  {string}      [checkboxSelector] Selector checkbox yang dipakai untuk mengumpulkan ids[].
 * @param  {string}      [emptyMessage]    Pesan alert bila tidak ada data terpilih.
 * @param  {Object}      [extraParams]     Parameter tambahan ikut dikirim (mis. filter
 *                                         halaman untuk teks "x dari y yang diajukan").
 * @returns {boolean}  true bila pratinjau dibuka, false bila dibatalkan.
 */
function sharedPrintSelected(route, triggerBtn = null, checkboxSelector = 'input[name="ids[]"]:checked', emptyMessage = 'Tidak ada data yang dipilih!', extraParams = {}) {
    const checkedCheckboxes = document.querySelectorAll(checkboxSelector);

    if (checkedCheckboxes.length === 0) {
        alert(emptyMessage);
        return false;
    }

    const formData = new FormData();
    formData.append('_token', getCsrfToken());

    Object.entries(extraParams || {}).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            formData.append(key, value);
        }
    });

    Array.from(checkedCheckboxes).forEach(checkbox => {
        formData.append('ids[]', checkbox.value);
    });

    const dropdown = triggerBtn ? triggerBtn.closest('#printDropdownMenu') : null;
    if (dropdown) {
        dropdown.classList.add('hidden');
    }

    window.openDocumentPreview(
        { url: route, method: 'POST', formData },
        { title: 'Pratinjau ' + checkedCheckboxes.length + ' Data Terpilih' }
    );

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
 * Link export/print PDF & Excel kini ditangani modul pratinjau
 * (`shared/document-preview.js`): klik → pratinjau di modal → Download.
 * Di sini cukup menutup dropdown Print Laporan saat salah satu link diklik.
 *
 * @returns {void}
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#printDropdownMenu a[href]').forEach(function (link) {
        link.addEventListener('click', function () {
            const dropdown = this.closest('#printDropdownMenu');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
        });
    });
});
