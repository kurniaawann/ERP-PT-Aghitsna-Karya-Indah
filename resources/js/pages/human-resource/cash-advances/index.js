/**
 * Cash Advances (Kasbon) Index Page - JavaScript Module
 *
 * Handles all interactive functionality for the Data Kasbon page:
 * - Type toggle (personal/team)
 * - Max kasbon check via AJAX
 * - Period date resolution via AJAX
 * - Amount validation against max limit
 * - Currency formatting
 * - Select All checkbox logic
 * - Bulk delete with loading state
 * - Form submit handlers with loading states
 */

// ==========================================
// CONFIGURATION
// ==========================================

/**
 * Read CSRF token and route URLs from data attributes on the page container.
 * These are set by the Blade template to avoid hardcoding URLs in JS.
 */
const pageContainer = document.getElementById('kasbon-page');
const CSRF_TOKEN = pageContainer ? pageContainer.dataset.csrfToken : '';
const CHECK_MAX_URL = pageContainer ? pageUrl('kasbon.check-max') : '';
const GET_WEEKS_URL = pageUrl('payroll.get-weeks');

/**
 * Store max kasbon data for each form prefix (add/edit_KSB001).
 * Used by validateKasbonAmount to check against the limit.
 */
let maxKasbonData = {};

// ==========================================
// CURRENCY HELPERS
// ==========================================

/**
 * Parse a formatted currency string back to a raw integer.
 * Handles Indonesian format with dots as thousands separator.
 *
 * @param  {string} value - Formatted currency string (e.g., "15.000")
 * @returns {number} Raw integer value (e.g., 15000)
 */
function parseCurrencyInput(value) {
    return parseInt(String(value || '').replace(/[^\d]/g, ''), 10) || 0;
}

/**
 * Format an input field value as IDR currency (e.g., 15000 -> "15.000").
 * Strips all non-digit characters and re-formats with Indonesian locale.
 *
 * Assigned to window because it's called from inline oninput attributes
 * in the Blade templates (Vite loads JS as ES module, not global).
 *
 * @param {HTMLInputElement} input - The input element to format
 */
window.formatCurrencyInput = function (input) {
    if (!input) return;

    const numeric = input.value.replace(/[^\d]/g, '');
    input.value = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
};

// ==========================================
// TYPE TOGGLE (Personal / Team)
// ==========================================

/**
 * Toggle employee and division field visibility based on kasbon type.
 *
 * For 'personal': shows employee select, hides division select.
 * For 'team': shows division select, hides employee select.
 * For empty: hides both.
 *
 * Assigned to window because it's called from inline onchange attributes.
 *
 * @param {string} prefix - Form prefix ('add' or 'edit_KSB001')
 */
window.toggleEmployeeSelect = function (prefix) {
    const kasbonTypeSelect = document.getElementById(prefix + '_kasbon_type');
    const employeeField = document.getElementById(prefix + '_employee_field');
    const employeeHidden = employeeField ? employeeField.querySelector('.searchable-select-hidden') : null;
    const divisionField = document.getElementById(prefix + '_division_field');
    const divisionHidden = divisionField ? divisionField.querySelector('.searchable-select-hidden') : null;
    const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');

    if (!kasbonTypeSelect || !employeeField || !divisionField) return;

    if (kasbonTypeSelect.value === 'team') {
        employeeField.style.display = 'none';
        divisionField.style.display = 'block';
        if (limitAlert) limitAlert.classList.add('hidden');

        if (employeeHidden) {
            employeeHidden.removeAttribute('required');
            employeeHidden.value = '';
        }
        if (divisionHidden) {
            divisionHidden.setAttribute('required', 'required');
        }
        if (typeof initSearchableSelects === 'function') {
            initSearchableSelects(divisionField);
        }
    } else if (kasbonTypeSelect.value === 'personal') {
        employeeField.style.display = 'block';
        divisionField.style.display = 'none';

        if (employeeHidden) {
            employeeHidden.setAttribute('required', 'required');
        }
        if (divisionHidden) {
            divisionHidden.removeAttribute('required');
            divisionHidden.value = '';
        }
        if (typeof initSearchableSelects === 'function') {
            initSearchableSelects(employeeField);
        }
    } else {
        employeeField.style.display = 'none';
        divisionField.style.display = 'none';
        if (limitAlert) limitAlert.classList.add('hidden');
    }
};

// ==========================================
// PERIOD DATE RESOLUTION (AJAX)
// ==========================================

/**
 * Resolve period_start_date and period_end_date from month, year, and kasbon_date.
 *
 * Fetches available weeks from the Payroll endpoint and finds the week
 * whose date range contains the kasbon_date.
 *
 * @param  {string} prefix - Form prefix ('add' or 'edit_KSB001')
 * @returns {Promise<{start_date: string, end_date: string, week_number: number}|null>}
 */
async function resolvePeriodStartDate(prefix) {
    const monthSelect = document.getElementById(prefix + '_period_month');
    const yearInput = document.getElementById(prefix + '_period_year');
    const kasbonDateInput = document.getElementById(prefix + '_kasbon_date');

    const month = monthSelect ? monthSelect.value : '';
    const year = yearInput ? yearInput.value : '';
    const kasbonDate = kasbonDateInput ? kasbonDateInput.value : '';

    if (!month || !year || !kasbonDate) return null;

    try {
        const response = await fetch(`${GET_WEEKS_URL}?month=${month}&year=${year}`);
        const data = await response.json();
        const weeks = data.weeks || [];

        for (const week of weeks) {
            if (kasbonDate >= week.start_date && kasbonDate <= week.end_date) {
                return {
                    start_date: week.start_date,
                    end_date: week.end_date,
                    week_number: week.week_number,
                };
            }
        }

        if (weeks.length > 0) {
            const lastWeek = weeks[weeks.length - 1];
            return {
                start_date: lastWeek.start_date,
                end_date: lastWeek.end_date,
                week_number: lastWeek.week_number,
            };
        }

        return null;
    } catch (error) {
        console.error('Error resolving period start date:', error);
        return null;
    }
}

// ==========================================
// MAX KASBON CHECK (AJAX)
// ==========================================

/**
 * Check maximum kasbon allowed for the selected employee based on attendance.
 *
 * Resolves the period start date first, then calls the check-max endpoint.
 * Updates the limit alert UI and stores the result in maxKasbonData.
 * Disables submit button if payroll is paid or no attendance.
 *
 * Assigned to window because it's called from inline onchange attributes.
 *
 * @param {string} prefix - Form prefix ('add' or 'edit_KSB001')
 */
window.checkMaxKasbon = async function (prefix) {
    const employeeField = document.getElementById(prefix + '_employee_field');
    const employeeHidden = employeeField ? employeeField.querySelector('.searchable-select-hidden') : null;
    const employeeSelect = employeeHidden || document.getElementById(prefix + '_employee_id');
    const kasbonDateInput = document.getElementById(prefix + '_kasbon_date');
    const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');
    const limitMessage = document.getElementById(prefix + '_kasbon_limit_message');
    const amountInput = document.getElementById(prefix + '_amount');
    const weekNumberInput = document.getElementById(prefix + '_week_number');

    if (!employeeSelect || !employeeSelect.value) {
        if (limitAlert) limitAlert.classList.add('hidden');
        maxKasbonData[prefix] = null;
        return;
    }

    const kasbonDate = kasbonDateInput ? kasbonDateInput.value : '';

    const periodInfo = await resolvePeriodStartDate(prefix);
    if (!periodInfo) {
        if (limitAlert && limitMessage) {
            limitMessage.textContent = 'Silakan lengkapi Bulan, Tahun, dan Tanggal Kasbon terlebih dahulu';
            setAlertStyle(limitAlert, 'error');
        }
        maxKasbonData[prefix] = null;
        return;
    }

    if (weekNumberInput) {
        weekNumberInput.value = periodInfo.week_number;
    }

    const periodStartDateInput = document.getElementById(prefix + '_period_start_date');
    const periodEndDateInput = document.getElementById(prefix + '_period_end_date');
    if (periodStartDateInput) {
        periodStartDateInput.value = periodInfo.start_date;
    }
    if (periodEndDateInput) {
        periodEndDateInput.value = periodInfo.end_date;
    }

    try {
        const response = await fetch(CHECK_MAX_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                employee_id: employeeSelect.value,
                period_start_date: periodInfo.start_date,
                kasbon_date: kasbonDate,
            }),
        });

        const data = await response.json();

        if (data.success) {
            maxKasbonData[prefix] = data;

            if (limitAlert && limitMessage) {
                limitMessage.textContent = data.message;
                setAlertStyle(limitAlert, 'warning');
            }

            if (amountInput && amountInput.value) {
                validateKasbonAmount(prefix);
            }
        } else {
            maxKasbonData[prefix] = null;

            if (limitAlert && limitMessage) {
                limitMessage.textContent = data.message || 'Gagal mengecek maksimal kasbon';
                setAlertStyle(limitAlert, 'error');
            }

            disableSubmitButton(prefix, true);
        }
    } catch (error) {
        console.error('Error checking max kasbon:', error);
        if (limitAlert) limitAlert.classList.add('hidden');
        maxKasbonData[prefix] = null;
    }
};

// ==========================================
// AMOUNT VALIDATION
// ==========================================

/**
 * Validate kasbon amount against the max allowed limit.
 *
 * If amount exceeds max, disables submit button and shows error alert.
 * If amount is valid, enables submit button and shows warning (info) alert.
 *
 * Assigned to window because it's called from inline oninput attributes.
 *
 * @param {string} prefix - Form prefix ('add' or 'edit_KSB001')
 */
window.validateKasbonAmount = function (prefix) {
    const amountInput = document.getElementById(prefix + '_amount');
    const limitAlert = document.getElementById(prefix + '_kasbon_limit_alert');
    const limitMessage = document.getElementById(prefix + '_kasbon_limit_message');

    if (!amountInput || !maxKasbonData[prefix]) {
        const amountValue = parseCurrencyInput(amountInput ? amountInput.value : '');
        if (amountValue >= 1000) {
            disableSubmitButton(prefix, false);
        }
        return;
    }

    const amount = parseCurrencyInput(amountInput.value);
    const maxKasbon = maxKasbonData[prefix].max_kasbon;

    if (amount > maxKasbon) {
        disableSubmitButton(prefix, true);

        if (limitAlert && limitMessage) {
            limitMessage.textContent =
                `Jumlah kasbon melebihi batas maksimal ${maxKasbonData[prefix].max_kasbon_formatted}`;
            setAlertStyle(limitAlert, 'error');
        }
    } else if (amount >= 1000) {
        disableSubmitButton(prefix, false);

        if (limitAlert && limitMessage) {
            limitMessage.textContent = maxKasbonData[prefix].message;
            setAlertStyle(limitAlert, 'warning');
        }
    } else {
        if (amount > 0) {
            disableSubmitButton(prefix, true);
        }
    }
};

// ==========================================
// BULK DELETE
// ==========================================

/**
 * Submit the bulk delete form with loading state.
 * Shows a spinner on the confirm button while submitting.
 *
 * Assigned to window because it's called from an inline onclick attribute
 * in the delete confirmation modal.
 */
window.submitDeleteForm = function () {
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
};

// ==========================================
// SELECT ALL CHECKBOX
// ==========================================

/**
 * Initialize select-all checkbox and individual checkbox listeners.
 * Updates delete button state based on selection.
 */
function initSelectAllCheckbox() {
    const selectAll = document.getElementById('select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const deleteButton = document.getElementById('delete-button')
        || document.querySelector('[onclick*="deleteModal"]')
        || document.querySelector('[data-delete-button]');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowCheckboxes.forEach(checkbox => {
                if (!checkbox.disabled) {
                    checkbox.checked = this.checked;
                }
            });
            updateDeleteButtonState();
        });
    }

    rowCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateDeleteButtonState();

            if (selectAll) {
                const allChecked = Array.from(rowCheckboxes)
                    .filter(cb => !cb.disabled)
                    .every(cb => cb.checked);
                selectAll.checked = allChecked;
            }
        });
    });

    updateDeleteButtonState();
}

/**
 * Update the delete button state based on checkbox selection.
 */
function updateDeleteButtonState() {
    const deleteButton = document.getElementById('delete-button')
        || document.querySelector('[onclick*="deleteModal"]')
        || document.querySelector('[data-delete-button]');
    const anyChecked = Array.from(document.querySelectorAll('.row-checkbox'))
        .some(cb => cb.checked && !cb.disabled);

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

// ==========================================
// FORM SUBMIT HANDLERS
// ==========================================

/**
 * Initialize form submit handlers for add and edit modals.
 * Handles loading state via handleFormSubmit() from shared module.
 */
function initFormSubmitHandlers() {
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function' && !handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    }

    document.querySelectorAll('[id^="editModalK"] form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (typeof handleFormSubmit === 'function' && !handleFormSubmit(submitBtn)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// AMOUNT INPUT FORMATTING
// ==========================================

/**
 * Initialize currency formatting on all kasbon amount inputs.
 */
function initAmountFormatting() {
    document.querySelectorAll('.kasbon-amount-input').forEach(input => {
        if (input.value) {
            window.formatCurrencyInput(input);
        }

        input.addEventListener('input', function () {
            window.formatCurrencyInput(this);
            const prefix = this.id === 'add_amount' ? 'add' :
                `edit_${this.closest('[id^="editModal"]')?.id.replace('editModal', '') || ''}`;
            validateKasbonAmount(prefix);
        });
    });
}

// ==========================================
// UI HELPERS
// ==========================================

/**
 * Set the alert style (warning/error) for the limit alert element.
 *
 * @param {HTMLElement} alertEl  The alert container element
 * @param {string}      style    'warning' or 'error'
 */
function setAlertStyle(alertEl, style) {
    alertEl.classList.remove('hidden');

    const textDiv = alertEl.querySelector('.text-sm');
    const icon = alertEl.querySelector('i');

    if (style === 'error') {
        alertEl.classList.remove('bg-warning-light', 'border-border-strong');
        alertEl.classList.add('bg-error-light', 'border-error');
        if (icon) {
            icon.classList.remove('text-warning');
            icon.classList.add('text-error');
        }
        if (textDiv) {
            textDiv.classList.remove('text-warning');
            textDiv.classList.add('text-error');
        }
    } else {
        alertEl.classList.remove('bg-error-light', 'border-error');
        alertEl.classList.add('bg-warning-light', 'border-border-strong');
        if (icon) {
            icon.classList.remove('text-error');
            icon.classList.add('text-warning');
        }
        if (textDiv) {
            textDiv.classList.remove('text-error');
            textDiv.classList.add('text-warning');
        }
    }
}

/**
 * Enable or disable the submit button for a given form prefix.
 *
 * @param {string}  prefix   Form prefix ('add' or 'edit_KSB001')
 * @param {boolean} disable  true to disable, false to enable
 */
function disableSubmitButton(prefix, disable) {
    let modalId;
    if (prefix === 'add') {
        modalId = 'addModal';
    } else if (prefix.startsWith('edit_')) {
        modalId = 'editModal' + prefix.replace('edit_', '');
    }

    const submitButton = modalId ? document.getElementById('submit-btn-' + modalId) : null;
    if (submitButton) {
        submitButton.disabled = disable;
        if (disable) {
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
}

/**
 * Get the URL for a named route from data attributes.
 * Falls back to reading from meta tags or window configuration.
 *
 * @param  {string} routeName  Laravel route name
 * @returns {string} The route URL
 */
function pageUrl(routeName) {
    const urlMap = {
        'kasbon.check-max': pageContainer?.dataset.urlCheckMax || '/kasbon/check-max',
        'payroll.get-weeks': pageContainer?.dataset.urlGetWeeks || '/payroll/weeks',
    };
    return urlMap[routeName] || '#';
}

// ==========================================
// INITIALIZATION
// ==========================================

/**
 * Initialize all kasbon page functionality on DOM ready.
 */
document.addEventListener('DOMContentLoaded', function () {
    initSelectAllCheckbox();
    initFormSubmitHandlers();
    initAmountFormatting();

    toggleEmployeeSelect('add');

    if (typeof initSearchableSelects === 'function') {
        initSearchableSelects();
    }
});
