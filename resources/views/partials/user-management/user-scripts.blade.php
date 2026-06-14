<script>
    @include('partials.shared.select-all-script')
    @include('partials.shared.delete-form-script')

    document.addEventListener('DOMContentLoaded', function() {
        initSelectAll('ids[]');

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
