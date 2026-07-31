const formSubmitState = {
    isSubmitting: false,
};

function handleFormSubmit(submitBtn, originalText, loadingText = 'Menyimpan...') {
    if (formSubmitState.isSubmitting) return false;

    formSubmitState.isSubmitting = true;

    if (submitBtn) {
        if (!submitBtn.dataset.originalHtml) {
            submitBtn.dataset.originalHtml = originalText || submitBtn.innerHTML;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
        submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
    }

    return true;
}

function resetFormSubmitState() {
    formSubmitState.isSubmitting = false;

    document.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = false;
        button.classList.remove('opacity-70', 'cursor-not-allowed');

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    });
}

window.handleFormSubmit = handleFormSubmit;
window.resetFormSubmitState = resetFormSubmitState;

// ─── Helper Download dengan Indikator Loading ─────────────────────────────────

function getFilenameFromResponse(response) {
    const disposition = response.headers.get('Content-Disposition');
    if (disposition) {
        const match = disposition.match(/filename[^;=\n]*=["']?([^"'\n]*)["']?/);
        if (match) return match[1];
    }
    const url = new URL(response.url);
    const pathname = url.pathname;
    return pathname.substring(pathname.lastIndexOf('/') + 1) || 'download';
}

function setButtonLoading(btn, loading, loadingText = 'Downloading...') {
    if (!btn) return;
    if (loading) {
        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }
        btn.disabled = true;
        btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${loadingText}`;
        btn.classList.add('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
        btn.classList.remove('opacity-70', 'cursor-not-allowed', 'pointer-events-none');
        delete btn.dataset.originalHtml;
    }
}

async function handleDownload(url, triggerBtn, loadingText = 'Downloading...') {
    if (triggerBtn && triggerBtn.dataset.downloading === 'true') return;

    if (triggerBtn) {
        triggerBtn.dataset.downloading = 'true';
    }

    setButtonLoading(triggerBtn, true, loadingText);

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

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
    } catch (error) {
        console.error('Download failed:', error);
    } finally {
        if (triggerBtn) {
            triggerBtn.dataset.downloading = 'false';
        }
        setButtonLoading(triggerBtn, false);
    }
}

async function handleFormDownload(formOrId, triggerBtn, loadingText = 'Memproses...') {
    if (triggerBtn && triggerBtn.dataset.downloading === 'true') return;

    if (triggerBtn) {
        triggerBtn.dataset.downloading = 'true';
    }

    setButtonLoading(triggerBtn, true, loadingText);

    try {
        const form = typeof formOrId === 'string' ? document.getElementById(formOrId) : formOrId;
        if (!form) throw new Error('Form not found');

        const formData = new FormData(form);

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

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
    } catch (error) {
        console.error('Download failed:', error);
    } finally {
        if (triggerBtn) {
            triggerBtn.dataset.downloading = 'false';
        }
        setButtonLoading(triggerBtn, false);
    }
}

window.getFilenameFromResponse = getFilenameFromResponse;
window.handleDownload = handleDownload;
window.handleFormDownload = handleFormDownload;
window.setButtonLoading = setButtonLoading;
