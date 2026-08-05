/**
 * RAB (Rencana Anggaran Biaya) - JavaScript Halaman Index
 *
 * Modul ini menangani:
 * - Select all checkbox dan update tombol delete
 * - Submit form dengan loading indicator
 * - Delete dengan modal konfirmasi
 * - Export PDF/Excel dengan loading indicator
 *
 * Manajemen struktur dinamis (kategori/sub-kategori/item) dihandle oleh rab-dynamic.js
 */
import './rab-dynamic';

/**
 * Inisialisasi semua interaksi pada halaman index RAB.
 *
 * Alur:
 * 1. Pasang handler checkbox "Pilih Semua" dan sinkronisasi checkbox individual.
 * 2. Inisialisasi status awal tombol hapus.
 * 3. Pasang handler submit untuk form tambah & edit RAB (loading + prepare JSON).
 * 4. Pasang handler klik untuk tombol ekspor PDF/Excel dengan indikator loading.
 */
document.addEventListener('DOMContentLoaded', function () {

    // ─── Checkbox Pilih Semua ───────────────────────────────────────────
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

    // ─── Submit Hapus (dipanggil dari onclick di modal) ──────────────
    /**
     * Submit form hapus dengan loading indicator.
     * Dipanggil dari inline onclick pada tombol konfirmasi modal hapus.
     *
     * Alur:
     * 1. Tampilkan spinner "Menghapus..." pada tombol dan nonaktifkan.
     * 2. Submit form `deleteForm` (bulk delete berdasarkan checkbox terpilih).
     */
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

    // ─── Form Tambah RAB ─────────────────────────────────────────────────
    /**
     * Handler submit form tambah RAB.
     *
     * Alur:
     * 1. Cegah double submit via handleFormSubmit (loading pada tombol submit).
     * 2. Panggil window.prepareRABSubmit() untuk memindai DOM struktur
     *    kategori/sub-kategori/item dan biaya lain-lain menjadi JSON
     *    pada hidden input sebelum form benar-benar dikirim.
     */
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

    // ─── Form Edit RAB ───────────────────────────────────────────────
    /**
     * Handler submit untuk semua form edit RAB (`editRABForm{rabNumber}`).
     *
     * Alur:
     * 1. Cegah double submit via handleFormSubmit (loading pada tombol submit).
     * 2. Ekstrak rabNumber dari ID form.
     * 3. Panggil window.prepareEditRABSubmit(rabNumber) untuk memindai DOM
     *    modal edit menjadi JSON pada hidden input sebelum form dikirim.
     */
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

    // ─── Tangani Ekspor PDF/Excel dengan loading ─────────────────────────
    /**
     * Handler klik untuk elemen dengan atribut `data-export`.
     *
     * Alur:
     * 1. Ambil URL dari `data-export-url` dan teks loading dari `data-export-loading`.
     * 2. Jika URL tersedia, cegah navigasi default lalu delegasikan download
     *    ke window.handleDownload (AJAX + download file dengan indikator loading).
     */
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
