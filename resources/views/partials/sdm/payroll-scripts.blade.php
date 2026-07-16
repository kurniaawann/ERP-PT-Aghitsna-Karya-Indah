{{--
    Payroll Scripts (JavaScript)

    Frontend logic for payroll management.

    Modules:
    1. Attendance Auto-Check
       - Triggers when user changes month/year/week in generate modal
       - Calls PayrollController@checkAttendanceCompleteness via AJAX
       - Displays validation results (complete/incomplete/already generated)
       - Enables/disables Generate button based on can_generate flag

    2. Select All Checkbox
       - Handles "select all" checkbox in table header
       - Updates individual checkbox states
       - Toggles bulk action buttons (Delete, Pay)

    3. Bulk Operations
       - submitDeleteForm(): Collects selected IDs, submits DELETE form
       - submitBulkPayForm(): Collects selected IDs + payment date, submits PATCH form
       - Shows loading state during submission

    4. Form Validation
       - validatePayrollEditNotes(): Ensures notes are provided when expenses > 0
       - Used by edit modal forms

    5. Dynamic Expense Items
       - addExpenseItem(): Adds new expense item row to generate modal
       - removeExpenseItem(): Removes expense item row
       - updateExpenseData(): Recalculates totals and updates hidden inputs
       - Expense items stored as JSON in additional_expenses_notes

    Dependencies:
    - resources/js/shared/form-submit.js (handleFormSubmit helper)
    - x-modal component (openModal/closeModal functions)
    - modalClosed event listener for cleanup
--}}

<script>
    // Shared helper is loaded from resources/js/shared/form-submit.js

    // ==========================================
    // DYNAMIC WEEK LOADING (Monday-Saturday weeks)
    // ==========================================

    const GET_WEEKS_URL = '{{ route("payroll.get-weeks") }}';

    /**
     * Fetch week options from the server for a given month/year.
     * Returns array of { week_number, label, start, end }.
     */
    async function fetchWeeks(month, year) {
        if (!month || !year || month < 1 || month > 12 || year < 2000) {
            return [];
        }

        try {
            const response = await fetch(`${GET_WEEKS_URL}?month=${month}&year=${year}`);
            const data = await response.json();
            return data.weeks || [];
        } catch (error) {
            console.error('Error fetching weeks:', error);
            return [];
        }
    }

    /**
     * Populate a <select> element with week options.
     * Preserves the current selection if it still exists in the new options.
     */
    function populateWeekDropdown(selectEl, weeks, selectedValue) {
        selectEl.innerHTML = '<option value="">Pilih</option>';

        weeks.forEach(function(week) {
            const option = document.createElement('option');
            option.value = week.week_number;
            option.textContent = week.label;

            if (selectedValue && parseInt(selectedValue) === week.week_number) {
                option.selected = true;
            }

            selectEl.appendChild(option);
        });

        // If previous selection no longer exists, clear it
        if (selectedValue && !weeks.some(w => w.week_number === parseInt(selectedValue))) {
            selectEl.value = '';
        }
    }

    // ==========================================
    // AUTO-CHECK ATTENDANCE SAAT PILIH BULAN/TAHUN/MINGGU
    // ==========================================

    const periodMonthSelect = document.getElementById('period_month');
    const periodYearInput = document.getElementById('period_year');
    const weekNumberSelect = document.getElementById('week_number');
    const periodStartDateInput = document.getElementById('period_start_date');
    const periodEndDateInput = document.getElementById('period_end_date');
    const checkingLoader = document.getElementById('checking-loader');
    const allCompleteDiv = document.getElementById('all-complete');
    const incompleteWarningDiv = document.getElementById('incomplete-warning');
    const completeInfoDiv = document.getElementById('complete-info');
    const alreadyGeneratedWarningDiv = document.getElementById('already-generated-warning');
    const incompleteList = document.getElementById('incomplete-list');
    const completeList = document.getElementById('complete-list');
    const alreadyGeneratedList = document.getElementById('already-generated-list');
    const generateSubmitBtn = document.querySelector('#generateModal button[type="submit"]');

    let checkTimeout = null;
    let cachedWeeksData = [];

    /**
     * Load week options for the generate modal based on selected month/year.
     */
    async function loadGenerateWeeks() {
        const month = periodMonthSelect.value;
        const year = periodYearInput.value;

        if (!month || !year) {
            weekNumberSelect.innerHTML = '<option value="">Pilih bulan & tahun terlebih dahulu</option>';
            cachedWeeksData = [];
            periodStartDateInput.value = '';
            periodEndDateInput.value = '';
            return;
        }

        const currentWeek = weekNumberSelect.value;
        const weeks = await fetchWeeks(month, year);
        cachedWeeksData = weeks;
        populateWeekDropdown(weekNumberSelect, weeks, currentWeek);
        updatePeriodDateInputs();
    }

    /**
     * Populate hidden inputs period_start_date / period_end_date based on selected week.
     */
    function updatePeriodDateInputs() {
        const selectedWeekNum = parseInt(weekNumberSelect.value);
        if (!selectedWeekNum) {
            periodStartDateInput.value = '';
            periodEndDateInput.value = '';
            return;
        }

        const selectedWeek = cachedWeeksData.find(w => w.week_number === selectedWeekNum);
        if (selectedWeek) {
            periodStartDateInput.value = selectedWeek.start_date;
            periodEndDateInput.value = selectedWeek.end_date;
        } else {
            periodStartDateInput.value = '';
            periodEndDateInput.value = '';
        }
    }

    async function checkAttendanceData() {
        const month = periodMonthSelect.value;
        const year = periodYearInput.value;
        const weekNumber = weekNumberSelect.value;

        // Reset tampilan
        allCompleteDiv.classList.add('hidden');
        incompleteWarningDiv.classList.add('hidden');
        completeInfoDiv.classList.add('hidden');
        alreadyGeneratedWarningDiv.classList.add('hidden');

        if (!month || !year || !weekNumber) {
            if (generateSubmitBtn) {
                generateSubmitBtn.disabled = true;
                generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            periodStartDateInput.value = '';
            periodEndDateInput.value = '';
            return;
        }

        // Update hidden date inputs before checking
        updatePeriodDateInputs();

        const startDate = periodStartDateInput.value;
        const endDate = periodEndDateInput.value;

        if (!startDate || !endDate) {
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
                    period_start_date: startDate,
                    period_end_date: endDate
                })
            });

            const data = await response.json();

            // Hide loader
            checkingLoader.classList.add('hidden');

            const monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];

            // Gunakan can_generate dari backend
            let canGenerate = data.can_generate;
            let disableReason = '';

            // Tampilkan informasi periode
            const periodInfo =
                `Periode: <strong>${data.period_start} - ${data.period_end}</strong> (${data.working_days} hari kerja)`;

            // Tampilkan karyawan dengan data lengkap (jika ada)
            if (data.complete_employees && data.complete_employees.length > 0) {
                completeInfoDiv.classList.remove('hidden');

                // Tambahkan info periode di bagian atas
                let completeHTML =
                    `<p class="text-xs text-success mb-2 pb-2 border-b border-border-light">${periodInfo}</p>`;

                completeHTML += data.complete_employees.map(emp => {
                    return `
                        <div class="flex items-center justify-between text-sm bg-surface-base p-2 rounded border border-success">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-success"></i>
                                <span class="font-medium text-text-heading">${emp.name}</span>
                                <span class="text-xs text-text-label">(${emp.employee_code})</span>
                            </div>
                            <span class="text-xs text-success font-semibold">${emp.filled_days}/${emp.total_days} hari</span>
                        </div>
                    `;
                }).join('');

                completeList.innerHTML = completeHTML;
            }

            // Check 1: If there are incomplete employees - CANNOT GENERATE
            if (data.incomplete_employees.length > 0) {
                disableReason = 'Data absensi belum lengkap';
                incompleteWarningDiv.classList.remove('hidden');

                // Tambahkan info periode di bagian atas
                let incompleteHTML =
                    `<p class="text-xs text-error mb-2 pb-2 border-b border-error font-semibold">${periodInfo}</p>`;

                incompleteHTML += data.incomplete_employees.map(emp => {
                    // Format tanggal yang kosong
                    const missingDatesFormatted = emp.missing_dates.map(date => {
                        const d = new Date(date);
                        return d.getDate();
                    }).join(', ');

                    return `
                        <div class="bg-surface-base p-2 rounded border border-error">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-semibold text-text-heading text-sm">${emp.name}</span>
                                <span class="text-xs bg-error-light text-error px-2 py-1 rounded">${emp.employee_code}</span>
                            </div>
                            <div class="text-xs text-text-label space-y-1">
                                <div class="flex items-center gap-1">
                                    <i class="fa-solid fa-calendar-xmark text-error"></i>
                                    <span><strong class="text-error">${emp.filled_days}</strong> dari <strong>${emp.total_days}</strong> hari kerja</span>
                                </div>
                                ${emp.missing_dates.length > 0 ? `
                                <div class="flex items-start gap-1">
                                    <i class="fa-solid fa-ban text-error text-xs mt-0.5"></i>
                                    <span>Tanggal kosong: <strong class="text-error">${missingDatesFormatted}</strong> ${monthNames[month]}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                }).join('');

                incompleteList.innerHTML = incompleteHTML;
            }

            // Check 2: If already generated AND no new employees - CANNOT GENERATE
            if (data.already_generated.length > 0 && !data.has_new_employees) {
                disableReason = 'Payroll sudah digenerate untuk semua karyawan';
                alreadyGeneratedWarningDiv.classList.remove('hidden');
                alreadyGeneratedList.innerHTML = '<ul class="list-disc list-inside space-y-1">' +
                    data.already_generated.map(emp =>
                        `<li class="text-sm">${emp.name} <span class="text-xs text-warning">(${emp.employee_code})</span></li>`
                    ).join('') + '</ul>';

                // Add additional message
                const noteDiv = document.createElement('div');
                noteDiv.className = 'mt-3 p-2 bg-warning-light rounded border border-border-strong';
                noteDiv.innerHTML =
                    '<p class="text-xs text-warning"><strong>Catatan:</strong> Semua karyawan sudah memiliki payroll untuk periode ini. Tidak dapat melakukan generate ulang.</p>';
                alreadyGeneratedList.appendChild(noteDiv);
            }
            // If already generated BUT there are new employees - Show info
            else if (data.already_generated.length > 0 && data.has_new_employees) {
                // Show info about already generated, but allow generation for new employees
                alreadyGeneratedWarningDiv.classList.remove('hidden');
                alreadyGeneratedList.innerHTML = '<ul class="list-disc list-inside space-y-1">' +
                    data.already_generated.map(emp =>
                        `<li class="text-sm">${emp.name} <span class="text-xs text-warning">(${emp.employee_code})</span></li>`
                    ).join('') + '</ul>';

                // Add info message
                const noteDiv = document.createElement('div');
                noteDiv.className = 'mt-3 p-2 bg-success-light rounded border border-border-strong';
                noteDiv.innerHTML =
                    '<p class="text-xs text-success"><strong>Info:</strong> Ada karyawan baru yang belum memiliki payroll. Anda dapat melanjutkan generate untuk karyawan baru tersebut.</p>';
                alreadyGeneratedList.appendChild(noteDiv);
            }

            // Check 3: Jika tidak ada karyawan yang perlu di-generate
            if (!data.has_new_employees && data.already_generated.length === 0) {
                disableReason = 'Tidak ada karyawan yang perlu di-generate untuk periode ini';
                incompleteWarningDiv.classList.remove('hidden');
                incompleteList.innerHTML = `
                    <p class="text-xs text-error mb-2 pb-2 border-b border-error font-semibold">${periodInfo}</p>
                    <div class="bg-surface-base p-3 rounded border border-error text-center">
                        <i class="fa-solid fa-users-slash text-error text-3xl mb-2"></i>
                        <p class="text-sm text-text-heading font-semibold">Tidak Ada Karyawan</p>
                        <p class="text-xs text-text-label mt-1">Tidak ada karyawan yang perlu di-generate payroll untuk periode ini.</p>
                    </div>
                `;
            }
            // Check 4: If can generate (all conditions met) - SHOW SUCCESS
            else if (canGenerate) {
                allCompleteDiv.classList.remove('hidden');
            }

            // Update button state
            if (generateSubmitBtn) {
                if (canGenerate) {
                    generateSubmitBtn.disabled = false;
                    generateSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    generateSubmitBtn.classList.add('hover:bg-success-hover');
                } else {
                    generateSubmitBtn.disabled = true;
                    generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    generateSubmitBtn.classList.remove('hover:bg-success-hover');
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

    // Load weeks when month or year changes in generate modal
    if (periodMonthSelect) {
        periodMonthSelect.addEventListener('change', function() {
            loadGenerateWeeks();
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 400);
        });
    }

    if (periodYearInput) {
        periodYearInput.addEventListener('input', function() {
            loadGenerateWeeks();
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 600);
        });
    }

    if (weekNumberSelect) {
        weekNumberSelect.addEventListener('change', function() {
            updatePeriodDateInputs();
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(checkAttendanceData, 300);
        });
    }

    // Initialize button state to disabled on page load
    if (generateSubmitBtn) {
        generateSubmitBtn.disabled = true;
        generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        generateSubmitBtn.classList.remove('hover:bg-success-hover');
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
            weekNumberSelect.innerHTML = '<option value="">Pilih bulan & tahun terlebih dahulu</option>';
            cachedWeeksData = [];
            periodStartDateInput.value = '';
            periodEndDateInput.value = '';

            // Reset button state
            if (generateSubmitBtn) {
                generateSubmitBtn.disabled = true;
                generateSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                generateSubmitBtn.classList.remove('hover:bg-success-hover');
                generateSubmitBtn.title = '';
            }
        }
    });

    // ==========================================
    // FILTER WEEK DROPDOWN (Index Page)
    // ==========================================

    const filterMonthSelect = document.querySelector('select[name="month"]');
    const filterYearInput = document.querySelector('input[name="year"]');
    const filterWeekSelect = document.getElementById('filter_week_number');
    const currentFilterWeek = '{{ request("week_number") }}';
    const currentFilterMonth = '{{ request("month") }}';
    const currentFilterYear = '{{ request("year") }}';

    async function loadFilterWeeks() {
        if (!filterMonthSelect || !filterYearInput || !filterWeekSelect) return;

        const month = filterMonthSelect.value;
        const year = filterYearInput.value;

        if (!month || !year) {
            filterWeekSelect.innerHTML = '<option value="">Semua Minggu</option>';
            return;
        }

        const weeks = await fetchWeeks(month, year);
        filterWeekSelect.innerHTML = '<option value="">Semua Minggu</option>';

        weeks.forEach(function(week) {
            const option = document.createElement('option');
            option.value = week.week_number;
            option.textContent = week.label;

            if (currentFilterWeek && parseInt(currentFilterWeek) === week.week_number &&
                currentFilterMonth == month && currentFilterYear == year) {
                option.selected = true;
            }

            filterWeekSelect.appendChild(option);
        });
    }

    if (filterMonthSelect) {
        filterMonthSelect.addEventListener('change', loadFilterWeeks);
    }
    if (filterYearInput) {
        filterYearInput.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(loadFilterWeeks, 500);
        });
    }

    // Load filter weeks on page load if month/year are set
    if (currentFilterMonth && currentFilterYear) {
        loadFilterWeeks();
    }

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
            bulkPayButton.classList.add('hover:bg-primary-hover');
        } else {
            // Disable Delete Button
            deleteButton.disabled = true;
            deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            deleteButton.classList.remove('hover:bg-btn-delete-hover');

            // Disable Bulk Pay Button
            bulkPayButton.disabled = true;
            bulkPayButton.classList.add('opacity-50', 'cursor-not-allowed');
            bulkPayButton.classList.remove('hover:bg-primary-hover');
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
            if (!handleFormSubmit(submitBtn, undefined, 'Memproses...')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Handle Edit Modal Submits
    document.querySelectorAll('[id^="editModal-"] form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const modal = this.closest('[id^="editModal-"]');
            const payrollId = modal ? modal.id.replace('editModal-', '') : null;

            if (payrollId && !validatePayrollEditNotes(payrollId)) {
                e.preventDefault();
                return false;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            if (!handleFormSubmit(submitBtn, undefined, 'Memproses...')) {
                e.preventDefault();
                return false;
            }
        });
    });

    function validatePayrollEditNotes(payrollId) {
        const amountInput = document.getElementById(`additional_expenses_${payrollId}`);
        const notesInput = document.getElementById(`additional_expenses_notes_${payrollId}`);

        if (!amountInput || !notesInput) {
            return true;
        }

        const amount = parseInt(amountInput.value, 10) || 0;
        const notes = notesInput.value.trim();

        if (amount > 0 && !notes) {
            notesInput.setCustomValidity('Keterangan pengeluaran tambahan wajib diisi jika nominal lebih dari 0.');
            notesInput.reportValidity();
            return false;
        }

        notesInput.setCustomValidity('');
        return true;
    }

    window.validatePayrollEditNotes = validatePayrollEditNotes;

    // ==========================================
    // DYNAMIC EXPENSE ITEMS
    // ==========================================

    let expenseItemCounter = 0;
    const expenseItems = [];

    function addExpenseItem() {
        expenseItemCounter++;
        const itemId = expenseItemCounter;

        const container = document.getElementById('expense-items-container');
        const noExpenseText = document.getElementById('no-expense-text');

        if (noExpenseText) {
            noExpenseText.style.display = 'none';
        }

        const itemHTML = `
            <div class="expense-item border border-border-strong rounded-lg p-3 bg-surface-base" data-item-id="${itemId}">
                <div class="flex gap-2 items-start">
                    <div class="flex-1 grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-text-secondary mb-1">Nama Pengeluaran</label>
                            <input type="text" 
                                class="expense-name w-full border border-border-strong rounded-lg px-2 py-1.5 text-sm bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="Contoh: Token Listrik"
                                oninput="updateExpenseData()"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs text-text-secondary mb-1">Jumlah (Rp)</label>
                            <input type="number" 
                                class="expense-amount w-full border border-border-strong rounded-lg px-2 py-1.5 text-sm bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="0"
                                min="0"
                                oninput="updateExpenseData()"
                                required>
                        </div>
                    </div>
                    <button type="button" 
                        onclick="removeExpenseItem(${itemId})"
                        class="mt-6 text-error hover:text-error hover:bg-error-light px-2 py-1.5 rounded transition-colors"
                        title="Hapus item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', itemHTML);
        updateExpenseData();
    }

    function removeExpenseItem(itemId) {
        const item = document.querySelector(`[data-item-id="${itemId}"]`);
        if (item) {
            item.remove();
            updateExpenseData();

            // Show "no expense" text if no items left
            const container = document.getElementById('expense-items-container');
            const items = container.querySelectorAll('.expense-item');
            const noExpenseText = document.getElementById('no-expense-text');

            if (items.length === 0 && noExpenseText) {
                noExpenseText.style.display = 'block';
            }
        }
    }

    function updateExpenseData() {
        const items = [];
        let total = 0;

        document.querySelectorAll('.expense-item').forEach(item => {
            const name = item.querySelector('.expense-name').value.trim();
            const amount = parseInt(item.querySelector('.expense-amount').value) || 0;

            if (name && amount > 0) {
                items.push({
                    name,
                    amount
                });
                total += amount;
            }
        });

        // Update hidden inputs
        document.getElementById('total_additional_expenses').value = total;
        document.getElementById('additional_expenses_notes').value = JSON.stringify(items);

        // Update display
        document.getElementById('total-expense-display').textContent =
            'Rp ' + total.toLocaleString('id-ID');
    }

    // Initialize: hide no-expense text if there are items
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('expense-items-container');
        const items = container.querySelectorAll('.expense-item');
        const noExpenseText = document.getElementById('no-expense-text');

        if (items.length > 0 && noExpenseText) {
            noExpenseText.style.display = 'none';
        }
    });
</script>
