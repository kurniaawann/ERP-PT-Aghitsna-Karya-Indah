/**
 * Searchable Select - Initializes searchable dropdowns created by the
 * <x-forms.searchable-select> Blade component.
 *
 * Usage: call initSearchableSelects() after DOM ready or after a modal opens.
 */

function initSearchableSelects(container) {
    const wrappers = (container || document).querySelectorAll('.searchable-select-wrapper');

    wrappers.forEach(function(wrapper) {
        // Skip already initialized
        if (wrapper.dataset.searchableInitialized === 'true') return;
        wrapper.dataset.searchableInitialized = 'true';

        const searchInput = wrapper.querySelector('.searchable-select-input');
        const dropdown = wrapper.querySelector('.searchable-dropdown');
        const options = wrapper.querySelectorAll('.searchable-option');
        const noResults = wrapper.querySelector('.searchable-no-results');
        const hiddenInput = wrapper.querySelector('.searchable-select-hidden');

        if (!searchInput || !dropdown) return;

        // Show dropdown on focus
        searchInput.addEventListener('focus', function() {
            dropdown.classList.remove('hidden');
        });

        // Search functionality
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let hasResults = false;

            options.forEach(function(option) {
                const searchText = option.dataset.search || '';
                if (searchText.includes(searchTerm)) {
                    option.style.display = 'block';
                    hasResults = true;
                } else {
                    option.style.display = 'none';
                }
            });

            const optionsDiv = wrapper.querySelector('.searchable-options');
            if (hasResults) {
                noResults.classList.add('hidden');
                optionsDiv.classList.remove('hidden');
            } else {
                noResults.classList.remove('hidden');
                optionsDiv.classList.add('hidden');
            }
        });

        // Handle option selection
        options.forEach(function(option) {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const label = this.dataset.label;

                searchInput.value = label || '';
                hiddenInput.value = value || '';

                dropdown.classList.add('hidden');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    });
}

window.initSearchableSelects = initSearchableSelects;

// Auto-init on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initSearchableSelects();
    });
} else {
    initSearchableSelects();
}
