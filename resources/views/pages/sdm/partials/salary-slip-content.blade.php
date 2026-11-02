{{-- =====================================================================
     Partial: Tab "Slip Gaji" di halaman Data Payroll (payroll?tab=salary-slip)
     Tujuan: Konten tab slip gaji karyawan bulanan (employment_type = bulanan):
             daftar slip dengan filter (bulan, tahun, pencarian), aksi massal
             (Bayar/Hapus), generate slip per periode, dan cetak PDF (rekap /
             terpilih di header halaman, per slip di menu ⋮).

     Data dari PayrollController@index (saat tab=salary-slip):
     - $slips     : LengthAwarePaginator daftar slip (dengan relasi employee)
     - $search, $month, $year : nilai filter aktif (nullable)
     - $executives: daftar penandatangan (Data Penandatangan)
     - $eligibleEmployees, $filterMonth, $filterYear : data modal generate

     Komponen yang di-include:
     - components.sdm.salary-slip.table           : toolbar + tabel + paginasi
     - components.sdm.salary-slip.generate-modal  : modal generate slip (2 langkah)
     - components.sdm.salary-slip.edit-modal      : modal edit rekap absensi (loop, draft)
     - components.sdm.salary-slip.detail-modal    : modal detail (loop)
     - x-fi.modal (deleteModal / bulkPayModal), x-fi.delete-row-modal

     Konfigurasi JS: window.salarySlipConfig; modul JS di-load hanya pada tab ini.
     ===================================================================== --}}

{{-- Tabel Slip Gaji (+ filter, aksi massal, paginasi) --}}
@include('components.sdm.salary-slip.table', ['slips' => $slips])

@push('modals')
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

    {{-- Modal Konfirmasi Hapus Massal --}}
    <x-fi.modal id="deleteModal" heading="Hapus Data Terpilih" icon="trash" color="danger" width="md"
        onConfirm="submitDeleteForm()" submitLabel="Ya, Hapus">
        Apakah kamu yakin ingin menghapus slip gaji yang dipilih?
    </x-fi.modal>

    {{-- Modal Konfirmasi Hapus 1 Slip (menu Aksi ⋮ tiap baris) --}}
    {{-- Mengirim satu id ke route('salary-slips.destroy') yang sama dengan hapus massal (ids[]). --}}
    <x-fi.delete-row-modal :action="route('salary-slips.destroy')" field="ids" heading="Hapus Slip Gaji" />

    {{-- Modal Konfirmasi Bayar Massal --}}
    <x-fi.modal id="bulkPayModal" heading="Konfirmasi Bayar" icon="banknotes" color="success" width="md"
        onConfirm="submitBulkPayForm()" submitLabel="Ya, Bayar">
        Apakah kamu yakin ingin membayar slip gaji yang dipilih? Setelah dibayar, data absensi slip terkunci dan tidak
        dapat diubah lagi. Cicilan kasbon bulan ini dicatat sebagai pembayaran kasbon; sisa kasbon terbawa ke slip bulan
        berikutnya.
    </x-fi.modal>
@endpush

@push('scripts')
    @php
        $salarySlipConfig = [
            'csrfToken' => csrf_token(),
            'currentYear' => (int) date('Y'),
            'eligibleEmployeesUrl' => route('salary-slips.eligible'),
            'executives' => $executives->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'position' => $e->position,
            ])->values()->toArray(),
        ];
    @endphp

    {{-- Route cetak slip terpilih (dipakai printSelected() milik modul JS) --}}
    <input type="hidden" id="salary-slip-print-selected-route" value="{{ route('salary-slips.export.pdf.selected') }}">

    <script>
        window.salarySlipConfig = @json($salarySlipConfig);
    </script>
    @vite('resources/js/pages/sdm/salary-slip/index.js')
@endpush
