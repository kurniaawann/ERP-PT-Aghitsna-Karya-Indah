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
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
            submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        return true;
    }

    // ==========================================
    // AUTO-CHECK ATTENDANCE SAAT PILIH BULAN/TAHUN
    // ==========================================

    const periodMonthSelect = document.getElementById('period_month');
    const periodYearInput = document.getElementById('period_year');
    const checkingLoader = document.getElementById('checking-loader');
    const allCompleteDiv = document.getElementById('all-complete');
    const incompleteWarningDiv = document.getElementById('incomplete-warning');
    const alreadyGeneratedWarningDiv = document.getElementById('already-generated-warning');
    const incompleteList = document.getElementById('incomplete-list');
    const alreadyGeneratedList = document.getElementById('already-generated-list');

    let checkTimeout = null;

    async function checkAttendanceData() {
        const month = periodMonthSelect.value;
        const year = periodYearInput.value;

        // Reset tampilan
        allCompleteDiv.classList.add('hidden');
        incompleteWarningDiv.classList.add('hidden');
        alreadyGeneratedWarningDiv.classList.add('hidden');

        if (!month || !year) {
            return;
        }

        // Show loader
        checkingLoader.classList.remove('hidden');

        try {
            const response = await fetch('{{ route('payroll.check-attendance') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    period_month: month,
                    period_year: year
                })
            });

            const data = await response.json();

            // Hide loader
            checkingLoader.classList.add('hidden');

            const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];

            // Show payroll yang sudah digenerate
            if (data.already_generated.length > 0) {
                alreadyGeneratedWarningDiv.classList.remove('hidden');
                alreadyGeneratedList.innerHTML = '<ul class="list-disc list-inside space-y-1">' +
                    data.already_generated.map(emp =>
                        `<li class="text-sm">${emp.name} <span class="text-xs text-yellow-600">(${emp.employee_code})</span></li>`
                    ).join('') + '</ul>';
            }

            // Show data incomplete
            if (data.incomplete_employees.length > 0) {
                incompleteWarningDiv.classList.remove('hidden');

                incompleteList.innerHTML = data.incomplete_employees.map(emp => {
                    // Format tanggal yang kosong
                    const missingDatesFormatted = emp.missing_dates.map(date => {
                        const d = new Date(date);
                        return d.getDate();
                    }).join(', ');

                    return `
                        <div class="bg-white p-3 rounded border border-red-200">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-semibold text-gray-800">${emp.name}</span>
                                <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded">${emp.employee_code}</span>
                            </div>
                            <div class="text-xs text-gray-600 space-y-1">
                                <div class="flex items-center gap-1">
                                    <i class="fa-solid fa-calendar-check text-gray-400"></i>
                                    <span>Data: <strong class="text-blue-600">${emp.filled_days}</strong> dari <strong>${emp.total_days}</strong> hari kerja</span>
                                </div>
                                <div class="flex items-start gap-1">
                                    <i class="fa-solid fa-calendar-xmark text-red-500 mt-0.5"></i>
                                    <span>Tanggal belum absen: <strong class="text-red-600">${missingDatesFormatted}</strong> ${monthNames[month]} ${year}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else if (data.already_generated.length === 0) {
                // Semua data lengkap dan belum digenerate
                allCompleteDiv.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            checkingLoader.classList.add('hidden');
        }
    }

    // Auto-check saat pilih bulan
    if (periodMonthSelect) {
        periodMonthSelect.addEventListener('change', function() {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 300);
        });
    }

    // Auto-check saat ubah tahun
    if (periodYearInput) {
        periodYearInput.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 500);
        });
    }

    // Reset saat modal ditutup
    window.addEventListener('modalClosed', function(e) {
        if (e.detail === 'generateModal') {
            allCompleteDiv.classList.add('hidden');
            incompleteWarningDiv.classList.add('hidden');
            alreadyGeneratedWarningDiv.classList.add('hidden');
            checkingLoader.classList.add('hidden');
            periodMonthSelect.value = '';
            periodYearInput.value = '{{ date('Y') }}';
        }
    });

    // ==========================================
    // SELECT ALL CHECKBOX
    // ==========================================

    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStates();
    });

    // Individual Checkbox
    document.querySelectorAll('input[name="ids[]"]:not(:disabled)').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled)');
            const checkedCheckboxes = document.querySelectorAll(
                'input[name="ids[]"]:not(:disabled):checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateButtonStates();
        });
    });

    // Update Button States (Delete & Bulk Pay)
    function updateButtonStates() {
        const deleteButton = document.getElementById('delete-button');
        const bulkPayButton = document.getElementById('bulk-pay-button');
        const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:not(:disabled):checked');

        if (checkedCheckboxes.length > 0) {
            // Enable Delete Button
            deleteButton.disabled = false;
            deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.add('hover:bg-btn-delete-hover');

            // Enable Bulk Pay Button
            bulkPayButton.disabled = false;
            bulkPayButton.classList.remove('opacity-50', 'cursor-not-allowed');
            bulkPayButton.classList.add('hover:bg-blue-700');
        } else {
            // Disable Delete Button
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');

            // Disable Bulk Pay Button
            bulkPayButton.disabled = true;
            bulkPayButton.classList.add('opacity-50', 'cursor-not-allowed');
            bulkPayButton.classList.remove('hover:bg-blue-700');
        }
    }

    // Submit Delete Form
    function submitDeleteForm() {
        const checkedCheckboxes = document.querySelectorAll('.payroll-checkbox:checked');
        const deleteForm = document.getElementById('deleteForm');

        if (checkedCheckboxes.length === 0) {
            return; // Don't submit if nothing is selected
        }

        // Remove previous inputs
        const existingIds = deleteForm.querySelectorAll('input[name="ids[]"]');
        existingIds.forEach(input => input.remove());

        // Add checked IDs to delete form
        checkedCheckboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = checkbox.value;
            deleteForm.appendChild(input);
        });

        // Add loading state to delete button
        const deleteBtn = document.getElementById('confirm-btn-deleteModal');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        // Submit form
        deleteForm.submit();
    }

    // Submit Bulk Pay Form
    function submitBulkPayForm() {
        const checkedCheckboxes = document.querySelectorAll('.payroll-checkbox:checked');
        const bulkPayForm = document.getElementById('bulkPayForm');

        if (checkedCheckboxes.length === 0) {
            return; // Don't submit if nothing is selected
        }

        // Remove previous dynamic inputs
        const existingIds = bulkPayForm.querySelectorAll('input[name="ids[]"]');
        existingIds.forEach(input => input.remove());
        const existingDate = bulkPayForm.querySelector('input[name="payment_date"]');
        if (existingDate) existingDate.remove();

        // Add checked IDs to bulk pay form
        checkedCheckboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = checkbox.value;
            bulkPayForm.appendChild(input);
        });

        // Add payment date (today)
        const dateInput = document.createElement('input');
        dateInput.type = 'hidden';
        dateInput.name = 'payment_date';
        dateInput.value = new Date().toISOString().split('T')[0];
        bulkPayForm.appendChild(dateInput);

        // Add loading state to bulk pay button
        const bulkPayBtn = document.getElementById('confirm-btn-bulkPayModal');
        if (bulkPayBtn) {
            bulkPayBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';
            bulkPayBtn.disabled = true;
            bulkPayBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        // Submit form
        bulkPayForm.submit();
    }

    // Initialize button states on page load
    updateButtonStates();

    // ==========================================
    // GENERATE PAYROLL FORM SUBMIT HANDLER
    // ==========================================

    // Handle Generate Modal Submit
    const generateForm = document.querySelector('#generateModal form');
    if (generateForm) {
        generateForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Handle Edit Modal Submits
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
