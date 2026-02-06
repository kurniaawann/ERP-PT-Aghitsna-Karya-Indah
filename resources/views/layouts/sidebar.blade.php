<!-- Sidebar -->
<aside id="sidebar"
    class="bg-white shadow-lg w-64 fixed lg:static inset-y-0 left-0 z-50 transform 
           -translate-x-full lg:translate-x-0 flex flex-col transition-transform duration-300 ease-in-out">

    <!-- Header Sidebar -->
    <div class="p-4 border-b border-border flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                <span class="text-white font-bold text-lg">AGI</span>
            </div>
            <span class="font-semibold text-text-heading">PT Aghitsna Karya Indah</span>
        </div>
        <button id="closeSidebar" class="lg:hidden text-text-secondary hover:text-text-primary">
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
                        {{ request()->is('dashboard') ? 'bg-primary-light text-primary' : 'text-text-primary hover:bg-primary-light hover:text-primary' }}">

                    <i
                        class="fas fa-home w-5 
                        {{ request()->is('dashboard') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                    </i>
                    <span class="ml-3 font-medium">Dashboard</span>
                </a>
            </li>

            {{-- Inventory Dropdown --}}
            <li>
                <button onclick="toggleDropdown('inventoryDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-box-open w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Inventory</span>
                    </div>

                    <i id="inventoryDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                {{-- Submenu --}}
                <ul id="inventoryDropdown" class="ml-8 mt-2 space-y-1 {{ request()->is('item*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/item') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('item*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-boxes w-4 
                                {{ request()->is('item*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Data Barang</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Invoice Dropdown --}}
            <li>
                <button onclick="toggleDropdown('invoiceDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-file-invoice w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Keuangan</span>
                    </div>

                    <i id="invoiceDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                {{-- Submenu --}}
                <ul id="invoiceDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('alumunium-invoice*') || request()->is('proyek-invoice*') || request()->is('payment-accounts*') || request()->is('recap-sales*') || request()->is('recap-expense*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/alumunium-invoice') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('alumunium-invoice*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-file-contract w-4 
                                {{ request()->is('alumunium-invoice*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Invoice Alumunium</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/proyek-invoice') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('proyek-invoice*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-file-contract w-4 
                                {{ request()->is('proyek-invoice*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Invoice Proyek</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/payment-accounts') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('payment-accounts*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-building-columns w-4 
                                {{ request()->is('payment-accounts*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Rekening Pembayaran</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/recap-sales') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('recap-sales*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-chart-bar w-4 
                                {{ request()->is('recap-sales*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Rekap Penjualan</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/recap-expense') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('recap-expense*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-money-bill-wave w-4 
                                {{ request()->is('recap-expense*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Rekap Pengeluaran</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Laporan Dropdown --}}
            <li>
                <button onclick="toggleDropdown('laporanDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-chart-line w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Laporan</span>
                    </div>

                    <i id="laporanDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                {{-- Submenu --}}
                <ul id="laporanDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('transaction-category*') || request()->is('report/sales*') || request()->is('report/expense*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/transaction-category') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('transaction-category*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-tags w-4 
                                {{ request()->is('transaction-category*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Kategori Transaksi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('report.sales') }}"
                            class="flex items-center px-4 py-2 rounded-lg group
                                {{ request()->is('report/sales*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-chart-line w-4 
                                {{ request()->is('report/sales*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Laporan Penjualan</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('report.expense') }}"
                            class="flex items-center px-4 py-2 rounded-lg group
                                {{ request()->is('report/expense*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-chart-pie w-4 
                                {{ request()->is('report/expense*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Laporan Pengeluaran</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- SDM (Sumber Daya Manusia) Dropdown --}}
            <li>
                <button onclick="toggleDropdown('sdmDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-users w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">SDM</span>
                    </div>

                    <i id="sdmDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                {{-- Submenu --}}
                <ul id="sdmDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('employee*') || request()->is('attendance*') || request()->is('overtime*') || request()->is('payroll*') || request()->is('kasbon*') || request()->is('reimburse*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/employee') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('employee*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-user-tie w-4 
                                {{ request()->is('employee*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Data Karyawan</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/attendance') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('attendance*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-calendar-check w-4 
                                {{ request()->is('attendance*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Absensi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/overtime') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('overtime*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-clock w-4 
                                {{ request()->is('overtime*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Lembur</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/kasbon') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('kasbon*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-hand-holding-usd w-4 
                                {{ request()->is('kasbon*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Kasbon</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/payroll') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('payroll*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-money-check-alt w-4 
                                {{ request()->is('payroll*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Payroll</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/reimburse') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('reimburse*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-receipt w-4 
                                {{ request()->is('reimburse*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Reimbursement</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Administrasi Dropdown --}}
            <li>
                <button onclick="toggleDropdown('administrasiDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-folder-open w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Administrasi</span>
                    </div>

                    <i id="administrasiDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                {{-- Submenu --}}
                <ul id="administrasiDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('document-receipt*') || request()->is('cash-out-proof*') || request()->is('kwintansi*') || request()->is('invoice-administrasi*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/document-receipt') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('document-receipt*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-file-signature w-4 
                                {{ request()->is('document-receipt*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Tanda Terima Dokumen</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/cash-out-proof') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('cash-out-proof*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-money-bill-wave w-4 
                                {{ request()->is('cash-out-proof*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Bukti Kas Keluar</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/kwintansi') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('kwintansi*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-receipt w-4 
                                {{ request()->is('kwintansi*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Kwintansi</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/invoice-administrasi') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('invoice-administrasi*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-file-invoice w-4 
                                {{ request()->is('invoice-administrasi*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Invoice</span>
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
