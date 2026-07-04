<script>
    // Dropdown Print Laporan Handler
    document.addEventListener('DOMContentLoaded', function() {
        const printDropdownButton = document.getElementById('printDropdownButton');
        const printDropdownMenu = document.getElementById('printDropdownMenu');

        if (printDropdownButton && printDropdownMenu) {
            printDropdownButton.addEventListener('click', function(e) {
                e.stopPropagation();
                printDropdownMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!printDropdownButton.contains(e.target) && !printDropdownMenu.contains(e.target)) {
                    printDropdownMenu.classList.add('hidden');
                }
            });

            // Prevent dropdown from closing when clicking inside
            printDropdownMenu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });

    // ─── Global Download Link Handler ─────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('a[href*="/export/pdf"], a[href*="/export/excel"], a[href*="/print/pdf"], a[href*="/print/excel"], a[href*="/export-pdf"], a[href*="/export-excel"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // Close dropdown if this link is inside one
                const dropdown = this.closest('#printDropdownMenu');
                if (dropdown) {
                    dropdown.classList.add('hidden');
                }
                handleDownload(this.href, this, 'Downloading...');
            });
        });
    });
</script>
