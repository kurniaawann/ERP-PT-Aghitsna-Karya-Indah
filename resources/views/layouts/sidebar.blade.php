<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ERP System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Menambahkan Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Overlay untuk mobile -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar-transition bg-white shadow-lg w-64 fixed lg:static inset-y-0 left-0 z-50 transform -translate-x-full lg:translate-x-0 flex flex-col">
            <!-- Header Sidebar --> 
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                        <span class="text-white font-bold text-lg">AGI</span>
                    </div>
                    <span class="font-semibold text-gray-800">PT Aghitsna Karya Indah</span>
                </div>
                <!-- Tombol tutup untuk mobile -->
                <button id="closeSidebar" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Navigasi Sidebar -->
            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-1 px-3">
                    <!--  Dashboard -->
                    <li>
                        <a href="{{ url('/dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 group active-sidebar-item" data-menu="dashboard">
                            <i class="fas fa-tachometer-alt w-5 text-gray-400 group-hover:text-blue-500"></i>
                            <span class="ml-3 font-medium">Dashboard</span>
                            <span class="active-indicator ml-auto w-2 h-8 bg-primary rounded-l-lg hidden"></span>
                             <!-- <span class="active-indicator mr-auto w-2 h-8 bg-primary rounded-r-lg hidden"></span> -->

                        </a>
                    </li>
                    <!-- Items -->
                    <li>
                        <a href="{{ url('/item') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 group active-sidebar-item" data-menu="item">
                            <i class="fas fa-boxes w-5 text-gray-400 group-hover:text-blue-500"></i>
                            <span class="ml-3 font-medium">Data Barang</span>
                            <span class="active-indicator ml-auto w-2 h-8 bg-primary rounded-l-lg hidden"></span>
                             <!-- <span class="active-indicator mr-auto w-2 h-8 bg-primary rounded-r-lg hidden"></span> -->

                        </a>
                    </li>
                    
                 
                </ul>
            </nav>

            <!-- Footer Sidebar
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <span class="text-gray-600 text-sm font-medium">{{ substr(auth()->user()->name ?? 'Guest', 0, 1) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name ?? 'Guest' }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email ?? 'guest@example.com' }}</p>
                    </div>
                </div>
            </div> -->
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm z-10">
                <div class="flex items-center justify-between p-4">
                    <!-- Tombol toggle sidebar untuk mobile -->
                    <button id="toggleSidebar" class="lg:hidden text-gray-500 hover:text-gray-700 focus:outline-none">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <!-- Breadcrumb atau judul halaman -->
                    <div class="hidden md:block">
                        <h1 class="text-xl font-semibold text-gray-800" id="pageTitle">@yield('title', 'Dashboard')</h1>
                    </div>
                    
                    <!-- User menu dan logout -->
                    <div class="flex items-center space-x-4">
                        <div class="hidden md:flex items-center space-x-2 text-gray-700">
                            <i class="fas fa-user-circle"></i>
                            <span>{{ auth()->user()->name ?? 'Guest' }}</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center space-x-2">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Fungsi untuk menandai menu aktif
        function setActiveMenu() {
            // Hapus semua status aktif
            document.querySelectorAll('.active-sidebar-item').forEach(item => {
                item.classList.remove('active-sidebar-item');
                item.classList.remove('bg-blue-50', 'text-blue-600');
                
                // Sembunyikan indikator aktif
                const indicator = item.querySelector('.active-indicator');
                if (indicator) {
                    indicator.classList.add('hidden');
                }
                
                // Reset ikon warna
                const icon = item.querySelector('i');
                if (icon) {
                    icon.classList.remove('text-blue-500');
                    icon.classList.add('text-gray-400');
                }
            });
            
            // Dapatkan path/hash saat ini
            const currentPath = window.location.pathname;
            const currentHash = window.location.hash.replace('#', '');
            
            // Tentukan menu yang aktif berdasarkan path atau hash
            let activeMenu = '';
            
            

            switch (currentPath) {
                case '/dashboard':
                    activeMenu = 'dashboard';
                    break;
               
                case '/item':
                    activeMenu = 'item';
                    break;
               
            
                default:
                    break;
            }
            
            // Jika tidak ada yang cocok, default ke dashboard
            if (!activeMenu && (currentPath === '/' || currentPath === '')) {
                activeMenu = 'dashboard';
            }
            
            // Terapkan status aktif ke menu yang sesuai
            if (activeMenu) {
                const activeItem = document.querySelector(`[data-menu="${activeMenu}"]`);
                if (activeItem) {
                    activeItem.classList.add('active-sidebar-item', 'bg-blue-50', 'text-blue-600');
                    
                    // Tampilkan indikator aktif
                    const indicator = activeItem.querySelector('.active-indicator');
                    if (indicator) {
                        indicator.classList.remove('hidden');
                    }
                    
                    // Ubah warna ikon
                    const icon = activeItem.querySelector('i');
                    if (icon) {
                        icon.classList.remove('text-gray-400');
                        icon.classList.add('text-blue-500');
                    }
                    
                    // Update judul halaman
                    const pageTitle = document.getElementById('pageTitle');
                    if (pageTitle && !pageTitle.textContent.trim()) {
                        const menuText = activeItem.querySelector('span.font-medium').textContent;
                        pageTitle.textContent = menuText;
                    }
                }
            }
        }
        
        // Toggle sidebar untuk mobile
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        });
        
        // Tutup sidebar untuk mobile
        document.getElementById('closeSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
        
        // Tutup sidebar ketika overlay diklik
        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
        
        // Tutup sidebar ketika ukuran layar berubah menjadi desktop
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.add('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
            }
        });
        
        // Update status menu aktif ketika hash berubah
        window.addEventListener('hashchange', setActiveMenu);
        
        // Inisialisasi status menu aktif ketika halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            setActiveMenu();
        });
        
        // Tambahkan event listener untuk semua link sidebar
        document.querySelectorAll('aside a[data-menu]').forEach(link => {
            link.addEventListener('click', function() {
                // Untuk mobile, tutup sidebar setelah memilih menu
                if (window.innerWidth < 1024) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>