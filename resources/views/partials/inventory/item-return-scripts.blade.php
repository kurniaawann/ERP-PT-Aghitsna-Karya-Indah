<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    let isSubmitting = false;

    function handleFormSubmit(submitBtn, originalText) {
        if (isSubmitting) return false;

        isSubmitting = true;

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        return true;
    }

    // ==========================================
    // MAIN SCRIPT - DOM READY
    // ==========================================

    document.addEventListener('DOMContentLoaded', function() {
        // ==========================================
        // FORM SUBMISSION WITH LOADING STATE
        // ==========================================

        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            if (form.id.includes('Modal') || form.method === 'POST' || form.method === 'PUT') {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn && !handleFormSubmit(submitBtn)) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });

        // ==========================================
        // BARANG DROPDOWN & REASON VALIDATION
        // ==========================================

        const barangSelects = document.querySelectorAll('select[name="id_item"]');
        const reasonInputs = document.querySelectorAll('textarea[name="reason"]');

        barangSelects.forEach(select => {
            select.addEventListener('change', function() {
                const selectedText = this.options[this.selectedIndex].text;
                console.log('Item selected:', selectedText);
            });
        });

        reasonInputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.length > 500) {
                    alert('⚠️ Alasan terlalu panjang (max 500 karakter)');
                    this.value = this.value.substring(0, 500);
                }
            });
        });

        // ==========================================
        // QUANTITY VALIDATION
        // ==========================================

        const qtyInputs = document.querySelectorAll('input[name="quantity"]');
        qtyInputs.forEach(input => {
            input.addEventListener('input', function() {
                if (parseInt(this.value) < 1) {
                    alert('⚠️ Jumlah harus minimal 1');
                    this.value = 1;
                }
            });
        });

        // ==========================================
        // CUSTOM DELETE FUNCTION
        // ==========================================

        window.deleteRecord = function(url) {
            if (confirm(
                    'Apakah Anda yakin ingin menghapus data retur ini? Stock akan dikembalikan otomatis.'
                )) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.innerHTML = `@csrf @method('DELETE')`;
                document.body.appendChild(form);
                form.submit();
            }
        };

        // ==========================================
        // MODAL FUNCTIONS (Global)
        // ==========================================

        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        };

        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        };

        // Close modal when clicking outside
        const allModals = document.querySelectorAll('[id*="Modal"]');
        allModals.forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    const closeBtn = this.querySelector('[onclick*="closeModal"]');
                    if (closeBtn) {
                        closeBtn.click();
                    }
                }
            });
        });

        // ==========================================
        // PAGINATION & FILTER RESET
        // ==========================================

        const searchForm = document.querySelector('form[method="GET"]');
        if (searchForm) {
            const inputs = searchForm.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    const pageInput = searchForm.querySelector('input[name="page"]');
                    if (pageInput) {
                        pageInput.remove();
                    }
                });
            });
        }

        // ==========================================
        // EXPORT WITH FILTERS
        // ==========================================

        const printDropdownButton = document.getElementById('printDropdownButton');
        const printDropdownMenu = document.getElementById('printDropdownMenu');

        if (printDropdownButton && printDropdownMenu) {
            printDropdownButton.addEventListener('click', function(e) {
                e.stopPropagation();
                printDropdownMenu.classList.toggle('hidden');
            });

            document.addEventListener('click', function(e) {
                if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                    printDropdownMenu.classList.add('hidden');
                }
            });

            const excelBtn = printDropdownMenu.querySelector('[href*="export/excel"]');
            const pdfBtn = printDropdownMenu.querySelector('[href*="export/pdf"]');

            function getFilterParams() {
                const searchInput = document.querySelector('input[name="search"]');
                const dateFromInput = document.querySelector('input[name="date_from"]');
                const dateToInput = document.querySelector('input[name="date_to"]');
                const monthInput = document.querySelector('select[name="month"]');
                const yearInput = document.querySelector('select[name="year"]');
                const returnTypeInput = document.querySelector('select[name="return_type"]');

                let params = new URLSearchParams();

                if (searchInput && searchInput.value) {
                    params.append('search', searchInput.value);
                }
                if (dateFromInput && dateFromInput.value) {
                    params.append('date_from', dateFromInput.value);
                }
                if (dateToInput && dateToInput.value) {
                    params.append('date_to', dateToInput.value);
                }
                if (monthInput && monthInput.value) {
                    params.append('month', monthInput.value);
                }
                if (yearInput && yearInput.value) {
                    params.append('year', yearInput.value);
                }
                if (returnTypeInput && returnTypeInput.value) {
                    params.append('return_type', returnTypeInput.value);
                }

                return params.toString();
            }

            if (excelBtn) {
                excelBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const baseUrl = '{{ route('item-return.export.excel') }}';
                    const filterParams = getFilterParams();
                    const fullUrl = filterParams ? baseUrl + '?' + filterParams : baseUrl;
                    window.location.href = fullUrl;
                });
            }

            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const baseUrl = '{{ route('item-return.export.pdf') }}';
                    const filterParams = getFilterParams();
                    const fullUrl = filterParams ? baseUrl + '?' + filterParams : baseUrl;
                    window.location.href = fullUrl;
                });
            }
        }
    });
</script>
