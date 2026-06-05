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
