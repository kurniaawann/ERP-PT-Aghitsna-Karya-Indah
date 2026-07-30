function sharedPrintSelected(route, triggerBtn = null, checkboxSelector = 'input[name="ids[]"]:checked', emptyMessage = 'Tidak ada data yang dipilih!') {
    const checkedCheckboxes = document.querySelectorAll(checkboxSelector);

    if (checkedCheckboxes.length === 0) {
        alert(emptyMessage);
        return false;
    }

    if (triggerBtn && triggerBtn.dataset.downloading === 'true') return false;

    if (triggerBtn) {
        triggerBtn.dataset.downloading = 'true';
    }

    setButtonLoading(triggerBtn, true, 'Memproses...');

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');

    Array.from(checkedCheckboxes).forEach(checkbox => {
        formData.append('ids[]', checkbox.value);
    });

    fetch(route, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(async (response) => {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const blob = await response.blob();
        const filename = getFilenameFromResponse(response);
        const objectUrl = window.URL.createObjectURL(blob);
        const tempAnchor = document.createElement('a');
        tempAnchor.href = objectUrl;
        tempAnchor.download = filename;
        document.body.appendChild(tempAnchor);
        tempAnchor.click();
        tempAnchor.remove();
        window.URL.revokeObjectURL(objectUrl);
    })
    .catch(error => {
        console.error('Download failed:', error);
    })
    .finally(() => {
        if (triggerBtn) {
            triggerBtn.dataset.downloading = 'false';
        }
        setButtonLoading(triggerBtn, false);
    });

    return true;
}
