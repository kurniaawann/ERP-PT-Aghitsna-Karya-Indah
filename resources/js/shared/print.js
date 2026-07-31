/**
 * Shared Print Utilities
 *
 * - Dropdown Print Laporan handler (id: printDropdownButton / printDropdownMenu)
 * - Global download link handler untuk link export/print PDF & Excel
 * - sharedPrintSelected: export data terpilih via AJAX menjadi file PDF
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

window.sharedPrintSelected = sharedPrintSelected;

/* ==========================================
 * DROPDOWN PRINT LAPORAN
 * ========================================== */

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
