{{-- =====================================================================
     Halaman: Data Payroll (payroll)
     Tujuan: Halaman utama pengelolaan payroll karyawan yang ber-tab:
             1. "Data Payroll" — payroll mingguan (paginasi) dengan pencarian
                & filter (bulan, tahun, minggu, proyek), aksi bulk
                (Bayar/Hapus/Generate), dan ekspor (Excel/PDF).
              2. "Slip Gaji" — slip gaji karyawan bulanan (di-include dari
                 partial pages.sdm.partials.salary-slip-content), aktif saat
                 ?tab=salary-slip. Tab menu hanya tampil untuk role admin.

     Data dari PayrollController@index (bergantung tab aktif):
     - Tab payroll: $payrollGroups (LengthAwarePaginator grup payroll),
       $currentPayrolls, $search, $month, $year, $weekNumber, $projectName,
       $projects, $executives
     - Tab salary-slip: $slips, $search, $month, $year, $executives,
       $eligibleEmployees, $filterMonth, $filterYear
     - $tab : tab aktif (payroll|salary-slip)

     Komponen yang di-include:
     - components.sdm.payroll.table                     : tabel daftar payroll
     - components.sdm.payroll.generate-modal            : modal generate payroll
     - components.sdm.payroll.edit-modal                : modal edit payroll draft (loop)
     - components.sdm.payroll.detail-modal              : modal detail payroll (loop)
     - x-pagination                                     : kontrol pagination
     - x-modal (deleteModal / bulkPayModal)             : konfirmasi hapus & bayar massal
     - pages.sdm.partials.salary-slip-content           : konten tab Slip Gaji

     Alur logika yang perlu diperhatikan:
     - Dropdown minggu diisi secara dinamis via AJAX ke route payroll.get-weeks
       (dipicu saat bulan/tahun dipilih).
     - Bulk Pay: tombol "Bayar Terpilih" membuka bulkPayModal lalu submit
       submitBulkPayForm() dengan daftar ID terpilih (PATCH).
     - Generate Payroll memvalidasi kelengkapan absensi terlebih dahulu
       (route payroll.check-attendance) sebelum menciptakan payroll draft.
     - Konfigurasi diteruskan ke JS via @json($payrollConfig) →
       window.payrollConfig (URL AJAX + nilai filter aktif).
     - Pada tab salary-slip, konfigurasi diteruskan via window.salarySlipConfig
       di partial (URL AJAX eligible + daftar petinggi).

     JS yang di-load (sesuai tab aktif):
     - @vite('resources/js/pages/sdm/payroll/index.js')        (tab payroll)
     - @vite('resources/js/pages/sdm/salary-slip/index.js')    (tab salary-slip)
     ===================================================================== --}}

@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Data Payroll')

@section('content')
    {{-- ============================================================
         SECTION: Header
         Judul halaman.
         ============================================================ --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-text-primary mb-1">Data Payroll</h1>
            <p class="text-text-secondary text-sm">
                @if (!auth()->user()->isSuperAdmin())
                    Pilih tab di bawah untuk mengelola payroll mingguan atau slip gaji karyawan bulanan.
                @else
                    Kelola payroll mingguan karyawan.
                @endif
            </p>
        </div>
    </div>

    {{-- ============================================================
         SECTION: Tab Menu (Data Payroll | Slip Gaji)
         Tab menu hanya tampil untuk role admin.
         ============================================================ --}}
    @if (auth()->user()->isAdmin())
    <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 my-6">
        <a href="{{ route('payroll.index') }}"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl border transition-colors duration-200 text-sm font-semibold
                {{ $tab === 'payroll' ? 'bg-primary text-white border-primary' : 'bg-surface-base text-text-primary border-border-strong hover:bg-primary-light hover:text-primary' }}">
            <i class="fa-solid fa-money-check-alt"></i>
            <span>Data Payroll</span>
        </a>
        <a href="{{ route('payroll.index', ['tab' => 'salary-slip']) }}"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-xl border transition-colors duration-200 text-sm font-semibold
                {{ $tab === 'salary-slip' ? 'bg-primary text-white border-primary' : 'bg-surface-base text-text-primary border-border-strong hover:bg-primary-light hover:text-primary' }}">
            <i class="fa-solid fa-file-invoice"></i>
            <span>Slip Gaji</span>
        </a>
    </div>
    @endif

    @if ($tab === 'salary-slip')
        {{-- ============================================================
             Tab: Slip Gaji (hanya non-superadmin) — konten di partial
             ============================================================ --}}
        @include('pages.sdm.partials.salary-slip-content')
    @else
        {{-- ============================================================
             Tab: Data Payroll (default)
             ============================================================ --}}
        <div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
        {{-- ============================================================
             SECTION: Filter / Toolbar
             Form filter (bulan, tahun, minggu, pencarian) + aksi kanan
             (ekspor Excel/PDF, Bayar Terpilih, Hapus, Generate Payroll).
             ============================================================ --}}
        {{-- Search & Action Buttons --}}
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            {{-- Form Pencarian dan Filter --}}
            <form method="GET" action="{{ route('payroll.index') }}"
                class="w-full min-[1560px]:w-auto min-[1560px]:flex-1 flex flex-col min-[1560px]:flex-row gap-3">

                {{-- Filter Bulan --}}
                <x-filters.month-filter :value="request('month')" fill />

                {{-- Filter Tahun --}}
                <x-filters.year-filter :value="request('year')" fill />

                {{-- Filter Minggu --}}
                {{-- Dropdown ini diisi secara dinamis oleh JS via route
                     payroll.get-weeks (bergantung bulan + tahun terpilih). --}}
                <div class="flex-1">
                    <select name="week_number" id="filter_week_number"
                        class="block w-full rounded-lg border border-border-strong bg-surface-secondary p-3 text-sm text-text-input focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary-light"
                        onchange="this.form.requestSubmit()">
                        <option value="">Semua Minggu</option>
                    </select>
                </div>

                {{-- Filter Proyek --}}
                {{-- Dropdown searchable (10 item per load) mengambil data proyek
                     dari Rekap Proyek; memilih proyek otomatis submit form. --}}
                <x-filters.project-filter :route="route('employee.projects-dropdown')"
                    :value="request('project_name')" fill dropdown-id="filter_project_name"
                    :auto-submit="true" />

                {{-- Search Input --}}
                <x-filters.search-input :value="request('search')" placeholder="Cari karyawan..." />
            </form>

            {{-- Aksi di Kanan --}}
            <div class="flex items-center gap-2 mt-2 min-[1560px]:mt-0 w-full min-[1560px]:w-auto">
                <div class="flex flex-col min-[1560px]:flex-row gap-2 w-full min-[1560px]:w-auto">
                    <x-buttons.print-dropdown :excelRoute="route('payroll.export.excel')" :pdfRoute="route('payroll.export.pdf')" :queryParams="[
                        'search' => request('search'),
                        'month' => request('month'),
                        'year' => request('year'),
                        'week_number' => request('week_number'),
                        'project_name' => request('project_name'),
                    ]" responsive="custom" fill :disabled="!request('project_name')" />

                    {{-- Tombol Bulk Pay: disabled selama tidak ada baris yang
                         dipilih; diaktifkan oleh JS setelah seleksi. Membuka
                         bulkPayModal lalu submitBulkPayForm(). --}}
                    <button type="button" id="bulk-pay-button" onclick="openModal('bulkPayModal')"
                        class="w-full min-[1560px]:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium opacity-50 cursor-not-allowed"
                        disabled>
                        <i class="fa-solid fa-money-check-alt"></i>
                        Bayar Terpilih
                    </button>

                    <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                    <button type="button" onclick="openModal('generateModal')"
                        class="w-full min-[1560px]:w-auto flex items-center justify-center gap-2 bg-success hover:bg-success-hover text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium">
                        <i class="fa-solid fa-calculator"></i>
                        Generate Payroll
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
             SECTION: Table
             Menampilkan daftar payroll dengan checkbox seleksi massal
             (draft maupun paid dapat dipilih & dihapus).
             ============================================================ --}}
        {{-- Table Component --}}
        @include('components.sdm.payroll.table', ['payrollGroups' => $payrollGroups])

    </div>

    {{-- ============================================================
         SECTION: Pagination
         Kontrol navigasi halaman daftar payroll.
         ============================================================ --}}
    {{-- Pagination --}}
    <x-pagination :paginator="$payrollGroups" />

    {{-- ============================================================
         SECTION: Modals
         - generate-modal : wizard generate payroll (validasi absensi dulu).
         - edit-modal     : modal edit payroll draft (loop, hanya status draft).
         - detail-modal   : modal detail read-only (loop).
         - deleteModal    : konfirmasi hapus massal (draft maupun paid).
         - bulkPayModal   : konfirmasi bayar massal (PATCH, hanya draft).
         ============================================================ --}}
    {{-- Modal Generate Payroll --}}
    @include('components.sdm.payroll.generate-modal', ['executives' => $executives])

    {{-- Hapus modal Bayar per-item: kini pembayaran hanya via Bulk Pay --}}

    {{-- Modal Detail untuk setiap payroll --}}
    @foreach ($currentPayrolls as $payroll)
        @if ($payroll->status === 'draft')
            @include('components.sdm.payroll.edit-modal', ['payroll' => $payroll])
        @endif
        @include('components.sdm.payroll.detail-modal', ['payroll' => $payroll])
    @endforeach

    {{-- Modal Konfirmasi Bulk Delete --}}
    <x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
        buttonText="Ya, Hapus">
        Apakah kamu yakin ingin menghapus data yang dipilih?

        (Payroll paid akan otomatis menghapus juga entri "Upah Pekerja" terkait di Laporan Keuangan)
    </x-modal>

    {{-- Modal Konfirmasi Bulk Pay --}}
    <x-modal id="bulkPayModal" title="Konfirmasi Bayar" method="PATCH" onConfirm="submitBulkPayForm()"
        buttonText="Ya, Bayar">
        <p class="text-text-primary">Apakah kamu yakin ingin membayar payroll yang dipilih?</p>
    </x-modal>

    {{-- ============================================================
         SECTION: Scripts
         $payrollConfig berisi URL endpoint AJAX (get-weeks &
         check-attendance), CSRF token, serta nilai filter aktif, lalu
         diteruskan ke JS melalui @json → window.payrollConfig.
         Modul JS halaman payroll di-load via Vite.
         ============================================================ --}}
    {{-- Pass server data to JavaScript --}}
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
            'executives' => $executives->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'position' => $e->position,
            ])->values()->toArray(),
        ];
    @endphp

    <script>
        window.payrollConfig = @json($payrollConfig);
    </script>

    {{-- JavaScript --}}
    @vite('resources/js/pages/sdm/payroll/index.js')
    @endif
@endsection
