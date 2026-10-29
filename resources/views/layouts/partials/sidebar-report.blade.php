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

<x-fi.sidebar.group id="laporanDropdown" icon="presentation-chart-line" label="Report" :open="$reportOpen">

    {{-- Kategori Transaksi: hanya Super Admin & Admin (bukan General Manager) --}}
    @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
        <x-fi.sidebar.item :href="url('/transaction-category')" icon="tag" :active="request()->is('transaction-category*')">
            Kategori Transaksi
        </x-fi.sidebar.item>
    @endif

    {{-- Laporan Akhir: gabungan Laporan Stok, Penjualan, Pengeluaran --}}
    <x-fi.sidebar.item :href="route('report.final')" icon="document-chart-bar" :active="request()->is('report/final*')">
        Laporan Akhir
    </x-fi.sidebar.item>

    {{-- Laporan Keuangan Proyek (Admin: ada di menu Finance) --}}
    @if (!$isAdmin)
        <x-fi.sidebar.item :href="route('project-financial-report.index')" icon="document-currency-dollar"
            :active="request()->routeIs('project-financial-report.*')">
            Laporan Keuangan Proyek
        </x-fi.sidebar.item>
    @endif

    {{-- Admin: Rekap Proyek & Kas Kantor (dulu Finance › Rekap) --}}
    @if ($isAdmin)
        <x-fi.sidebar.item :href="url('/recap-proyek')" icon="clipboard-document-list" :active="$isRecapProyekPage">
            Rekap Proyek
        </x-fi.sidebar.item>
        <x-fi.sidebar.item :href="url('/recap-expense')" icon="wallet" :active="request()->is('recap-expense*')">
            Kas Kantor
        </x-fi.sidebar.item>
    @endif

    {{-- Reimbursement: Super Admin & Admin (dulu di menu Finance) --}}
    @if (!$isGeneralManager && ($isSuperAdmin || $isAdmin))
        <x-fi.sidebar.item :href="url('/reimburse')" icon="receipt-refund" :active="request()->is('reimburse*')"
            :badge="$reimburseBadgeCount > 0 ? $reimburseBadgeCount : null">
            Reimbursement
        </x-fi.sidebar.item>
    @endif
</x-fi.sidebar.group>
