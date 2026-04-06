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
        // AUTO-UPDATE TOTAL HARGA
        // ==========================================

        function updateTotalPrice(modal) {
            const qtyInput = modal.querySelector('input[name="quantity"]');
            const priceInput = modal.querySelector('input[name="capital_price"]');

            if (qtyInput && priceInput) {
                const qty = parseInt(qtyInput.value) || 0;
                const price = parseInt(priceInput.value) || 0;
                const total = qty * price;

                let totalDisplay = modal.querySelector('.total-price-display');
                if (!totalDisplay) {
                    totalDisplay = document.createElement('div');
                    totalDisplay.className =
                        'total-price-display p-2 bg-blue-50 rounded border border-blue-200 text-sm font-semibold text-blue-800 mt-2';
                    priceInput.parentElement.parentElement.appendChild(totalDisplay);
                }

                totalDisplay.textContent = 'Total: Rp ' + new Intl.NumberFormat('id-ID').format(total);
            }
        }

        // Handle add modal
        const addModal = document.getElementById('addModal');
        if (addModal) {
            const addQty = addModal.querySelector('input[name="quantity"]');
            const addPrice = addModal.querySelector('input[name="capital_price"]');

            if (addQty && addPrice) {
                addQty.addEventListener('input', () => updateTotalPrice(addModal));
                addPrice.addEventListener('input', () => updateTotalPrice(addModal));
            }
        }

        // Handle edit modals
        const editModals = document.querySelectorAll('[id^="editModal-"]');
        editModals.forEach(modal => {
            const editQty = modal.querySelector('input[name="quantity"]');
            const editPrice = modal.querySelector('input[name="capital_price"]');

            if (editQty && editPrice) {
                editQty.addEventListener('input', () => updateTotalPrice(modal));
                editPrice.addEventListener('input', () => updateTotalPrice(modal));
            }
        });

        // ==========================================
        // BARANG DROPDOWN CHANGE EVENT
        // ==========================================

        const barangSelects = document.querySelectorAll('select[name="id_item"]');
        barangSelects.forEach(select => {
            select.addEventListener('change', function() {
                // Optional: Show stock info or update display
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.text.includes('Stock:')) {
                    console.log('Selected:', selectedOption.text);
                }
            });
        });

        // ==========================================
        // CUSTOM DELETE FUNCTION
        // ==========================================

        window.deleteRecord = function(url) {
            if (confirm('Apakah Anda yakin ingin menghapus data ini? Qty akan dikompensasi otomatis.')) {
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

                return params.toString();
            }

            if (excelBtn) {
                excelBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const baseUrl = '{{ route('stock-in.export.excel') }}';
                    const filterParams = getFilterParams();
                    const fullUrl = filterParams ? baseUrl + '?' + filterParams : baseUrl;
                    window.location.href = fullUrl;
                });
            }

            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const baseUrl = '{{ route('stock-in.export.pdf') }}';
                    const filterParams = getFilterParams();
                    const fullUrl = filterParams ? baseUrl + '?' + filterParams : baseUrl;
                    window.location.href = fullUrl;
                });
            }
        }
    });
</script>
