{{--
    Generate Slip Gaji Modal (gaya Filament, 2 langkah)

    Membuat slip gaji draft untuk karyawan kantor (employment_type = bulanan)
    yang BELUM memiliki slip pada periode terpilih.

    Langkah 1 — Data Slip & Tanggal Merah:
    1. Pilih bulan + tahun periode.
    2. Daftar karyawan (multi-select searchable) dimuat sesuai periode via AJAX
       (SalarySlipController@eligibleEmployees).
    3. Cicilan kasbon bulan ini per karyawan yang masih punya sisa kasbon
       (default = seluruh sisa, maksimal = sisa) → kasbon_installments[kode].
    4. Centang hari libur / tanggal merah pada kalender periode (opsional).
    5. Pilih penanda tangan (opsional) — snapshot disimpan per slip.

    Langkah 2 — Rekap Absensi (tombol Lanjut, panel modal melebar):
    Grid semua karyawan terpilih × tanggal (H → I → S → C → A → L), Minggu &
    tanggal merah otomatis L. Nilai terkirim sebagai attendance[kode] = "HHLH...".
    Submit → SalarySlipController@generate (POST).

    Frontend JS: resources/js/pages/sdm/salary-slip/index.js
    (loadEligibleEmployees, renderHolidayDays, renderKasbonInstallments,
    goToGenerateStep, renderGenerateAttendanceGrid). JS mengganti lebar panel
    (firstElementChild #generateModal: max-w-lg ↔ max-w-6xl) dan menyisipkan
    tombol Kembali / Lanjut sebelum #submit-btn-generateModal.
--}}

<x-fi.modal id="generateModal" heading="Generate Slip Gaji" icon="document-plus" color="success"
    :action="route('salary-slips.generate')" submitLabel="Generate">

    {{-- Penanda langkah (diperbarui goToGenerateStep) --}}
    <div class="flex items-center gap-3 text-sm">
        <div class="generate-step-indicator flex items-center gap-2 font-semibold text-primary" data-step="1">
            <span class="generate-step-badge flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-white">1</span>
            <span>Data Slip &amp; Tanggal Merah</span>
        </div>
        <div class="h-px flex-1 bg-gray-200"></div>
        <div class="generate-step-indicator flex items-center gap-2 text-text-secondary" data-step="2">
            <span class="generate-step-badge flex h-6 w-6 items-center justify-center rounded-full bg-surface-hover text-xs text-text-label">2</span>
            <span>Rekap Absensi</span>
        </div>
    </div>

    {{-- ===================== LANGKAH 1 ===================== --}}
    <div id="generate-step-1" class="generate-step grid gap-y-5 transition-all duration-300 ease-out">
        <div class="flex gap-3 rounded-lg bg-blue-50 p-4 text-sm ring-1 ring-inset ring-blue-600/10">
            <x-fi.icon name="information-circle" class="h-5 w-5 shrink-0 text-primary" />
            <div class="text-gray-700">
                <p class="font-semibold text-gray-950">Informasi Slip Gaji Bulanan</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    <li>Slip dibuat untuk <strong>karyawan kantor</strong> (Data Karyawan → Jenis Karyawan = KARYAWAN KANTOR)</li>
                    <li>Satu slip per karyawan per bulan — karyawan yang sudah punya slip dilewati otomatis</li>
                    <li>Perhitungan: <strong>gaji pokok + uang transport + uang makan + lembur − potongan</strong></li>
                    <li>Potongan: BPJS Kesehatan 1% gaji pokok, JHT 2% UMP, JPN 1% UMP, PPh 21 (manual), dan cicilan kasbon bulan ini</li>
                    <li>Hari Minggu otomatis berstatus <strong>L</strong> (Libur); centang hari libur lain pada periode ini di bagian <strong>Hari Libur</strong></li>
                    <li>Klik <strong>Lanjut</strong> untuk mengisi rekap absensi semua karyawan terpilih sekaligus (masih bisa diubah lewat <strong>Edit</strong> sebelum dibayar)</li>
                </ul>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-x-4">
            <x-fi.field label="Bulan" for="period_month" required>
                <x-fi.select name="period_month" id="period_month" required data-fi-select placeholder="Pilih" :value="$filterMonth"
                    :options="[1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember']"
                    oninvalid="this.setCustomValidity('Bulan tidak boleh kosong')" oninput="this.setCustomValidity('')" />
            </x-fi.field>
            <x-fi.field label="Tahun" for="period_year" required>
                <x-fi.input type="number" name="period_year" id="period_year" placeholder="2025" :value="$filterYear"
                    required min="2000" max="2100" />
            </x-fi.field>
        </div>

        {{-- Karyawan bulanan yang belum punya slip periode ini → employee_codes[] --}}
        <x-fi.multi-select name="employee_codes" id="generate-eligible-employees" label="Karyawan Kantor" :required="true"
            placeholder="Cari karyawan kantor..."
            :options="$eligibleEmployees->map(fn ($e) => ['value' => $e->employee_code, 'label' => $e->name . ' - ' . $e->employee_code])->values()" />

        {{-- Cicilan Kasbon — baris per karyawan terpilih yang masih punya sisa
             kasbon (renderKasbonInstallments) → kasbon_installments[kode] --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <header class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
                <x-fi.icon name="banknotes" class="h-5 w-5 text-gray-400" />
                <h3 class="text-sm font-semibold text-gray-950">Cicilan Kasbon Bulan Ini</h3>
            </header>
            <div class="grid gap-y-3 p-4">
                <p class="text-sm text-gray-500">Kasbon karyawan kantor bisa dicicil. Default cicilan = seluruh sisa kasbon
                    (maksimal sisa kasbon); sisanya terbawa ke slip bulan berikutnya. Masih bisa diubah lewat tombol Edit
                    slip sebelum dibayar.</p>
                <div id="generate-kasbon-installments" class="space-y-2">
                    <p class="text-sm text-gray-500">Pilih karyawan untuk melihat sisa kasbon.</p>
                </div>
            </div>
        </section>

        {{-- Hari Libur — kalender periode (renderHolidayDays) → holidays[] (Y-m-d) --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <header class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
                <x-fi.icon name="calendar-days" class="h-5 w-5 text-gray-400" />
                <h3 class="text-sm font-semibold text-gray-950">Hari Libur (Opsional)</h3>
            </header>
            <div class="grid gap-y-3 p-4">
                <p class="text-sm text-gray-500">Hari Minggu (merah) sudah otomatis berstatus Libur. Centang tanggal lain pada
                    periode ini yang merupakan hari libur (mis. libur nasional, cuti bersama).</p>
                <div id="holiday-days-grid" class="max-w-md"></div>
            </div>
        </section>

        {{-- Penanda Tangan — satu set untuk semua slip periode ini (opsional) --}}
        <section class="rounded-lg ring-1 ring-gray-950/5">
            <header class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
                <x-fi.icon name="pencil-square" class="h-5 w-5 text-gray-400" />
                <h3 class="text-sm font-semibold text-gray-950">Penanda Tangan</h3>
            </header>
            <div class="grid gap-y-4 p-4">
                <p class="text-sm text-gray-500">Opsional — tidak wajib diisi. Data diambil dari modul Data Penandatangan; jika
                    dikosongkan, blok tanda tangan pada PDF ditampilkan sebagai garis putus-putus.</p>
                @php
                    $executiveOptions = $executives->mapWithKeys(fn ($e) => [$e->id => $e->name . ($e->position ? ' - ' . $e->position : '')]);
                @endphp
                @foreach (['disetujui' => 'Disetujui oleh', 'diperiksa' => 'Diperiksa oleh', 'dibuat' => 'Dibuat oleh'] as $roleKey => $roleLabel)
                    <x-fi.field :label="$roleLabel" for="generate-signature-{{ $roleKey }}">
                        <x-fi.select name="signatures[{{ $roleKey }}]" id="generate-signature-{{ $roleKey }}"
                            data-fi-select="search" placeholder="-- Pilih Penandatangan --" :options="$executiveOptions" />
                    </x-fi.field>
                @endforeach
            </div>
        </section>
    </div>

    {{-- ===================== LANGKAH 2 ===================== --}}
    {{-- Rekap absensi semua karyawan terpilih (renderGenerateAttendanceGrid) --}}
    <div id="generate-step-2" class="generate-step hidden transition-all duration-300 ease-out">
        <div class="mb-4 flex gap-3 rounded-lg bg-blue-50 p-4 text-sm text-gray-700 ring-1 ring-inset ring-blue-600/10">
            <x-fi.icon name="information-circle" class="h-5 w-5 shrink-0 text-primary" />
            <p>Klik kotak tanggal untuk mengganti status: <strong>H</strong>=Hadir, <strong>I</strong>=Izin,
                <strong>S</strong>=Sakit, <strong>C</strong>=Cuti, <strong>A</strong>=Alpha, <strong>L</strong>=Libur
                (klik kanan untuk mundur). Minggu &amp; tanggal merah dari langkah 1 otomatis <strong>L</strong>.
                Cukup ubah hari yang tidak hadir, mis. karyawan ke-2 cuti tanggal 5.</p>
        </div>

        <div id="generate-attendance-grid" class="mb-3"></div>

        @include('components.sdm.salary-slip.status-legend')
    </div>
</x-fi.modal>
