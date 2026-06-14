{{--
    Shared Select-All + Delete Button State Script
    Usage: @include('partials.shared.select-all-script')
    Then call: initSelectAll('checkboxName', 'deleteButtonId', callbackFunction)
    - checkboxName: value of the name attribute on checkboxes (e.g. 'ids[]')
    - deleteButtonId: ID of the delete button (default: 'delete-button')
    - onStateChange: optional function(count) called when selection changes
--}}
function initSelectAll(checkboxName, deleteButtonId, onStateChange) {
    deleteButtonId = deleteButtonId || 'delete-button';

    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('input[name="' + checkboxName + '"]');
    const deleteButton = document.getElementById(deleteButtonId);

    function updateDeleteButtonState() {
        const checkedCheckboxes = document.querySelectorAll('input[name="' + checkboxName + '"]:checked');
        const count = checkedCheckboxes.length;

        if (deleteButton) {
            deleteButton.disabled = count === 0;
            if (count > 0) {
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
                deleteButton.classList.add('hover:bg-btn-delete-hover');
            } else {
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
                deleteButton.classList.remove('hover:bg-btn-delete-hover');
            }
        }

        if (typeof onStateChange === 'function') {
            onStateChange(count);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            checkboxes.forEach(function(cb) { cb.checked = this.checked; }, this);
            updateDeleteButtonState();
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            if (!this.checked && selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateDeleteButtonState();
        });
    });

    updateDeleteButtonState();
}
