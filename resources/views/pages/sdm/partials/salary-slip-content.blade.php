{{-- =====================================================================
     Partial: Tab "Slip Gaji" di halaman Data Payroll (payroll?tab=salary-slip)
     Tujuan: Konten tab slip gaji karyawan bulanan (employment_type = bulanan).
             Menampilkan daftar slip dengan filter (bulan, tahun, pencarian),
             aksi bulk (Bayar/Hapus), generate slip per periode, dan cetak PDF
             (rekap / per slip).

     Data dari PayrollController@index (saat tab=salary-slip):
     - $slips     : LengthAwarePaginator daftar slip (dengan relasi employee)
     - $search, $month, $year : nilai filter aktif (nullable)
     - $executives: daftar petinggi untuk pemilihan penanda tangan
     - $eligibleEmployees, $filterMonth, $filterYear : data modal generate

     Komponen yang di-include:
     - components.sdm.salary-slip.table           : tabel daftar slip
     - components.sdm.salary-slip.generate-modal  : modal generate slip
     - components.sdm.salary-slip.edit-modal      : modal edit rekap absensi (loop, draft)
     - components.sdm.salary-slip.detail-modal    : modal detail (loop)
     - x-pagination                               : kontrol pagination
     - x-modal (deleteModal / bulkPayModal)       : konfirmasi hapus & bayar massal

     Konfigurasi diteruskan ke JS via @json($salarySlipConfig) →
     window.salarySlipConfig; modul JS di-load via Vite hanya pada tab ini.
     ===================================================================== --}}

<div class="bg-surface-base p-4 sm:p-6 rounded-xl shadow">
    {{-- ============================================================
         SECTION: Filter / Toolbar
         ============================================================ --}}
    <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
        {{-- Form Pencarian dan Filter (submit ke halaman Data Payroll,
             tab Slip Gaji agar filter & pagination tetap pada tab aktif). --}}
        <form method="GET" action="{{ route('payroll.index', ['tab' => 'salary-slip']) }}"
            class="w-full min-[1560px]:w-auto min-[1560px]:flex-1 flex flex-col min-[1560px]:flex-row gap-3">

            {{-- Filter Bulan --}}
            <x-filters.month-filter :value="request('month')" fill />

            {{-- Filter Tahun --}}
            <x-filters.year-filter :value="request('year')" fill />

            {{-- Search Input --}}
            <x-filters.search-input :value="request('search')" placeholder="Cari karyawan..." />
        </form>

        {{-- Aksi di Kanan --}}
        <div class="flex items-center gap-2 mt-2 min-[1560px]:mt-0 w-full min-[1560px]:w-auto">
            <div class="flex flex-col min-[1560px]:flex-row gap-2 w-full min-[1560px]:w-auto">
                {{-- Print Slip Gaji (semua sesuai filter / terpilih) --}}
                <x-buttons.print-dropdown-with-selected :pdfRoute="route('salary-slips.export.pdf')" :queryParams="[
                    'search' => request('search'),
                    'month' => request('month'),
                    'year' => request('year'),
                ]" responsive="custom" fill />

                {{-- Tombol Bulk Pay --}}
                <button type="button" id="bulk-pay-button" onclick="openModal('bulkPayModal')"
                    class="w-full min-[1560px]:w-auto flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium opacity-50 cursor-not-allowed"
                    disabled>
                    <i class="fa-solid fa-money-check-alt"></i>
                    Bayar Terpilih
                </button>

                <x-buttons.delete-button modalId="deleteModal" responsive="custom" />

                <button type="button" onclick="openModal('generateModal')"
                    class="w-full min-[1560px]:w-auto flex items-center justify-center gap-2 bg-success hover:bg-success-hover text-white px-4 py-2 rounded-lg transition-colors duration-200 text-sm font-medium">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    Generate Slip Gaji
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         SECTION: Table
         ============================================================ --}}
    @include('components.sdm.salary-slip.table', ['slips' => $slips])
</div>

{{-- ============================================================
     SECTION: Pagination
     ============================================================ --}}
<x-pagination :paginator="$slips" />

{{-- ============================================================
     SECTION: Modals
     ============================================================ --}}
{{-- Modal Generate --}}
@include('components.sdm.salary-slip.generate-modal', [
    'executives' => $executives,
    'eligibleEmployees' => $eligibleEmployees,
    'filterMonth' => $filterMonth,
    'filterYear' => $filterYear,
])

{{-- Modal Edit (loop, hanya draft) & Modal Detail --}}
@foreach ($slips as $slip)
    @if ($slip->status === 'draft')
        @include('components.sdm.salary-slip.edit-modal', ['slip' => $slip])
    @endif
    @include('components.sdm.salary-slip.detail-modal', ['slip' => $slip])
@endforeach

{{-- Modal Konfirmasi Bulk Delete --}}
<x-modal id="deleteModal" title="Konfirmasi Hapus" :confirmDelete="true" onConfirm="submitDeleteForm()"
    buttonText="Ya, Hapus">
    Apakah kamu yakin ingin menghapus slip gaji yang dipilih?
</x-modal>

{{-- Modal Konfirmasi Bulk Pay --}}
<x-modal id="bulkPayModal" title="Konfirmasi Bayar" onConfirm="submitBulkPayForm()" buttonText="Ya, Bayar">
    <p class="text-text-primary">Apakah kamu yakin ingin membayar slip gaji yang dipilih? Setelah dibayar, data
        absensi slip terkunci dan tidak dapat diubah lagi.</p>
</x-modal>

{{-- ============================================================
     SECTION: Scripts
     ============================================================ --}}
@php
    $salarySlipConfig = [
        'csrfToken' => csrf_token(),
        'currentYear' => (int) date('Y'),
        'eligibleEmployeesUrl' => route('salary-slips.eligible'),
        'executives' => $executives->map(fn($e) => [
            'id' => $e->id,
            'name' => $e->name,
            'position' => $e->position,
        ])->values()->toArray(),
    ];
@endphp

{{-- Hidden input untuk route print selected (dipakai JS modul halaman). --}}
<input type="hidden" id="salary-slip-print-selected-route" value="{{ route('salary-slips.export.pdf.selected') }}">

<script>
    window.salarySlipConfig = @json($salarySlipConfig);
</script>

{{-- JavaScript --}}
@vite('resources/js/pages/sdm/salary-slip/index.js')
