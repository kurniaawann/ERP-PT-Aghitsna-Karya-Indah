{{-- =====================================================================
     Sidebar (gaya Filament). Di-include dari layouts.app.
     - Desktop (lg+): statis di kiri; header logo putih sejajar topbar,
       navigasi menyatu dengan latar halaman.
     - Mobile: laci (drawer) dibuka #toggleSidebar (topbar), ditutup
       #closeSidebar / overlay / Escape.
     Item & grup menu: x-fi.sidebar.item / x-fi.sidebar.group. Grup terbuka
     otomatis bila salah satu menunya sedang dibuka; toggleDropdown(id)
     membuka/menutup grup secara manual.
     ===================================================================== --}}
<aside id="sidebar"
    class="fixed inset-y-0 start-0 z-50 flex w-64 shrink-0 -translate-x-full flex-col bg-white shadow-xl ring-1 ring-gray-950/5 transition-transform duration-300 ease-in-out lg:static lg:z-0 lg:translate-x-0 lg:bg-transparent lg:shadow-none lg:ring-0">

    {{-- Header Sidebar: logo + nama perusahaan (tinggi sama dengan topbar) --}}
    <div class="flex h-16 shrink-0 items-center gap-x-3 bg-white px-4 ring-1 ring-gray-950/5 lg:shadow-sm">
        <a href="{{ url(auth()->user()?->getHomeRoute() ?? '/dashboard') }}"
            class="flex min-w-0 flex-1 items-center gap-x-3 rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-primary/50">
            <img src="{{ asset('images/logo.jpeg') }}" alt="PT Aghitsna Karya Indah"
                class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-gray-950/10">
            <span class="text-sm font-bold leading-5 tracking-tight text-gray-950">PT Aghitsna Karya Indah</span>
        </a>

        <button id="closeSidebar" type="button" title="Tutup menu"
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 outline-none transition duration-75 hover:bg-gray-100 hover:text-gray-600 focus-visible:ring-2 focus-visible:ring-primary/50 lg:hidden">
            <x-fi.icon name="x-mark" class="h-5 w-5" />
            <span class="sr-only">Tutup menu</span>
        </button>
    </div>

    {{-- Navigasi --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-5" aria-label="Menu utama">
        <ul class="flex flex-col gap-y-1">

            @php
                $user = auth()->user();
                $isSuperAdmin = $user?->isSuperAdmin();
                $isAdmin = $user?->isAdmin();
                $isGeneralManager = $user?->isGeneralManager();

                // Badge notifikasi Reimbursement — hanya tampil jika ada
                // aktivitas baru SEJAK user terakhir membuka halaman
                // (reimburse_seen_at), lalu hilang saat menu diklik:
                // - Admin      : pengajuan baru (status draft / menunggu
                //                disetujui) yang dibuat setelah terakhir dibuka.
                // - Super Admin: perubahan status disetujui/ditolak
                //                (status_changed_at) setelah terakhir dibuka.
                $reimburseBadgeCount = 0;
                if ($user && ($user->isAdmin() || $user->isSuperAdmin())) {
                    $seenAt = $user->reimburse_seen_at ?? now()->subYears(10);

                    $reimburseBadgeQuery = \App\Models\Finance\Reimburse::query();

                    if ($user->isAdmin()) {
                        $reimburseBadgeCount = (clone $reimburseBadgeQuery)
                            ->where('status', 'draft')
                            ->where('created_at', '>', $seenAt)
                            ->count();
                    } else {
                        $reimburseBadgeCount = (clone $reimburseBadgeQuery)
                            ->whereIn('status', ['approved', 'rejected'])
                            ->where('status_changed_at', '>', $seenAt)
                            ->count();
                    }
                }
            @endphp

            {{-- Dashboard --}}
            @if (!$isGeneralManager)
                <x-fi.sidebar.item :href="url('/dashboard')" icon="home" :active="request()->is('dashboard')">
                    Dashboard
                </x-fi.sidebar.item>
            @endif

            {{-- Inventory --}}
            @if (!$isAdmin && !$isGeneralManager)
                <x-fi.sidebar.group id="inventoryDropdown" icon="archive-box" label="Inventory"
                    :open="(request()->is('item') || request()->is('item/*')) || request()->is('do-semen*') || request()->is('stock-in*') || request()->is('stock-out*') || request()->is('item-return*')">
                    <x-fi.sidebar.item :href="url('/item')" icon="cube"
                        :active="(request()->is('item') || request()->is('item/*')) && !request()->is('item-return*')">
                        Data Barang
                    </x-fi.sidebar.item>
                    <x-fi.sidebar.item :href="route('cement-do.index')" icon="truck" :active="request()->is('do-semen*')">
                        DO Semen
                    </x-fi.sidebar.item>
                    <x-fi.sidebar.item :href="route('stock-in.index')" icon="inbox-arrow-down" :active="request()->is('stock-in*')">
                        Barang Masuk
                    </x-fi.sidebar.item>
                    <x-fi.sidebar.item :href="route('stock-out.index')" icon="arrow-up-tray" :active="request()->is('stock-out*')">
                        Barang Keluar
                    </x-fi.sidebar.item>
                    <x-fi.sidebar.item :href="route('item-return.index')" icon="arrow-uturn-left" :active="request()->is('item-return*')">
                        Pengembalian Barang
                    </x-fi.sidebar.item>
                </x-fi.sidebar.group>
            @endif

            {{-- Finance --}}
            @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
                @if ($isAdmin)
                    {{-- Admin: Invoice, Rekening Pembayaran & Laporan Keuangan Proyek
                         (Rekap Proyek, Kas Kantor & Reimbursement pindah ke menu Report) --}}
                    <x-fi.sidebar.group id="invoiceDropdown" icon="banknotes" label="Finance"
                        :open="request()->is('proyek-invoice*') || request()->is('payment-accounts*') || request()->routeIs('project-financial-report.*') || request()->is('recap-proyek*/laporan-keuangan*')">
                        <x-fi.sidebar.item :href="url('/proyek-invoice')" icon="document-text" :active="request()->is('proyek-invoice*')">
                            Invoice
                        </x-fi.sidebar.item>
                        <x-fi.sidebar.item :href="url('/payment-accounts')" icon="building-library" :active="request()->is('payment-accounts*')">
                            Rekening Pembayaran
                        </x-fi.sidebar.item>
                        <x-fi.sidebar.item :href="route('project-financial-report.index')" icon="document-currency-dollar"
                            :active="request()->routeIs('project-financial-report.*')">
                            Laporan Keuangan Proyek
                        </x-fi.sidebar.item>
                    </x-fi.sidebar.group>
                @else
                    {{-- Super Admin: Full Finance menu --}}
                    <x-fi.sidebar.group id="invoiceDropdown" icon="banknotes" label="Finance"
                        :open="request()->is('item-invoice*') || request()->is('alumunium-invoice*') || request()->is('proyek-invoice*') || request()->is('purchase-invoice*') || request()->is('payment-accounts*') || request()->is('rekap*') || request()->is('recap-sales*') || request()->is('recap-alumunium*') || request()->is('recap-proyek*') || request()->is('recap-expense*')">
                        {{-- Invoice: satu menu → halaman bertab (Barang | Alumunium | Proyek), seperti Surat Menyurat --}}
                        <x-fi.sidebar.item :href="route('item-invoice.index')" icon="document-text"
                            :active="request()->is('item-invoice*') || request()->is('alumunium-invoice*') || request()->is('proyek-invoice*')">
                            Invoice
                        </x-fi.sidebar.item>
                        <x-fi.sidebar.item :href="url('/purchase-invoice')" icon="shopping-cart" :active="request()->is('purchase-invoice*')">
                            Faktur Pembelian
                        </x-fi.sidebar.item>
                        <x-fi.sidebar.item :href="url('/payment-accounts')" icon="building-library" :active="request()->is('payment-accounts*')">
                            Rekening Pembayaran
                        </x-fi.sidebar.item>
                        <x-fi.sidebar.item :href="url('/rekap')" icon="chart-bar" :active="request()->is('rekap*')">
                            Rekap
                        </x-fi.sidebar.item>
                    </x-fi.sidebar.group>
                @endif
            @endif

            {{-- Report (Admin: paling bawah, di bawah User Management) --}}
            @if (!$isAdmin)
                @include('layouts.partials.sidebar-report')
            @endif

            {{-- Human Resource --}}
            @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
                <x-fi.sidebar.group id="sdmDropdown" icon="user-group" label="Human Resource"
                    :open="request()->is('employee*') || request()->is('attendance*') || request()->is('overtime*') || request()->is('payroll*') || request()->is('kasbon*') || request()->is('division*') || request()->is('tunjangan*') || request()->is('executive*')">
                    <x-fi.sidebar.item :href="url('/employee')" icon="identification" :active="request()->is('employee*')">
                        Data Karyawan
                    </x-fi.sidebar.item>
                    <x-fi.sidebar.item :href="url('/executive')" icon="pencil-square" :active="request()->is('executive*')">
                        Data Penandatangan
                    </x-fi.sidebar.item>
                    <x-fi.sidebar.item :href="url('/attendance')" icon="calendar-days" :active="request()->is('attendance*')">
                        Absensi
                    </x-fi.sidebar.item>
                    <x-fi.sidebar.item :href="url('/division')" icon="rectangle-group" :active="request()->is('division*')">
                        Divisi
                    </x-fi.sidebar.item>
                    <x-fi.sidebar.item :href="url('/payroll')" icon="credit-card" :active="request()->is('payroll*')">
                        Payroll
                    </x-fi.sidebar.item>

                    @if ($isAdmin)
                        {{-- Admin: satu menu → halaman bertab Lembur | Kasbon (x-sdm.allowance-tabs) --}}
                        <x-fi.sidebar.item :href="url('/overtime')" icon="receipt-percent"
                            :active="request()->is('overtime*') || request()->is('kasbon*')">
                            Tunjangan & Potongan
                        </x-fi.sidebar.item>
                    @else
                        {{-- Super Admin: submenu Lembur & Kasbon --}}
                        <x-fi.sidebar.group id="tunjanganDropdown" icon="receipt-percent" label="Tunjangan & Potongan"
                            :open="request()->is('overtime*') || request()->is('kasbon*')">
                            <x-fi.sidebar.item :href="url('/overtime')" icon="clock" :active="request()->is('overtime*')">
                                Lembur
                            </x-fi.sidebar.item>
                            <x-fi.sidebar.item :href="url('/kasbon')" icon="currency-dollar" :active="request()->is('kasbon*')">
                                Kasbon
                            </x-fi.sidebar.item>
                        </x-fi.sidebar.group>
                    @endif
                </x-fi.sidebar.group>
            @endif

            {{-- Administration --}}
            @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
                @if ($isAdmin)
                    {{-- Admin: Penawaran Harga langsung ke Penawaran Proyek (tanpa submenu); Bukti Kas Keluar jadi tab Surat Menyurat --}}
                    <x-fi.sidebar.group id="administrasiDropdown" icon="folder-open" label="Administration"
                        :open="request()->is('cash-out-proof*') || request()->is('project-quotation*') || request()->is('surat-menyurat*') || request()->is('rab*')">
                        <x-fi.sidebar.item :href="url('/surat-menyurat')" icon="envelope"
                            :active="request()->is('surat-menyurat*') || request()->is('cash-out-proof*')">
                            Surat Menyurat
                        </x-fi.sidebar.item>
                        <x-fi.sidebar.item :href="url('/project-quotation')" icon="clipboard-document-check" :active="request()->is('project-quotation*')">
                            Penawaran Harga
                        </x-fi.sidebar.item>
                        <x-fi.sidebar.item :href="url('/rab')" icon="calculator" :active="request()->is('rab*')">
                            RAB
                        </x-fi.sidebar.item>
                    </x-fi.sidebar.group>
                @else
                    {{-- Super Admin: Full Administration menu (Bukti Kas Keluar jadi tab Surat Menyurat) --}}
                    <x-fi.sidebar.group id="administrasiDropdown" icon="folder-open" label="Administration"
                        :open="request()->is('cash-out-proof*') || request()->is('aluminium-quotation*') || request()->is('project-quotation*') || request()->is('surat-menyurat*') || request()->is('rab*')">
                        <x-fi.sidebar.item :href="url('/surat-menyurat')" icon="envelope"
                            :active="request()->is('surat-menyurat*') || request()->is('cash-out-proof*')">
                            Surat Menyurat
                        </x-fi.sidebar.item>
                        {{-- Penawaran Harga: satu menu → halaman bertab (Alumunium | Proyek), seperti Surat Menyurat --}}
                        <x-fi.sidebar.item :href="route('aluminium-quotation.index')" icon="clipboard-document-check"
                            :active="request()->is('aluminium-quotation*') || request()->is('project-quotation*')">
                            Penawaran Harga
                        </x-fi.sidebar.item>
                        <x-fi.sidebar.item :href="url('/rab')" icon="calculator" :active="request()->is('rab*')">
                            RAB
                        </x-fi.sidebar.item>
                    </x-fi.sidebar.group>
                @endif
            @endif

            {{-- User Management (Super Admin + Admin) --}}
            @if ($isSuperAdmin || $isAdmin)
                <x-fi.sidebar.item :href="route('user-management.index')" icon="shield-check" :active="request()->is('user-management*')">
                    User Management
                </x-fi.sidebar.item>
            @endif

            {{-- Report untuk Admin: paling bawah (revisi klien) --}}
            @if ($isAdmin)
                @include('layouts.partials.sidebar-report')
            @endif

        </ul>
    </nav>
</aside>

{{-- Overlay untuk mobile --}}
<div id="sidebarOverlay" class="fixed inset-0 z-40 hidden bg-gray-950/50 lg:hidden"></div>

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

        // Escape menutup sidebar mobile (hanya saat sedang terbuka)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay && !overlay.classList.contains('hidden')) {
                closeSidebar();
            }
        });

        // Tutup sidebar saat resize ke desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) { // lg breakpoint
                closeSidebar();
            }
        });

        // Menu aktif yang berada di bawah (mis. Report admin) tetap terlihat:
        // gulung navigasi sidebar saja (bukan halaman)
        const nav = sidebar ? sidebar.querySelector('nav') : null;
        const activeItem = nav ? nav.querySelector('[aria-current="page"]') : null;
        if (activeItem) {
            const overflow = activeItem.getBoundingClientRect().bottom - nav.getBoundingClientRect().bottom;
            if (overflow > 0) nav.scrollTop += overflow + 16;
        }
    });

    // Function untuk toggle dropdown (grup menu sidebar)
    function toggleDropdown(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        const icon = document.getElementById(dropdownId + 'Icon');
        const button = document.querySelector('[aria-controls="' + dropdownId + '"]');

        if (dropdown) {
            const isOpen = dropdown.classList.toggle('hidden') === false;

            // Panah berputar ke atas saat grup terbuka
            if (icon) icon.classList.toggle('rotate-180', isOpen);
            if (button) button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }
</script>
