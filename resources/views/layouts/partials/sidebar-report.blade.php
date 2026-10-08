{{-- =====================================================================
     Menu Report (sidebar). Di-include dari layouts.sidebar:
     - Super Admin & General Manager: setelah menu Finance.
     - Admin: paling bawah, di bawah User Management (revisi klien).

     Isi per role:
     - Kategori Transaksi        : Super Admin & Admin
     - Laporan Akhir             : semua role
     - Laporan Keuangan Proyek   : Super Admin & General Manager
                                   (Admin: dipindah ke menu Finance)
     - Rekap Proyek & Kas Kantor : Admin (dipindah dari Finance › Rekap)
     - Reimbursement             : Super Admin & Admin (dipindah dari Finance)

     Variabel dari parent: $isSuperAdmin, $isAdmin, $isGeneralManager,
     $reimburseBadgeCount.
     ===================================================================== --}}
@php
    $isRecapProyekPage = request()->is('recap-proyek*') && !request()->is('recap-proyek*/laporan-keuangan*');
    $reportOpen = request()->is('transaction-category*')
        || request()->is('report/final*')
        || (!$isAdmin && (request()->routeIs('project-financial-report.*') || request()->is('recap-proyek*/laporan-keuangan*')))
        || ($isAdmin && ($isRecapProyekPage || request()->is('recap-expense*')))
        || (!$isGeneralManager && request()->is('reimburse*'));
@endphp
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

    <ul id="laporanDropdown" class="ml-8 mt-2 space-y-1 {{ $reportOpen ? '' : 'hidden' }}">

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

        {{-- Laporan Keuangan Proyek (Admin: ada di menu Finance) --}}
        @if (!$isAdmin)
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
        @endif

        {{-- Admin: Rekap Proyek & Kas Kantor (dulu Finance › Rekap) --}}
        @if ($isAdmin)
        <li>
            <a href="{{ url('/recap-proyek') }}"
                class="flex items-center px-4 py-2 rounded-lg transition-colors duration-200 group
                    {{ $isRecapProyekPage ? 'bg-primary-light text-primary' : 'text-text-label hover:bg-primary-light hover:text-primary' }}">
                <i
                    class="fas fa-file-invoice w-4
                    {{ $isRecapProyekPage ? 'text-primary' : 'text-text-tertiary group-hover:text-primary' }}">
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
                <span class="ml-3 text-sm font-medium">Kas Kantor</span>
            </a>
        </li>
        @endif

        {{-- Reimbursement: Super Admin & Admin (dulu di menu Finance) --}}
        @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
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
        @endif
    </ul>
</li>
