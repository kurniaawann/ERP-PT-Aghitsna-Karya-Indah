<!-- Sidebar -->
<aside id="sidebar"
    class="bg-white shadow-lg w-64 fixed lg:static inset-y-0 left-0 z-50 transform 
           -translate-x-full lg:translate-x-0 flex flex-col transition-transform duration-300 ease-in-out">

    <!-- Header Sidebar -->
    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                <span class="text-white font-bold text-lg">AGI</span>
            </div>
            <span class="font-semibold text-gray-800">PT Aghitsna Karya Indah</span>
        </div>
        <button id="closeSidebar" class="lg:hidden text-gray-500 hover:text-gray-700">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- Navigasi -->
    <nav class="flex-1 overflow-y-auto py-4">
        <ul class="space-y-1 px-3">

            {{-- Dashboard --}}
            <li>
                <a href="{{ url('/dashboard') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 group
                        {{ request()->is('dashboard') ? 'bg-primary-light text-primary' : 'text-gray-700 hover:bg-primary-light hover:text-primary' }}">

                    <i
                        class="fas fa-home w-5 
                        {{ request()->is('dashboard') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">
                    </i>
                    <span class="ml-3 font-medium">Dashboard</span>
                </a>
            </li>

            {{-- Data Barang --}}
            <li>
                <a href="{{ url('/item') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 group
                        {{ request()->is('item*') ? 'bg-primary-light text-primary' : 'text-gray-700 hover:bg-primary-light hover:text-primary' }}">

                    <i
                        class="fas fa-box-open w-5 
                        {{ request()->is('item*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">
                    </i>
                    <span class="ml-3 font-medium">Data Barang</span>
                </a>
            </li>

            {{-- Kategori Transaksi --}}
            <li>
                <a href="{{ url('/transaction-category') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 group
                        {{ request()->is('transaction-category*') ? 'bg-primary-light text-primary' : 'text-gray-700 hover:bg-primary-light hover:text-primary' }}">

                    <i
                        class="fas fa-tags w-5 
                        {{ request()->is('transaction-category*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">
                    </i>
                    <span class="ml-3 font-medium">Kategori Transaksi</span>
                </a>
            </li>

            {{-- Invoice Dropdown --}}
            <li>
                <button onclick="toggleDropdown('invoiceDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-gray-700 hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-file-invoice w-5 text-gray-400 group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Invoice</span>
                    </div>

                    <i id="invoiceDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-gray-400 group-hover:text-primary">
                    </i>
                </button>

                {{-- Submenu --}}
                <ul id="invoiceDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('alumunium-invoice*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/alumunium-invoice') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('alumunium-invoice*') ? 'bg-primary-light text-primary' : 'text-gray-600 hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-receipt w-4 
                                {{ request()->is('alumunium-invoice*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Invoice Alumunium</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Laporan Dropdown --}}
            <li>
                <button onclick="toggleDropdown('laporanDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group 
                        {{ request()->is('sales-report*') || request()->is('expense-report*') ? 'bg-primary-light text-primary' : 'text-gray-700 hover:bg-primary-light hover:text-primary' }}">

                    <div class="flex items-center">
                        <i
                            class="fas fa-chart-line w-5 
                            {{ request()->is('sales-report*') || request()->is('expense-report*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">
                        </i>
                        <span class="ml-3 font-medium">Laporan</span>
                    </div>

                    <i id="laporanDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 
                            {{ request()->is('sales-report*') || request()->is('expense-report*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">
                    </i>
                </button>

                {{-- Submenu --}}
                <ul id="laporanDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('sales-report*') || request()->is('expense-report*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/sales-report') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('sales-report*') ? 'bg-primary-light text-primary' : 'text-gray-600 hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-chart-bar w-4 
                                {{ request()->is('sales-report*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Laporan Penjualan</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/expense-report') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('expense-report*') ? 'bg-primary-light text-primary' : 'text-gray-600 hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-money-bill-wave w-4 
                                {{ request()->is('expense-report*') ? 'text-primary' : 'text-gray-400 group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Laporan Pengeluaran</span>
                        </a>
                    </li>
                </ul>
            </li>

        </ul>
    </nav>
</aside>

<!-- Overlay untuk mobile -->
<div id="sidebarOverlay"
    class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden transition-opacity duration-300"></div>

<script>
    // Pastikan script berjalan setelah DOM loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Toggle Logic
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleButton = document.getElementById('toggleSidebar');
        const closeButton = document.getElementById('closeSidebar');

        // Function untuk membuka sidebar
        function openSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                // Prevent body scroll saat sidebar terbuka di mobile
                document.body.style.overflow = 'hidden';
            }
        }

        // Function untuk menutup sidebar
        function closeSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                // Restore body scroll
                document.body.style.overflow = '';
            }
        }

        // Event listener untuk toggle button
        if (toggleButton) {
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openSidebar();
            });
        }

        // Event listener untuk close button
        if (closeButton) {
            closeButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeSidebar();
            });
        }

        // Event listener untuk overlay
        if (overlay) {
            overlay.addEventListener('click', function(e) {
                e.preventDefault();
                closeSidebar();
            });
        }

        // Tutup sidebar saat resize ke desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) { // lg breakpoint
                closeSidebar();
            }
        });
    });

    // Function untuk toggle dropdown
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const icon = document.getElementById(dropdownId + 'Icon');

        if (dropdown && icon) {
            dropdown.classList.toggle('hidden');

            // Toggle icon antara chevron-down dan chevron-up
            if (dropdown.classList.contains('hidden')) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
    }
</script>
