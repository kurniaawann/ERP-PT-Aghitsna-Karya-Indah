<script>
    function submitDeleteForm() {
        const deleteBtn = document.getElementById('confirm-btn-deleteModal');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }
        document.getElementById('deleteForm').submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // ── Select All & delete button state ──────────────────────────────────────
        const selectAllCheckbox = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        const deleteButton = document.getElementById('delete-button');

        function updateDeleteButtonState() {
            const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
            if (deleteButton) deleteButton.disabled = !anyChecked;
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateDeleteButtonState();
            });
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (!this.checked && selectAllCheckbox) selectAllCheckbox.checked = false;
                updateDeleteButtonState();
            });
        });

        updateDeleteButtonState();

        // ── Loading state pada form add modal ──────────────────────────────────────
        const addModal = document.getElementById('addModal');
        if (addModal) {
            const addForm = addModal.querySelector('form');
            if (addForm) {
                addForm.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
                        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                    }
                });
            }
        }

        // ── Loading state pada semua form edit modal ───────────────────────────────
        document.querySelectorAll('[id^="editModal-"]').forEach(modal => {
            const form = modal.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
                        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                    }
                });
            }
        });
    });
</script>
