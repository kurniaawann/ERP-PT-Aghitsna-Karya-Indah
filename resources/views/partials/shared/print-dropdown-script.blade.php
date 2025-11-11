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
</script>
