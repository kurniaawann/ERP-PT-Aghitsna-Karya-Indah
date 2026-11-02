{{-- =====================================================================
     Halaman: Data Payroll (payroll, gaya Filament)
     Tujuan: Halaman utama pengelolaan payroll karyawan yang ber-tab:
             1. "Data Payroll" — payroll mingguan (paginasi per grup proyek +
                minggu) dengan pencarian & filter (bulan, tahun, minggu,
                proyek), aksi massal (Bayar/Hapus), Generate, dan ekspor
                (Excel/PDF; aktif setelah filter proyek dipilih).
             2. "Slip Gaji" — slip gaji karyawan bulanan (partial
                pages.sdm.partials.salary-slip-content), aktif saat
                ?tab=salary-slip. Tab tampil untuk role admin & superadmin.

     Data dari PayrollController@index (bergantung tab aktif):
     - Tab payroll: $payrollGroups, $currentPayrolls, $search, $month, $year,
       $weekNumber, $projectName, $projects, $executives
     - Tab salary-slip: $slips, $search, $month, $year, $executives,
       $eligibleEmployees, $filterMonth, $filterYear
     - $tab : tab aktif (payroll|salary-slip)

     Komponen yang di-include (tab payroll):
     - components.sdm.payroll.table          : toolbar + tabel grup + paginasi
     - components.sdm.payroll.generate-modal : modal generate payroll
     - components.sdm.payroll.edit-modal     : modal edit payroll draft (loop)
     - components.sdm.payroll.detail-modal   : modal detail payroll (loop)
     - x-fi.modal (deleteModal / bulkPayModal), x-fi.delete-row-modal

     Alur logika:
     - Dropdown minggu diisi AJAX (route payroll.get-weeks).
     - Bulk Pay: "Bayar Terpilih" → bulkPayModal → submitBulkPayForm() (PATCH).
     - Generate memvalidasi kelengkapan absensi dulu (payroll.check-attendance).
     - Konfigurasi JS: window.payrollConfig / window.salarySlipConfig.

     JS (sesuai tab aktif):
     - @vite('resources/js/pages/sdm/payroll/index.js')        (tab payroll)
     - @vite('resources/js/pages/sdm/salary-slip/index.js')    (tab salary-slip, di partial)
     ===================================================================== --}}
@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Payroll')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman + Aksi (sesuai tab aktif) --}}
        <x-fi.page-header heading="Data Payroll"
            :breadcrumbs="['Human Resource' => null, 'Payroll' => route('payroll.index')]">
            <x-slot:actions>
                @if ($tab === 'salary-slip')
                    {{-- Cetak Slip Gaji: semua (sesuai filter) / yang dicentang --}}
                    <x-fi.export-dropdown :pdfRoute="route('salary-slips.export.pdf')"
                        :pdfSelectedRoute="route('salary-slips.export.pdf.selected')"
                        :queryParams="['search' => request('search'), 'month' => request('month'), 'year' => request('year')]" />
                    <x-fi.button color="success" icon="document-plus" onclick="openModal('generateModal')">
                        Generate Slip Gaji
                    </x-fi.button>
                @else
                    {{-- Ekspor payroll: aktif setelah filter proyek dipilih --}}
                    <x-fi.export-dropdown :excelRoute="route('payroll.export.excel')" :pdfRoute="route('payroll.export.pdf')"
                        :queryParams="[
                            'search' => request('search'),
                            'month' => request('month'),
                            'year' => request('year'),
                            'week_number' => request('week_number'),
                            'project_name' => request('project_name'),
                        ]"
                        :disabled="! request('project_name')"
                        :title="request('project_name') ? null : 'Pilih proyek terlebih dahulu untuk mencetak laporan'" />
                    <x-fi.button color="success" icon="calculator" onclick="openModal('generateModal')">
                        Generate Payroll
                    </x-fi.button>
                @endif
            </x-slot:actions>
        </x-fi.page-header>

        {{-- SECTION: Tab (Data Payroll | Slip Gaji) — admin & superadmin --}}
        @if (auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
            <x-fi.tabs :active="$tab" :tabs="[
                'payroll' => ['label' => 'Data Payroll', 'icon' => 'credit-card', 'url' => route('payroll.index')],
                'salary-slip' => ['label' => 'Slip Gaji', 'icon' => 'document-text', 'url' => route('payroll.index', ['tab' => 'salary-slip'])],
            ]" />
        @endif

        @if ($tab === 'salary-slip')
            {{-- Tab: Slip Gaji — konten (tabel, modal, script) di partial --}}
            @include('pages.sdm.partials.salary-slip-content')
        @else
            {{-- Tab: Data Payroll — toolbar + tabel grup + paginasi --}}
            @include('components.sdm.payroll.table', ['payrollGroups' => $payrollGroups])
        @endif
    </div>

    @if ($tab !== 'salary-slip')
        {{-- SECTION: Modal Generate Payroll --}}
        @include('components.sdm.payroll.generate-modal', ['executives' => $executives])

        {{-- SECTION: Modal Edit (draft) & Detail per payroll --}}
        @foreach ($currentPayrolls as $payroll)
            @if ($payroll->status === 'draft')
                @include('components.sdm.payroll.edit-modal', ['payroll' => $payroll])
            @endif
            @include('components.sdm.payroll.detail-modal', ['payroll' => $payroll])
        @endforeach

        {{-- SECTION: Modal Konfirmasi Hapus Massal --}}
        <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
            onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
            Apakah kamu yakin ingin menghapus data yang dipilih?
            Payroll paid akan otomatis menghapus juga entri "Upah Pekerja" terkait di Laporan Keuangan.
        </x-fi.modal>

        {{-- SECTION: Modal Konfirmasi Hapus 1 Payroll (menu Aksi ⋮ tiap baris) --}}
        {{-- Mengirim satu id ke route('payroll.destroy') yang sama dengan hapus massal (ids[]). --}}
        <x-fi.delete-row-modal :action="route('payroll.destroy')" field="ids" heading="Hapus Payroll"
            warning="Payroll paid akan otomatis menghapus juga entri “Upah Pekerja” terkait di Laporan Keuangan." />

        {{-- SECTION: Modal Konfirmasi Bayar Massal --}}
        <x-fi.modal id="bulkPayModal" heading="Konfirmasi Bayar" icon="banknotes" color="success" width="md"
            onConfirm="submitBulkPayForm()" submitLabel="Ya, Bayar">
            Apakah kamu yakin ingin membayar payroll yang dipilih?
        </x-fi.modal>

        @push('scripts')
            {{-- URL AJAX (get-weeks & check-attendance), CSRF, filter aktif, daftar petinggi --}}
            @php
                $payrollConfig = [
                    'getWeeksUrl' => route('payroll.get-weeks'),
                    'checkAttendanceUrl' => route('payroll.check-attendance'),
                    'csrfToken' => csrf_token(),
                    'currentYear' => (int) date('Y'),
                    'filterWeek' => request('week_number'),
                    'filterMonth' => request('month'),
                    'filterYear' => request('year'),
                    'filterProject' => request('project_name'),
                    'executives' => $executives->map(fn ($e) => [
                        'id' => $e->id,
                        'name' => $e->name,
                        'position' => $e->position,
                    ])->values()->toArray(),
                ];
            @endphp
            <script>
                window.payrollConfig = @json($payrollConfig);
            </script>
            @vite('resources/js/pages/sdm/payroll/index.js')
        @endpush
    @endif
@endsection
