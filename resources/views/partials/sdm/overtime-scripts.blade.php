<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    function parseCurrencyInput(value) {
        const rawValue = String(value ?? '').trim();

        if (!rawValue) {
            return 0;
        }

        return parseInt(rawValue.replace(/[^0-9-]/g, ''), 10) || 0;
    }

    function formatRupiah(value) {
        return 'Rp ' + (Number(value) || 0).toLocaleString('id-ID');
    }

    // ==========================================
    // EMPLOYEE SEARCH & PAGINATION DROPDOWN
    // ==========================================

    let employeePage = 1;
    let employeeSearch = '';
    let employeeHasMore = false;
    let debounceTimer = null;

    function debounceFetchEmployees(query) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            employeeSearch = query;
            employeePage = 1;
            fetchEmployees();
        }, 300);
    }

    function openEmployeeDropdown() {
        const dropdown = document.getElementById('add-employee-dropdown');
        if (dropdown) {
            dropdown.classList.remove('hidden');
            if (!dropdown.dataset.loaded) {
                employeePage = 1;
                fetchEmployees();
            }
        }
    }

    function closeEmployeeDropdown() {
        const dropdown = document.getElementById('add-employee-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }

    function fetchEmployees() {
        const url = '{{ route('overtime.employees-dropdown') }}?search=' + encodeURIComponent(employeeSearch) +
            '&page=' + employeePage + '&limit=10';

        fetch(url)
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById('add-employee-list');
                const prevBtn = document.getElementById('add-employee-prev');
                const nextBtn = document.getElementById('add-employee-next');
                const pageInfo = document.getElementById('add-employee-page-info');

                if (!list) return;

                if (employeePage === 1) {
                    list.innerHTML = '';
                }

                if (data.data.length === 0) {
                    list.innerHTML = '<div class="p-3 text-sm text-text-secondary text-center">Karyawan tidak ditemukan</div>';
                } else {
                    data.data.forEach(emp => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-surface-hover cursor-pointer border-b border-border-strong text-sm employee-option';
                        div.dataset.id = emp.id;
                        div.dataset.name = emp.name;
                        div.textContent = emp.text;
                        div.onclick = function() { selectEmployee(emp.id, emp.name); };
                        list.appendChild(div);
                    });
                }

                employeeHasMore = data.hasMore;

                if (prevBtn) prevBtn.disabled = employeePage <= 1;
                if (nextBtn) nextBtn.disabled = !employeeHasMore;
                if (pageInfo) pageInfo.textContent = 'Halaman ' + employeePage;

                document.getElementById('add-employee-dropdown').dataset.loaded = '1';
            })
            .catch(err => console.error('Error fetching employees:', err));
    }

    function changeEmployeePage(direction) {
        employeePage += direction;
        const list = document.getElementById('add-employee-list');
        if (list) list.innerHTML = '';
        fetchEmployees();
    }

    function selectEmployee(id, name) {
        document.getElementById('add-selected-employee-id').value = id;
        document.getElementById('add-employee-search').value = name;
        document.getElementById('add-employee-selected-name').textContent = 'Dipilih: ' + name;
        closeEmployeeDropdown();
        validateAddOvertime();
    }

    // Close dropdown on outside click
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('add-employee-dropdown');
        const search = document.getElementById('add-employee-search');
        if (dropdown && search && !search.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    // ==========================================
    // VALIDASI DUPLIKAT OVERTIME (CLIENT-SIDE)
    // ==========================================

    const existingAttendance = @json($existingAttendance ?? []);

    const addDateInput = document.getElementById('add-attendance-date');
    const addDuplicateWarning = document.getElementById('add-duplicate-warning');
    const addDuplicateWarningText = document.getElementById('add-duplicate-warning-text');
    const addSubmitBtn = document.querySelector('#addModal button[type="submit"]');

    function validateAddOvertime() {
        const employeeId = document.getElementById('add-selected-employee-id').value;
        const date = addDateInput ? addDateInput.value : '';

        if (!employeeId || !date) {
            addDuplicateWarning.classList.add('hidden');
            if (addSubmitBtn) {
                addSubmitBtn.disabled = false;
                addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }

        if (existingAttendance[employeeId] && existingAttendance[employeeId][date]) {
            const existing = existingAttendance[employeeId][date];
            const employeeName = document.getElementById('add-employee-search').value;
            const formattedDate = new Date(date).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            if (existing.status === 'lembur') {
                addDuplicateWarningText.textContent =
                    `Karyawan ${employeeName} sudah memiliki data lembur pada tanggal ${formattedDate}. Silakan pilih tanggal lain atau edit data yang sudah ada.`;
                addDuplicateWarning.classList.remove('hidden');
                if (addSubmitBtn) {
                    addSubmitBtn.disabled = true;
                    addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            } else if (existing.status === 'izin' || existing.status === 'sakit' || existing.status === 'cuti') {
                addDuplicateWarningText.textContent =
                    `Karyawan ${employeeName} memiliki status ${existing.status.toUpperCase()} pada tanggal ${formattedDate}. Lembur hanya bisa ditambahkan untuk karyawan yang hadir.`;
                addDuplicateWarning.classList.remove('hidden');
                if (addSubmitBtn) {
                    addSubmitBtn.disabled = true;
                    addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            } else {
                addDuplicateWarning.classList.add('hidden');
                if (addSubmitBtn) {
                    addSubmitBtn.disabled = false;
                    addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                return true;
            }
        } else {
            addDuplicateWarning.classList.add('hidden');
            if (addSubmitBtn) {
                addSubmitBtn.disabled = false;
                addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }
    }

    if (addDateInput) {
        addDateInput.addEventListener('change', validateAddOvertime);
    }

    // Validasi untuk Edit Modals
    document.querySelectorAll('[id^="edit-attendance-date-"]').forEach(dateInput => {
        const overtimeId = dateInput.dataset.overtimeId;
        const originalDate = dateInput.dataset.originalDate;
        const employeeId = dateInput.closest('form').querySelector('input[name="employee_id"]').value;
        const duplicateWarning = document.getElementById('edit-duplicate-warning-' + overtimeId);
        const duplicateWarningText = document.getElementById('edit-duplicate-warning-text-' + overtimeId);
        const submitBtn = document.querySelector('#editModal-' + overtimeId + ' button[type="submit"]');

        function validateEditOvertime() {
            const date = dateInput.value;

            if (!date) {
                duplicateWarning.classList.add('hidden');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                return true;
            }

            // Jika tanggal tidak berubah, tidak perlu validasi
            if (date === originalDate) {
                duplicateWarning.classList.add('hidden');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                return true;
            }

            // Cek apakah kombinasi employee + date sudah ada (exclude record ini)
            if (existingAttendance[employeeId] && existingAttendance[employeeId][date]) {
                const existing = existingAttendance[employeeId][date];

                // Jika ID sama, berarti ini record yang sama (skip validasi)
                if (existing.id != overtimeId) {
                    const employeeName = dateInput.closest('form').querySelector('input[type="text"]').value;
                    const formattedDate = new Date(date).toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });

                    // Cegah jika status adalah "lembur" (duplikat)
                    if (existing.status === 'lembur') {
                        duplicateWarningText.textContent =
                            `Karyawan ${employeeName} sudah memiliki data lembur pada tanggal ${formattedDate}. Silakan pilih tanggal lain atau hapus data yang sudah ada.`;
                        duplicateWarning.classList.remove('hidden');

                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                        return false;
                    }
                    // Cegah jika status adalah izin, sakit, atau cuti
                    else if (existing.status === 'izin' || existing.status === 'sakit' || existing.status ===
                        'cuti') {
                        duplicateWarningText.textContent =
                            `Karyawan ${employeeName} memiliki status ${existing.status.toUpperCase()} pada tanggal ${formattedDate}. Lembur hanya bisa ditambahkan untuk karyawan yang hadir.`;
                        duplicateWarning.classList.remove('hidden');

                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                        return false;
                    }
                }
            }

            duplicateWarning.classList.add('hidden');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }

        dateInput.addEventListener('change', validateEditOvertime);
    });

    // ==========================================
    // SELECT ALL CHECKBOX
    // ==========================================

    // Select All Checkbox
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButtonState();
    });

    // Individual Checkbox
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="ids[]"]');
            const checkedCheckboxes = document.querySelectorAll('input[name="ids[]"]:checked');

            selectAll.checked = checkboxes.length === checkedCheckboxes.length;
            updateDeleteButtonState();
        });
    });

    // ==========================================
    // CALCULATE OVERTIME TOTAL
    // ==========================================

    // Calculate Overtime Total in Add Modal
    const addHoursInput = document.getElementById('add-overtime-hours');
    const addRateInput = document.getElementById('add-overtime-rate');
    const addTotalInput = document.getElementById('add-overtime-total');

    function calculateAddOvertimeTotal() {
        const hours = parseFloat(addHoursInput.value) || 0;
        const rate = parseCurrencyInput(addRateInput.value);
        const total = hours * rate;
        addTotalInput.value = formatRupiah(total);
    }

    if (addHoursInput && addRateInput) {
        addHoursInput.addEventListener('input', calculateAddOvertimeTotal);
        addRateInput.addEventListener('input', function() {
            this.value = formatRupiah(parseCurrencyInput(this.value));
            calculateAddOvertimeTotal();
        });
    }

    // Calculate Overtime Total in Edit Modals
    document.querySelectorAll('[id^="edit-overtime-hours-"]').forEach(hoursInput => {
        const id = hoursInput.id.replace('edit-overtime-hours-', '');
        const rateInput = document.getElementById('edit-overtime-rate-' + id);
        const totalInput = document.getElementById('edit-overtime-total-' + id);

        function calculateEditOvertimeTotal() {
            const hours = parseFloat(hoursInput.value) || 0;
            const rate = parseCurrencyInput(rateInput.value);
            const total = hours * rate;
            totalInput.value = formatRupiah(total);
        }

        hoursInput.addEventListener('input', calculateEditOvertimeTotal);
        if (rateInput) {
            rateInput.addEventListener('input', function() {
                this.value = formatRupiah(parseCurrencyInput(this.value));
                calculateEditOvertimeTotal();
            });
        }
    });

    // ==========================================
    // DELETE BUTTON STATE
    // ==========================================

    // Update Delete Button State
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

    // Submit Delete Form
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

    // Handle Add Modal Submit
    const addOvertimeForm = document.querySelector('#addModal form');
    if (addOvertimeForm) {
        addOvertimeForm.addEventListener('submit', function(e) {
            // Validasi sudah dilakukan, jika lolos tambahkan loading
            if (!validateAddOvertime()) {
                e.preventDefault();
                return false;
            }

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
