<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

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
        // STOCK VALIDATION ON QUANTITY CHANGE
        // ==========================================

        const barangSelects = document.querySelectorAll('select[name="id_item"]');
        barangSelects.forEach(select => {
            select.addEventListener('change', function() {
                const selectedText = this.options[this.selectedIndex].text;
                const qtyInput = this.closest('form')?.querySelector('input[name="quantity"]');

                // Extract stock info from dropdown text
                // Format: "ID - Name (Stock: X)"
                const stockMatch = selectedText.match(/\(Stock:\s*(\d+)\)/);
                if (stockMatch && qtyInput) {
                    const availableStock = parseInt(stockMatch[1]);
                    qtyInput.max = availableStock;
                    qtyInput.title = `Stok tersedia: ${availableStock}`;

                    // Show warning if stock is low
                    if (availableStock === 0) {
                        alert('⚠️ Stok barang ini tidak tersedia!');
                        this.value = '';
                    }
                }
            });
        });

        // ==========================================
        // VALIDATE QUANTITY DOESN'T EXCEED STOCK
        // ==========================================

        const qtyInputs = document.querySelectorAll('input[name="quantity"]');
        qtyInputs.forEach(input => {
            input.addEventListener('input', function() {
                const max = this.max ? parseInt(this.max) : Infinity;
                if (parseInt(this.value) > max) {
                    alert(`⚠️ Jumlah tidak boleh melebihi stok tersedia (${max})`);
                    this.value = max;
                }
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
            if (confirm('Apakah Anda yakin ingin menghapus data ini? Stock akan dikembalikan otomatis.')) {
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
                    const baseUrl = '{{ route('stock-out.export.excel') }}';
                    const filterParams = getFilterParams();
                    const fullUrl = filterParams ? baseUrl + '?' + filterParams : baseUrl;
                    handleDownload(fullUrl, this, 'Downloading...');
                });
            }

            if (pdfBtn) {
                pdfBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const baseUrl = '{{ route('stock-out.export.pdf') }}';
                    const filterParams = getFilterParams();
                    const fullUrl = filterParams ? baseUrl + '?' + filterParams : baseUrl;
                    handleDownload(fullUrl, this, 'Downloading...');
                });
            }
        }
    });
</script>
