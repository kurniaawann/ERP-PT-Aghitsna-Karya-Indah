/**
 * RAB (Rencana Anggaran Biaya) - Index Page JavaScript
 *
 * Modul ini menangani:
 * - Select all checkbox dan update tombol delete
 * - Submit form dengan loading indicator
 * - Delete dengan modal konfirmasi
 * - Export PDF/Excel dengan loading indicator
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Select All Checkbox ───────────────────────────────────────────
    const selectAll = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');

    /**
     * Mengupdate status enabled/disabled tombol Hapus
     * berdasarkan jumlah checkbox yang dipilih.
     */
    function updateDeleteButton() {
        const checked = document.querySelectorAll('.item-checkbox:checked').length;
        const deleteBtn = document.getElementById('delete-button');
        if (!deleteBtn) return;
        deleteBtn.disabled = checked === 0;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            itemCheckboxes.forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateDeleteButton();
        });
    }

    itemCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (selectAll) {
                const all = document.querySelectorAll('.item-checkbox:checked');
                selectAll.checked = all.length === itemCheckboxes.length;
            }
            updateDeleteButton();
        });
    });

    updateDeleteButton();

    // ─── Submit Delete (dipanggil dari onclick di modal) ──────────────
    window.submitDeleteForm = function () {
        const btn = document.getElementById('confirm-btn-deleteModal');
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            btn.disabled = true;
            btn.classList.add('opacity-70', 'cursor-not-allowed');
        }
        const f = document.getElementById('deleteForm');
        if (f) f.submit();
    };

    // ─── Add RAB Form ─────────────────────────────────────────────────
    const addForm = document.getElementById('addRABForm');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const orig = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn && !handleFormSubmit(submitBtn, orig)) {
                e.preventDefault();
                return;
            }
            window.prepareRABSubmit();
        });
    }

    // ─── Edit RAB Forms ───────────────────────────────────────────────
    document.querySelectorAll('form[id^="editRABForm"]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const orig = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn && !handleFormSubmit(submitBtn, orig)) {
                e.preventDefault();
                return;
            }
            const num = this.id.replace('editRABForm', '');
            window.prepareEditRABSubmit(num);
        });
    });

    // ─── Handle Export PDF/Excel with loading ─────────────────────────
    document.querySelectorAll('[data-export]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const url = this.getAttribute('data-export-url');
            const loading = this.getAttribute('data-export-loading') || 'Downloading...';
            if (url && window.handleDownload) {
                e.preventDefault();
                window.handleDownload(url, this, loading);
            }
        });
    });
});
