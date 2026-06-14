<script>
    // Shared helper is loaded from resources/js/shared/form-submit.js

    @include('partials.shared.select-all-script')
    @include('partials.shared.delete-form-script')

    initSelectAll('ids[]');

    // Select All Employees in Add Modal
    const selectAllEmployees = document.getElementById('selectAllEmployees');
    if (selectAllEmployees) {
        selectAllEmployees.addEventListener('change', function() {
            const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
            employeeCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            validateDuplicateAttendance();
        });

        document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
                const checkedEmployees = document.querySelectorAll('.employee-checkbox:checked');
                selectAllEmployees.checked = employeeCheckboxes.length === checkedEmployees.length;
                validateDuplicateAttendance();
            });
        });
    }

    // ==========================================
    // VALIDASI DUPLIKAT ABSENSI (CLIENT-SIDE)
    // ==========================================

    const existingAttendance = @json($existingAttendance ?? []);
    const duplicateWarning = document.getElementById('duplicate-warning');
    const duplicateWarningText = document.getElementById('duplicate-warning-text');
    const addSubmitBtn = document.querySelector('#addModal button[type="submit"]');

    function validateDuplicateAttendance() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const checkedEmployees = document.querySelectorAll('.employee-checkbox:checked');

        if (!startDateInput.value || !endDateInput.value || checkedEmployees.length === 0) {
            duplicateWarning.classList.add('hidden');
            if (addSubmitBtn) {
                addSubmitBtn.disabled = false;
                addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }

        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        const duplicates = [];

        checkedEmployees.forEach(checkbox => {
            const employeeId = checkbox.value;
            const employeeName = checkbox.closest('label').textContent.trim().split(' - ')[0];

            if (existingAttendance[employeeId]) {
                // Loop through date range
                let currentDate = new Date(startDate);
                while (currentDate <= endDate) {
                    const dateStr = currentDate.toISOString().split('T')[0];

                    if (existingAttendance[employeeId].includes(dateStr)) {
                        const formattedDate = new Date(dateStr).toLocaleDateString('id-ID', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                        duplicates.push(`${employeeName} pada tanggal ${formattedDate}`);
                    }

                    currentDate.setDate(currentDate.getDate() + 1);
                }
            }
        });

        if (duplicates.length > 0) {
            const displayDuplicates = duplicates.slice(0, 5);
            let message = 'Karyawan berikut sudah memiliki absensi: ' + displayDuplicates.join('; ');

            if (duplicates.length > 5) {
                message += ` dan ${duplicates.length - 5} lainnya`;
            }

            message += '. Silakan hapus atau edit data yang sudah ada.';

            duplicateWarningText.textContent = message;
            duplicateWarning.classList.remove('hidden');

            if (addSubmitBtn) {
                addSubmitBtn.disabled = true;
                addSubmitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            return false;
        } else {
            duplicateWarning.classList.add('hidden');
            if (addSubmitBtn) {
                addSubmitBtn.disabled = false;
                addSubmitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return true;
        }
    }

    // Validasi form add modal - minimal 1 karyawan dipilih
    const addModalForm = document.querySelector('#addModal form');
    const employeeError = document.getElementById('employee-error');

    if (addModalForm) {
        addModalForm.addEventListener('submit', function(e) {
            const checkedEmployees = document.querySelectorAll('.employee-checkbox:checked');
            if (checkedEmployees.length === 0) {
                e.preventDefault();
                if (employeeError) {
                    employeeError.classList.remove('hidden');
                }
                return false;
            }

            // Validasi duplikat sebelum submit
            if (!validateDuplicateAttendance()) {
                e.preventDefault();
                return false;
            }
        });

        // Hide error message when checkbox is clicked
        document.querySelectorAll('.employee-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedEmployees = document.querySelectorAll('.employee-checkbox:checked');
                if (checkedEmployees.length > 0 && employeeError) {
                    employeeError.classList.add('hidden');
                }
            });
        });
    }

    // Validasi tanggal: end_date >= start_date dan tidak boleh masa depan
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            const dateError = document.getElementById('date-error');
            // Set minimum end_date sama dengan start_date
            endDateInput.min = this.value;

            // Jika end_date sudah terisi tapi lebih kecil dari start_date, reset
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
            // Hide error saat start_date berubah
            if (dateError) {
                dateError.classList.add('hidden');
            }

            // Validasi duplikat setelah tanggal berubah
            validateDuplicateAttendance();
        });

        endDateInput.addEventListener('change', function() {
            const dateError = document.getElementById('date-error');
            // Validasi end_date tidak boleh lebih kecil dari start_date
            if (startDateInput.value && this.value < startDateInput.value) {
                if (dateError) {
                    dateError.classList.remove('hidden');
                }
                this.value = startDateInput.value;
            } else {
                if (dateError) {
                    dateError.classList.add('hidden');
                }
            }

            // Validasi duplikat setelah tanggal berubah
            validateDuplicateAttendance();
        });
    }

    // Initialize delete button state on page load
    updateDeleteButtonState();

    // ==========================================
    // ADD/EDIT FORM SUBMIT HANDLERS
    // ==========================================

    // Handle Add Modal Submit
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            // Validasi sudah dilakukan di atas, jika lolos baru tambahkan loading
            if (!validateDuplicateAttendance()) {
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
