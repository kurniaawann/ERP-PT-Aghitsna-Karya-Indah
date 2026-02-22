<script>
    // Store maksimal kasbon untuk setiap form
    let maxKasbonData = {};

    // Toggle employee select based on kasbon type
    function toggleEmployeeSelect(prefix) {
        const kasbonTypeSelect = document.getElementById(prefix + '_kasbon_type');
        const employeeField = document.getElementById(prefix + '_employee_field');
        const employeeSelect = document.getElementById(prefix + '_employee_id');
        const divisionField = document.getElementById(prefix + '_division_field');
        const divisionSelect = document.getElementById(prefix + '_division');
        const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');

        if (kasbonTypeSelect && employeeField && divisionField) {
            if (kasbonTypeSelect.value === 'team') {
                // Hide employee field, show division field
                employeeField.style.display = 'none';
                divisionField.style.display = 'block';
                if (limitAlert) limitAlert.classList.add('hidden');

                if (employeeSelect) {
                    employeeSelect.removeAttribute('required');
                    employeeSelect.value = '';
                }
                if (divisionSelect) {
                    divisionSelect.setAttribute('required', 'required');
                }
            } else if (kasbonTypeSelect.value === 'personal') {
                // Show employee field, hide division field
                employeeField.style.display = 'block';
                divisionField.style.display = 'none';

                if (employeeSelect) {
                    employeeSelect.setAttribute('required', 'required');
                }
                if (divisionSelect) {
                    divisionSelect.removeAttribute('required');
                    divisionSelect.value = '';
                }
            } else {
                // No selection, hide both
                employeeField.style.display = 'none';
                divisionField.style.display = 'none';
                if (limitAlert) limitAlert.classList.add('hidden');
            }
        }
    }

    // Check maksimal kasbon berdasarkan kehadiran sampai tanggal kasbon
    async function checkMaxKasbon(prefix) {
        const employeeSelect = document.getElementById(prefix + '_employee_id');
        const monthSelect = document.getElementById(prefix + '_period_month');
        const yearInput = document.getElementById(prefix + '_period_year');
        const kasbonDateInput = document.getElementById(prefix + '_kasbon_date');
        const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');
        const limitMessage = document.getElementById(prefix + '_kasbon_limit_message');
        const amountInput = document.getElementById(prefix + '_amount');
        const weekNumberInput = document.getElementById(prefix + '_week_number');

        // Cek apakah semua field yang diperlukan sudah diisi
        if (!employeeSelect || !employeeSelect.value) {
            if (limitAlert) limitAlert.classList.add('hidden');
            maxKasbonData[prefix] = null;
            return;
        }

        const month = monthSelect ? monthSelect.value : '';
        const year = yearInput ? yearInput.value : '';
        const kasbonDate = kasbonDateInput ? kasbonDateInput.value : '';

        if (!month || !year || !kasbonDate) {
            if (limitAlert && limitMessage) {
                limitMessage.textContent =
                    'Silakan lengkapi Bulan, Tahun, dan Tanggal Kasbon terlebih dahulu';
                limitAlert.classList.remove('hidden', 'bg-yellow-50', 'border-yellow-200');
                limitAlert.classList.add('bg-red-50', 'border-red-200');
                const icon = limitAlert.querySelector('i');
                if (icon) {
                    icon.classList.remove('text-yellow-600');
                    icon.classList.add('text-red-600');
                }
            }
            maxKasbonData[prefix] = null;
            return;
        }

        try {
            const response = await fetch('{{ route('kasbon.check-max') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    employee_id: employeeSelect.value,
                    period_month: month,
                    period_year: year,
                    kasbon_date: kasbonDate
                })
            });

            const data = await response.json();

            if (data.success) {
                // Auto-fill week number jika ada hidden input
                if (weekNumberInput && data.week_number) {
                    weekNumberInput.value = data.week_number;
                }

                // Simpan data kasbon maksimal
                maxKasbonData[prefix] = data;

                if (limitAlert && limitMessage) {
                    limitMessage.textContent = data.message;
                    limitAlert.classList.remove('hidden', 'bg-red-50', 'border-red-200');
                    limitAlert.classList.add('bg-yellow-50', 'border-yellow-200');

                    const icon = limitAlert.querySelector('i');
                    if (icon) {
                        icon.classList.remove('text-red-600');
                        icon.classList.add('text-yellow-600');
                    }

                    const textDiv = limitAlert.querySelector('.text-sm');
                    if (textDiv) {
                        textDiv.classList.remove('text-red-800');
                        textDiv.classList.add('text-yellow-800');
                    }
                }

                // Validasi amount jika sudah diisi
                if (amountInput && amountInput.value) {
                    validateKasbonAmount(prefix);
                }
            } else {
                // Handle berbagai error: payroll_paid, no_attendance, atau error lain
                maxKasbonData[prefix] = null;

                // Auto-fill week number jika ada
                if (weekNumberInput && data.week_number) {
                    weekNumberInput.value = data.week_number;
                }

                if (limitAlert && limitMessage) {
                    limitMessage.textContent = data.message || 'Gagal mengecek maksimal kasbon';
                    limitAlert.classList.remove('hidden', 'bg-yellow-50', 'border-yellow-200');
                    limitAlert.classList.add('bg-red-50', 'border-red-200');

                    const icon = limitAlert.querySelector('i');
                    if (icon) {
                        icon.classList.remove('text-yellow-600');
                        icon.classList.add('text-red-600');
                    }

                    const textDiv = limitAlert.querySelector('.text-sm');
                    if (textDiv) {
                        textDiv.classList.remove('text-yellow-800');
                        textDiv.classList.add('text-red-800');
                    }
                }

                // Disable submit button karena ada error (payroll paid / no attendance / dll)
                let modalId;
                if (prefix === 'add') {
                    modalId = 'addModal';
                } else if (prefix.startsWith('edit_')) {
                    modalId = 'editModal' + prefix.replace('edit_', '');
                }
                const submitButton = modalId ? document.getElementById('submit-btn-' + modalId) : null;
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch (error) {
            console.error('Error checking max kasbon:', error);
            if (limitAlert) limitAlert.classList.add('hidden');
            maxKasbonData[prefix] = null;
        }
    }

    // Validasi jumlah kasbon saat input
    function validateKasbonAmount(prefix) {
        const amountInput = document.getElementById(prefix + '_amount');
        const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');
        const limitMessage = document.getElementById(prefix + '_kasbon_limit_message');

        // Dapatkan ID modal dari prefix (add -> addModal, edit_KSB001 -> editModalKSB001)
        let modalId;
        if (prefix === 'add') {
            modalId = 'addModal';
        } else if (prefix.startsWith('edit_')) {
            modalId = 'editModal' + prefix.replace('edit_', '');
        }
        const submitButton = modalId ? document.getElementById('submit-btn-' + modalId) : null;

        if (!amountInput || !maxKasbonData[prefix]) {
            // Jika belum ada data max kasbon, enable button jika amount >= 1000
            if (submitButton && amountInput.value >= 1000) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return;
        }

        const amount = parseInt(amountInput.value) || 0;
        const maxKasbon = maxKasbonData[prefix].max_kasbon;

        if (amount > maxKasbon) {
            // Kasbon melebihi batas - disable button dan tampilkan error
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }

            if (limitAlert && limitMessage) {
                limitMessage.textContent =
                    `Jumlah kasbon melebihi batas maksimal ${maxKasbonData[prefix].max_kasbon_formatted}`;
                limitAlert.classList.remove('bg-yellow-50', 'border-yellow-200');
                limitAlert.classList.add('bg-red-50', 'border-red-200');

                const icon = limitAlert.querySelector('i');
                if (icon) {
                    icon.classList.remove('text-yellow-600');
                    icon.classList.add('text-red-600');
                }

                const textDiv = limitAlert.querySelector('.text-sm');
                if (textDiv) {
                    textDiv.classList.remove('text-yellow-800');
                    textDiv.classList.add('text-red-800');
                }
            }
        } else if (amount >= 1000) {
            // Kasbon valid - enable button dan kembalikan warna alert ke kuning (info)
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }

            if (limitAlert && limitMessage) {
                limitMessage.textContent = maxKasbonData[prefix].message;
                limitAlert.classList.add('bg-yellow-50', 'border-yellow-200');
                limitAlert.classList.remove('bg-red-50', 'border-red-200');

                const icon = limitAlert.querySelector('i');
                if (icon) {
                    icon.classList.add('text-yellow-600');
                    icon.classList.remove('text-red-600');
                }

                const textDiv = limitAlert.querySelector('.text-sm');
                if (textDiv) {
                    textDiv.classList.add('text-yellow-800');
                    textDiv.classList.remove('text-red-800');
                }
            }
        } else {
            // Amount kurang dari minimum (1000) - disable button
            if (submitButton && amount > 0) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Handle bulk delete
    function submitDeleteForm() {
        const deleteBtn = document.getElementById('confirm-btn-deleteModal');
        if (deleteBtn) {
            deleteBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menghapus...';
            deleteBtn.disabled = true;
            deleteBtn.classList.add('opacity-70', 'cursor-not-allowed');
        }

        const form = document.getElementById('deleteForm');
        if (form) {
            form.submit();
        }
    }

    // Select All Checkbox Handler
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select-all');
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        const deleteButton = document.querySelector('[onclick*="deleteModal"]');

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                rowCheckboxes.forEach(checkbox => {
                    if (!checkbox.disabled) {
                        checkbox.checked = this.checked;
                    }
                });
                updateDeleteButtonState();
            });
        }

        if (rowCheckboxes) {
            rowCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateDeleteButtonState();

                    // Update select-all state
                    if (selectAll) {
                        const allChecked = Array.from(rowCheckboxes)
                            .filter(cb => !cb.disabled)
                            .every(cb => cb.checked);
                        selectAll.checked = allChecked;
                    }
                });
            });
        }

        function updateDeleteButtonState() {
            const anyChecked = Array.from(rowCheckboxes).some(cb => cb.checked && !cb.disabled);

            if (deleteButton) {
                if (anyChecked) {
                    deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    deleteButton.disabled = false;
                } else {
                    deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
                    deleteButton.disabled = true;
                }
            }
        }

        // Initial state
        updateDeleteButtonState();

        // Initialize employee field visibility for add modal
        toggleEmployeeSelect('add');
    });
</script>
