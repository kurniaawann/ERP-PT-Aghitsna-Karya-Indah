<!-- Sidebar -->
<aside id="sidebar"
    class="bg-surface-base shadow-lg w-64 fixed lg:static inset-y-0 left-0 z-50 transform 
           -translate-x-full lg:translate-x-0 flex flex-col transition-transform duration-300 ease-in-out">

    <!-- Header Sidebar -->
    <div class="p-4 border-b border-border flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <div
                class="w-10 h-10 bg-surface-base rounded-full flex items-center justify-center overflow-hidden border border-border">
                <img src="{{ asset('images/logo.jpeg') }}" alt="PT Aghitsna Karya Indah"
                    class="w-full h-full object-cover">
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
            @endif

            {{-- Inventory Dropdown --}}
            @if (!$isAdmin && !$isGeneralManager)
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
                <ul id="inventoryDropdown"
                    class="ml-8 mt-2 space-y-1 {{ (request()->is('item') || request()->is('item/*')) || request()->is('do-semen*') || request()->is('stock-in*') || request()->is('stock-out*') || request()->is('item-return*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/item') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ (request()->is('item') || request()->is('item/*')) && !request()->is('item-return*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-boxes w-4 
                                {{ (request()->is('item') || request()->is('item/*')) && !request()->is('item-return*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Data Barang</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cement-do.index') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('do-semen*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-truck w-4 
                                {{ request()->is('do-semen*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">DO Semen</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('stock-in.index') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('stock-in*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-truck-loading w-4 
                                {{ request()->is('stock-in*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Barang Masuk</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('stock-out.index') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('stock-out*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-sign-out-alt w-4 
                                {{ request()->is('stock-out*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Barang Keluar</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('item-return.index') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('item-return*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-reply-all w-4 
                                {{ request()->is('item-return*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Pengembalian Barang</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endif

            {{-- Finance Dropdown --}}
            @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
            <li>
                <button onclick="toggleDropdown('invoiceDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-file-invoice w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Finance</span>
                    </div>

                    <i id="invoiceDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                {{-- Submenu --}}
                @if ($isAdmin)
                {{-- Admin: Invoice submenu with 4 new items --}}
                <ul id="invoiceDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('proyek-invoice*') || request()->is('payment-accounts*') || request()->is('recap-proyek*') || request()->is('recap-expense*') || request()->is('reimburse*') || request()->is('payment-proofs*') ? '' : 'hidden' }}">
                    <li>
                        <a href="{{ url('/proyek-invoice') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('proyek-invoice*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-file-contract w-4 
                                {{ request()->is('proyek-invoice*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Invoice</span>
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
                        <button onclick="toggleDropdown('adminRekapDropdown')"
                            class="flex items-center justify-between w-full px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('recap-proyek*') || request()->is('recap-expense*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <div class="flex items-center">
                                <i
                                    class="fas fa-chart-bar w-4
                                    {{ request()->is('recap-proyek*') || request()->is('recap-expense*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                </i>
                                <span class="ml-3 text-sm font-medium">Rekap</span>
                            </div>

                            <i id="adminRekapDropdownIcon"
                                class="fas fa-chevron-down text-xs transition-transform duration-200
                                {{ request()->is('recap-proyek*') || request()->is('recap-expense*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                        </button>

                        <ul id="adminRekapDropdown"
                            class="ml-6 mt-1 space-y-1 {{ request()->is('recap-proyek*') || request()->is('recap-expense*') ? '' : 'hidden' }}">
                            <li>
                                <a href="{{ url('/recap-proyek') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('recap-proyek*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-invoice w-4 
                                        {{ request()->is('recap-proyek*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Rekap Proyek</span>
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
                    <li>
                        <a href="{{ url('/reimburse') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('reimburse*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-receipt w-4 
                                {{ request()->is('reimburse*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Reimbursement</span>
                            @if ($reimburseBadgeCount > 0)
                                <span
                                    class="inline-flex items-center justify-center ml-auto min-w-[20px] h-5 px-1.5 rounded-full text-xs font-semibold bg-error text-white">
                                    {{ $reimburseBadgeCount }}
                                </span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('payment-proofs.index') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('payment-proofs*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-image w-4 
                                {{ request()->is('payment-proofs*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Bukti Pembayaran</span>
                        </a>
                    </li>
                </ul>
                @else
                {{-- Super Admin: Full Finance menu --}}
                <ul id="invoiceDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('item-invoice*') || request()->is('alumunium-invoice*') || request()->is('proyek-invoice*') || request()->is('payment-proofs*') || request()->is('purchase-invoice*') || request()->is('payment-accounts*') || request()->is('recap-sales*') || request()->is('recap-alumunium*') || request()->is('recap-proyek*') || request()->is('recap-expense*') || request()->is('reimburse*') ? '' : 'hidden' }}">
                    <li>
                        <button onclick="toggleDropdown('invoiceMasterDropdown')"
                            class="flex items-center justify-between w-full px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('item-invoice*') || request()->is('alumunium-invoice*') || request()->is('proyek-invoice*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <div class="flex items-center">
                                <i
                                    class="fas fa-file-invoice w-4
                                    {{ request()->is('item-invoice*') || request()->is('alumunium-invoice*') || request()->is('proyek-invoice*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                </i>
                                <span class="ml-3 text-sm font-medium">Invoice</span>
                            </div>

                            <i id="invoiceMasterDropdownIcon"
                                class="fas fa-chevron-down text-xs transition-transform duration-200
                                {{ request()->is('item-invoice*') || request()->is('alumunium-invoice*') || request()->is('proyek-invoice*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                        </button>

                        <ul id="invoiceMasterDropdown"
                            class="ml-6 mt-1 space-y-1 {{ request()->is('item-invoice*') || request()->is('alumunium-invoice*') || request()->is('proyek-invoice*') ? '' : 'hidden' }}">
                            <li>
                                <a href="{{ url('/item-invoice') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('item-invoice*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-contract w-4 
                                        {{ request()->is('item-invoice*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Invoice Barang</span>
                                </a>
                            </li>
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
                                    <span class="ml-3 text-sm font-medium">{{ auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek' }}</span>
                                </a>
                            </li>
                            </ul>
                    </li>
                    <li>
                        <a href="{{ url('/purchase-invoice') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('purchase-invoice*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-shopping-cart w-4 
                                {{ request()->is('purchase-invoice*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Faktur Pembelian</span>
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
                        <button onclick="toggleDropdown('rekapDropdown')"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('recap-sales*') || request()->is('recap-alumunium*') || request()->is('recap-proyek*') || request()->is('recap-expense*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <div class="flex items-center">
                                <i
                                    class="fas fa-chart-bar w-4
                                    {{ request()->is('recap-sales*') || request()->is('recap-alumunium*') || request()->is('recap-proyek*') || request()->is('recap-expense*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                </i>
                                <span class="ml-3 text-sm font-medium">Rekap</span>
                            </div>

                            <i id="rekapDropdownIcon"
                                class="fas fa-chevron-down text-xs transition-transform duration-200
                                {{ request()->is('recap-sales*') || request()->is('recap-alumunium*') || request()->is('recap-proyek*') || request()->is('recap-expense*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                        </button>

                        <ul id="rekapDropdown"
                            class="ml-6 mt-1 space-y-1 {{ request()->is('recap-sales*') || request()->is('recap-alumunium*') || request()->is('recap-proyek*') || request()->is('recap-expense*') ? '' : 'hidden' }}">
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
                                <a href="{{ url('/recap-alumunium') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('recap-alumunium*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-invoice-dollar w-4 
                                        {{ request()->is('recap-alumunium*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Rekap Alumunium</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/recap-proyek') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('recap-proyek*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-invoice w-4 
                                        {{ request()->is('recap-proyek*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Rekap Proyek</span>
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
                    <li>
                        <a href="{{ url('/reimburse') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('reimburse*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-receipt w-4 
                                {{ request()->is('reimburse*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Reimbursement</span>
                            @if ($reimburseBadgeCount > 0)
                                <span
                                    class="inline-flex items-center justify-center ml-auto min-w-[20px] h-5 px-1.5 rounded-full text-xs font-semibold bg-error text-white">
                                    {{ $reimburseBadgeCount }}
                                </span>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('payment-proofs.index') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('payment-proofs*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-image w-4 
                                {{ request()->is('payment-proofs*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Bukti Pembayaran</span>
                        </a>
                    </li>
                </ul>
                @endif
            </li>
            @endif

            {{-- Report Dropdown (Laporan Akhir untuk semua role yang punya akses laporan) --}}
            <li>
                <button onclick="toggleDropdown('laporanDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-chart-line w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Report</span>
                    </div>

                    <i id="laporanDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                <ul id="laporanDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('transaction-category*') || request()->is('report/final*') || request()->is('report/project-financial-report*') || request()->is('recap-proyek*/laporan-keuangan*') ? '' : 'hidden' }}">

                    {{-- Kategori Transaksi: hanya Super Admin & Admin (bukan General Manager) --}}
                    @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
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
                    @endif

                    {{-- Laporan Akhir: gabungan Laporan Stok, Penjualan, Pengeluaran --}}
                    <li>
                        <a href="{{ route('report.final') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('report/final*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-file-alt w-4 
                                {{ request()->is('report/final*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Laporan Akhir</span>
                        </a>
                    </li>

                    {{-- Laporan Keuangan Proyek: halaman daftar laporan terpisah --}}
                    <li>
                        <a href="{{ route('project-financial-report.index') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->routeIs('project-financial-report.*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-file-invoice-dollar w-4 
                                {{ request()->routeIs('project-financial-report.*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Laporan Keuangan Proyek</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- SDM (Sumber Daya Manusia) Dropdown --}}
            @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
            <li>
                <button onclick="toggleDropdown('sdmDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-users w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Human Resource</span>
                    </div>

                    <i id="sdmDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                {{-- Submenu --}}
                <ul id="sdmDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('employee*') || request()->is('attendance*') || request()->is('overtime*') || request()->is('payroll*') || request()->is('kasbon*') || request()->is('division*') || request()->is('tunjangan*') || request()->is('executive*') ? '' : 'hidden' }}">
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
                        <a href="{{ url('/executive') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('executive*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-signature w-4 
                                {{ request()->is('executive*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Data Petinggi</span>
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
                        <a href="{{ url('/division') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('division*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-sitemap w-4 
                                {{ request()->is('division*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">Divisi</span>
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

                    <!-- Tunjangan dan Potongan Dropdown -->
                    <li>
                        <button onclick="toggleDropdown('tunjanganDropdown')"
                            class="flex items-center justify-between w-full px-4 py-2 rounded-lg transition-colors duration-200 group text-text-label hover:bg-primary-light hover:text-primary">
                            <div class="flex items-center">
                                <i class="fas fa-money-bill-wave w-4 text-text-tertiary group-hover:text-primary"></i>
                                <span class="ml-3 text-sm font-medium">Tunjangan & Potongan</span>
                            </div>
                            <i id="tunjanganDropdownIcon"
                                class="fas fa-chevron-down text-xs transition-transform duration-200 text-text-tertiary group-hover:text-primary"></i>
                        </button>

                        <!-- Tunjangan & Potongan Submenu -->
                        <ul id="tunjanganDropdown"
                            class="ml-8 mt-2 space-y-1 {{ request()->is('overtime*') || request()->is('kasbon*') ? '' : 'hidden' }}">
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
                        </ul>
                    </li>
                </ul>
            </li>
            @endif

            {{-- Administrasi Dropdown --}}
            @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
            <li>
                <button onclick="toggleDropdown('administrasiDropdown')"
                    class="flex items-center justify-between w-full px-4 py-3 rounded-lg transition-colors duration-200 group text-text-primary hover:bg-primary-light hover:text-primary">

                    <div class="flex items-center">
                        <i class="fas fa-folder-open w-5 text-text-tertiary group-hover:text-primary">
                        </i>
                        <span class="ml-3 font-medium">Administration</span>
                    </div>

                    <i id="administrasiDropdownIcon"
                        class="fas fa-chevron-down text-sm transition-transform duration-200 text-text-tertiary group-hover:text-primary">
                    </i>
                </button>

                @if ($isAdmin)
                {{-- Admin: Penawaran Aluminium hidden, Penawaran Proyek renamed to "Penawaran" --}}
                <ul id="administrasiDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('cash-out-proof*') || request()->is('project-quotation*') || request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') || request()->is('rab*') ? '' : 'hidden' }}">
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
                        <button onclick="toggleDropdown('suratMenyuratDropdown')"
                            class="flex items-center justify-between w-full px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <div class="flex items-center">
                                <i
                                    class="fas fa-envelope w-4
                                    {{ request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                </i>
                                <span class="ml-3 text-sm font-medium">Surat Menyurat</span>
                            </div>

                            <i id="suratMenyuratDropdownIcon"
                                class="fas fa-chevron-down text-xs transition-transform duration-200
                                {{ request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                        </button>

                        <ul id="suratMenyuratDropdown"
                            class="ml-6 mt-1 space-y-1 {{ request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') ? '' : 'hidden' }}">
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
                                <a href="{{ url('/nota-administrasi') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('nota-administrasi*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-invoice w-4 
                                        {{ request()->is('nota-administrasi*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Nota</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/delivery-note') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('delivery-note*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-truck w-4 
                                        {{ request()->is('delivery-note*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Surat Jalan</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/surat-perintah-kerja') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('surat-perintah-kerja*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-contract w-4 
                                        {{ request()->is('surat-perintah-kerja*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Surat Perintah Kerja</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <button onclick="toggleDropdown('penawaranHargaDropdown')"
                            class="flex items-center justify-between w-full px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('project-quotation*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <div class="flex items-center">
                                <i
                                    class="fas fa-file-contract w-4
                                    {{ request()->is('project-quotation*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                </i>
                                <span class="ml-3 text-sm font-medium">Penawaran Harga</span>
                            </div>

                            <i id="penawaranHargaDropdownIcon"
                                class="fas fa-chevron-down text-xs transition-transform duration-200
                                {{ request()->is('project-quotation*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                        </button>

                        <ul id="penawaranHargaDropdown"
                            class="ml-6 mt-1 space-y-1 {{ request()->is('project-quotation*') ? '' : 'hidden' }}">
                            <li>
                                <a href="{{ url('/project-quotation') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('project-quotation*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-clipboard-list w-4
                                        {{ request()->is('project-quotation*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Penawaran</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="{{ url('/rab') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('rab*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-calculator w-4
                                {{ request()->is('rab*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">RAB</span>
                        </a>
                    </li>
                </ul>
                @else
                {{-- Super Admin: Full Administration menu --}}
                <ul id="administrasiDropdown"
                    class="ml-8 mt-2 space-y-1 {{ request()->is('cash-out-proof*') || request()->is('aluminium-quotation*') || request()->is('project-quotation*') || request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') || request()->is('rab*') ? '' : 'hidden' }}">
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
                        <button onclick="toggleDropdown('suratMenyuratDropdown')"
                            class="flex items-center justify-between w-full px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <div class="flex items-center">
                                <i
                                    class="fas fa-envelope w-4
                                    {{ request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                </i>
                                <span class="ml-3 text-sm font-medium">Surat Menyurat</span>
                            </div>

                            <i id="suratMenyuratDropdownIcon"
                                class="fas fa-chevron-down text-xs transition-transform duration-200
                                {{ request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                        </button>

                        <ul id="suratMenyuratDropdown"
                            class="ml-6 mt-1 space-y-1 {{ request()->is('document-receipt*') || request()->is('kwintansi*') || request()->is('nota-administrasi*') || request()->is('delivery-note*') || request()->is('surat-perintah-kerja*') ? '' : 'hidden' }}">
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
                                <a href="{{ url('/nota-administrasi') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('nota-administrasi*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-invoice w-4 
                                        {{ request()->is('nota-administrasi*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Nota</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/delivery-note') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('delivery-note*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-truck w-4 
                                        {{ request()->is('delivery-note*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Surat Jalan</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/surat-perintah-kerja') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('surat-perintah-kerja*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-contract w-4 
                                        {{ request()->is('surat-perintah-kerja*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Surat Perintah Kerja</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <button onclick="toggleDropdown('penawaranHargaDropdown')"
                            class="flex items-center justify-between w-full px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('aluminium-quotation*') || request()->is('project-quotation*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <div class="flex items-center">
                                <i
                                    class="fas fa-file-contract w-4
                                    {{ request()->is('aluminium-quotation*') || request()->is('project-quotation*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                </i>
                                <span class="ml-3 text-sm font-medium">Penawaran Harga</span>
                            </div>

                            <i id="penawaranHargaDropdownIcon"
                                class="fas fa-chevron-down text-xs transition-transform duration-200
                                {{ request()->is('aluminium-quotation*') || request()->is('project-quotation*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                        </button>

                        <ul id="penawaranHargaDropdown"
                            class="ml-6 mt-1 space-y-1 {{ request()->is('aluminium-quotation*') || request()->is('project-quotation*') ? '' : 'hidden' }}">
                            <li>
                                <a href="{{ url('/aluminium-quotation') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('aluminium-quotation*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-file-contract w-4
                                        {{ request()->is('aluminium-quotation*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Penawaran Alumunium</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/project-quotation') }}"
                                    class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                        {{ request()->is('project-quotation*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                                    <i
                                        class="fas fa-clipboard-list w-4
                                        {{ request()->is('project-quotation*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                                    </i>
                                    <span class="ml-3 text-sm font-medium">Penawaran Proyek</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="{{ url('/rab') }}"
                            class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                                {{ request()->is('rab*') ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                            <i
                                class="fas fa-calculator w-4
                                {{ request()->is('rab*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                            </i>
                            <span class="ml-3 text-sm font-medium">RAB</span>
                        </a>
                    </li>
                </ul>
                @endif
            </li>
            @endif

            {{-- User Management (Super Admin + Admin) --}}
            @if ($isSuperAdmin || $isAdmin)
                <li>
                    <a href="{{ route('user-management.index') }}"
                        class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 group
                            {{ request()->is('user-management*') ? 'bg-primary-light text-primary' : 'text-text-primary hover:bg-primary-light hover:text-primary' }}">
                        <i
                            class="fas fa-user-shield w-5
                            {{ request()->is('user-management*') ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
                        </i>
                        <span class="ml-3 font-medium">User Management</span>
                    </a>
                </li>
            @endif

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
