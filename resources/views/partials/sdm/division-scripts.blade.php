<script>
    /**
     * Division (Divisi) page JavaScript module.
     *
     * Handles:
     * - Select All / Deselect All checkboxes
     * - Individual checkbox state management and delete button enable/disable
     * - Bulk delete form submission with loading state
     * - Add/Edit form submit handling with double-submit prevention
     */

    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    // ==========================================
    // SELECT ALL CHECKBOX
    // ==========================================

    /**
     * Select All Checkbox
     * Ketika checkbox "Pilih Semua" diklik, centang/batalkan semua
     * checkbox individu dan perbarui status tombol hapus.
     */
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButtonState();
    });

    /**
     * Individual Checkbox
     * Ketika checkbox individu diklik, perbarui status checkbox "Pilih Semua"
     * (centang semua jika semua tercentang, batalkan jika ada yang belum).
     */
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateDeleteButtonState();
        });
    });

    /**
     * Update Delete Button State
     * Aktifkan/nonaktifkan tombol hapus berdasarkan jumlah checkbox yang dipilih.
     * Jika tidak ada yang dipilih, tombol disabled dengan opacity rendah.
     */
    function updateDeleteButtonState() {
        const deleteButton = document.getElementById('delete-button');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

        if (checkedCheckboxes.length > 0) {
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');
        } else {
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');
        }
    }

    // ==========================================
    // BULK DELETE CONFIRMATION
    // ==========================================

    /**
     * Submit Delete Form
     * Menampilkan loading spinner pada tombol konfirmasi lalu submit form hapus.
     */
    function submitDeleteForm() {
        const deleteBtn = document.getElementById('confirm-btn-deleteModal');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }
        document.getElementById('deleteForm').submit();
    }

    // Initialize delete button state on page load
    updateDeleteButtonState();

    // ==========================================
    // ADD/EDIT FORM SUBMIT HANDLERS
    // ==========================================

    /**
     * Handle Add Modal Submit
     * Mencegah double submit pada form tambah divisi.
     */
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Handle Edit Modal Submits
     * Mencegah double submit pada semua form edit divisi.
     */
    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>
