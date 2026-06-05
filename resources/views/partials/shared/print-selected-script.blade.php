function sharedPrintSelected(route, checkboxSelector = 'input[name="ids[]"]:checked', emptyMessage = 'Tidak ada data yang dipilih!') {
    const checkedCheckboxes = document.querySelectorAll(checkboxSelector);

    if (checkedCheckboxes.length === 0) {
        alert(emptyMessage);
        return false;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route;
    form.style.display = 'none';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    Array.from(checkedCheckboxes).forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = checkbox.value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    return true;
}