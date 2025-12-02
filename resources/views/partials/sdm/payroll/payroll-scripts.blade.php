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
    const generateSubmitBtn = document.querySelector('#generateModal button[type="submit"]');

    let checkTimeout = null;

    async function checkAttendanceData() {
        const month = periodMonthSelect.value;
        const year = periodYearInput.value;

        // Reset tampilan
        allCompleteDiv.classList.add('hidden');
        incompleteWarningDiv.classList.add('hidden');
        alreadyGeneratedWarningDiv.classList.add('hidden');

        if (!month || !year) {
            // Disable button if no period selected
            if (generateSubmitBtn) {
                generateSubmitBtn.disabled = true;
                generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
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

            // Default: enable button
            let canGenerate = true;
            let disableReason = '';

            // Check 1: If there are incomplete employees - CANNOT GENERATE
            if (data.incomplete_employees.length > 0) {
                canGenerate = false;
                disableReason = 'Data absensi belum lengkap';
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
            }

            // Check 2: If already generated AND no new employees - CANNOT GENERATE
            if (data.already_generated.length > 0 && !data.has_new_employees) {
                canGenerate = false;
                disableReason = 'Payroll sudah digenerate untuk semua karyawan';
                alreadyGeneratedWarningDiv.classList.remove('hidden');
                alreadyGeneratedList.innerHTML = '<ul class="list-disc list-inside space-y-1">' +
                    data.already_generated.map(emp =>
                        `<li class="text-sm">${emp.name} <span class="text-xs text-yellow-600">(${emp.employee_code})</span></li>`
                    ).join('') + '</ul>';

                // Add additional message
                const noteDiv = document.createElement('div');
                noteDiv.className = 'mt-3 p-2 bg-white rounded border border-yellow-300';
                noteDiv.innerHTML =
                    '<p class="text-xs text-yellow-700"><strong>Catatan:</strong> Semua karyawan sudah memiliki payroll untuk periode ini. Tidak dapat melakukan generate ulang.</p>';
                alreadyGeneratedList.appendChild(noteDiv);
            }
            // If already generated BUT there are new employees - CAN GENERATE (for new employees only)
            else if (data.already_generated.length > 0 && data.has_new_employees) {
                // Show info about already generated, but allow generation for new employees
                alreadyGeneratedWarningDiv.classList.remove('hidden');
                alreadyGeneratedList.innerHTML = '<ul class="list-disc list-inside space-y-1">' +
                    data.already_generated.map(emp =>
                        `<li class="text-sm">${emp.name} <span class="text-xs text-yellow-600">(${emp.employee_code})</span></li>`
                    ).join('') + '</ul>';

                // Add info message
                const noteDiv = document.createElement('div');
                noteDiv.className = 'mt-3 p-2 bg-green-50 rounded border border-green-300';
                noteDiv.innerHTML =
                    '<p class="text-xs text-green-700"><strong>Info:</strong> Ada karyawan baru yang belum memiliki payroll. Anda dapat melanjutkan generate untuk karyawan baru tersebut.</p>';
                alreadyGeneratedList.appendChild(noteDiv);
            }

            // Check 3: If all complete and no issues - CAN GENERATE
            if (data.incomplete_employees.length === 0 && (data.already_generated.length === 0 || data
                    .has_new_employees)) {
                allCompleteDiv.classList.remove('hidden');
            }

            // Update button state
            if (generateSubmitBtn) {
                if (canGenerate) {
                    generateSubmitBtn.disabled = false;
                    generateSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    generateSubmitBtn.classList.add('hover:bg-green-700');
                } else {
                    generateSubmitBtn.disabled = true;
                    generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    generateSubmitBtn.classList.remove('hover:bg-green-700');
                    generateSubmitBtn.title = disableReason;
                }
            }

        } catch (error) {
            console.error('Error:', error);
            checkingLoader.classList.add('hidden');
            // Disable button on error
            if (generateSubmitBtn) {
                generateSubmitBtn.disabled = true;
                generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
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

    // Initialize button state to disabled on page load
    if (generateSubmitBtn) {
        generateSubmitBtn.disabled = true;
        generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        generateSubmitBtn.classList.remove('hover:bg-green-700');
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

            // Reset button state
            if (generateSubmitBtn) {
                generateSubmitBtn.disabled = true;
                generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                generateSubmitBtn.classList.remove('hover:bg-green-700');
                generateSubmitBtn.title = '';
            }
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
    // PRINT DROPDOWN FUNCTIONALITY
    // ==========================================

    const printDropdownButton = document.getElementById('printDropdownButton');
    const printDropdownMenu = document.getElementById('printDropdownMenu');

    if (printDropdownButton && printDropdownMenu) {
        printDropdownButton.addEventListener('click', function(e) {
            e.stopPropagation();
            printDropdownMenu.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                printDropdownMenu.classList.add('hidden');
            }
        });
    }

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
