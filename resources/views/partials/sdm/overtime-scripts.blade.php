<script>
    // ==========================================
    // PREVENT DOUBLE SUBMIT & LOADING STATE
    // ==========================================

    // Shared helper is loaded from resources/js/shared/form-submit.js

    // ==========================================
    // VALIDASI DUPLIKAT OVERTIME (CLIENT-SIDE)
    // ==========================================

    const existingAttendance = @json($existingAttendance ?? []);

    // Validasi untuk Add Modal
    const addEmployeeSelect = document.getElementById('add-employee-id');
    const addDateInput = document.getElementById('add-attendance-date');
    const addDuplicateWarning = document.getElementById('add-duplicate-warning');
    const addDuplicateWarningText = document.getElementById('add-duplicate-warning-text');
    const addSubmitBtn = document.querySelector('#addModal button[type="submit"]');

    function validateAddOvertime() {
        if (!addEmployeeSelect || !addDateInput) return true;

        const employeeId = addEmployeeSelect.value;
        const date = addDateInput.value;

        if (!employeeId || !date) {
            addDuplicateWarning.classList.add('hidden');
            if (addSubmitBtn) {
                addSubmitBtn.disabled = false;
                addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }

        // Cek apakah kombinasi employee + date sudah ada
        // Karyawan hanya boleh lembur jika mereka hadir (status: hadir)
        if (existingAttendance[employeeId] && existingAttendance[employeeId][date]) {
            const existing = existingAttendance[employeeId][date];
            const employeeName = addEmployeeSelect.options[addEmployeeSelect.selectedIndex].text.split(' - ')[0];
            const formattedDate = new Date(date).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            // Cegah jika sudah ada data lembur (duplikat)
            if (existing.status === 'lembur') {
                addDuplicateWarningText.textContent =
                    `Karyawan ${employeeName} sudah memiliki data lembur pada tanggal ${formattedDate}. Silakan pilih tanggal lain atau edit data yang sudah ada.`;
                addDuplicateWarning.classList.remove('hidden');

                if (addSubmitBtn) {
                    addSubmitBtn.disabled = true;
                    addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            }
            // Cegah jika karyawan izin, sakit, atau cuti (tidak masuk akal ada lembur)
            else if (existing.status === 'izin' || existing.status === 'sakit' || existing.status === 'cuti') {
                addDuplicateWarningText.textContent =
                    `Karyawan ${employeeName} memiliki status ${existing.status.toUpperCase()} pada tanggal ${formattedDate}. Lembur hanya bisa ditambahkan untuk karyawan yang hadir.`;
                addDuplicateWarning.classList.remove('hidden');

                if (addSubmitBtn) {
                    addSubmitBtn.disabled = true;
                    addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                }
                return false;
            }
            // Izinkan jika status adalah "hadir" atau status lainnya
            else {
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

    if (addEmployeeSelect && addDateInput) {
        addEmployeeSelect.addEventListener('change', validateAddOvertime);
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
        const rate = parseInt(addRateInput.value) || 0;
        const total = hours * rate;
        addTotalInput.value = 'Rp ' + total.toLocaleString('id-ID');
    }

    if (addHoursInput && addRateInput) {
        addHoursInput.addEventListener('input', calculateAddOvertimeTotal);
        addRateInput.addEventListener('input', calculateAddOvertimeTotal);
    }

    // Calculate Overtime Total in Edit Modals
    document.querySelectorAll('[id^="edit-overtime-hours-"]').forEach(hoursInput => {
        const id = hoursInput.id.replace('edit-overtime-hours-', '');
        const rateInput = document.getElementById('edit-overtime-rate-' + id);
        const totalInput = document.getElementById('edit-overtime-total-' + id);

        function calculateEditOvertimeTotal() {
            const hours = parseFloat(hoursInput.value) || 0;
            const rate = parseInt(rateInput.value) || 0;
            const total = hours * rate;
            totalInput.value = 'Rp ' + total.toLocaleString('id-ID');
        }

        hoursInput.addEventListener('input', calculateEditOvertimeTotal);
        rateInput.addEventListener('input', calculateEditOvertimeTotal);
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
