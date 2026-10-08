{{--
    Generate Slip Gaji Modal (2 langkah)

    Membuat slip gaji draft untuk karyawan kantor (employment_type = bulanan)
    yang BELUM memiliki slip pada periode terpilih. Karyawan kantor tidak
    memakai modul Absensi Harian — kehadirannya direkap di sini.

    Langkah 1 — Data Slip & Tanggal Merah:
    1. Pilih bulan + tahun periode.
    2. Daftar karyawan (multi-select searchable, bisa banyak) otomatis dimuat
       sesuai periode via AJAX (SalarySlipController@eligibleEmployees).
    3. Atur cicilan kasbon bulan ini per karyawan yang masih punya sisa
       kasbon (default = seluruh sisa, maksimal = sisa) → kasbon_installments[kode].
    4. Centang hari libur / tanggal merah pada kalender periode (opsional) —
       kalender dimulai hari Senin, kolom Minggu paling kanan merah & otomatis Libur.
    5. Pilih penanda tangan (opsional) — snapshot disimpan per slip.

    Langkah 2 — Rekap Absensi (tombol Lanjut, tampilan bergeser):
    Grid semua karyawan terpilih × tanggal, tombol per hari seperti modal
    Edit (H → I → S → C → A → L). Minggu & tanggal merah otomatis L. Ada
    "Reset semua ke Hadir" dan reset per baris. Nilai terkirim sebagai
    attendance[kode karyawan] = "HHLHC..." (satu huruf per hari).
    Submit → SalarySlipController@generate (POST).

    Frontend JS: resources/js/pages/sdm/salary-slip/index.js
    (loadEligibleEmployees, renderHolidayDays, renderKasbonInstallments,
    goToGenerateStep, renderGenerateAttendanceGrid)
--}}

<x-modal id="generateModal" title="Generate Slip Gaji" action="{{ route('salary-slips.generate') }}" method="POST"
    buttonText="Generate">

    {{-- Penanda langkah (diperbarui goToGenerateStep) --}}
    <div class="flex items-center gap-3 mb-4 text-sm">
        <div class="generate-step-indicator flex items-center gap-2 font-semibold text-primary" data-step="1">
            <span class="generate-step-badge w-6 h-6 rounded-full flex items-center justify-center text-xs bg-primary text-white">1</span>
            <span>Data Slip &amp; Tanggal Merah</span>
        </div>
        <div class="flex-1 h-px bg-border"></div>
        <div class="generate-step-indicator flex items-center gap-2 text-text-secondary" data-step="2">
            <span class="generate-step-badge w-6 h-6 rounded-full flex items-center justify-center text-xs bg-surface-hover text-text-label">2</span>
            <span>Rekap Absensi</span>
        </div>
    </div>

    {{-- ===================== LANGKAH 1 ===================== --}}
    <div id="generate-step-1" class="generate-step transition-all duration-300 ease-out">
        <div class="mb-4 p-4 bg-primary-light border border-primary rounded-lg">
            <div class="flex gap-2">
                <i class="fa-solid fa-info-circle text-primary mt-1"></i>
                <div class="text-sm text-text-primary">
                    <p class="font-semibold mb-1">Informasi Slip Gaji Bulanan:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Slip dibuat untuk <strong>karyawan kantor</strong> (Data Karyawan → Jenis Karyawan = KARYAWAN KANTOR)</li>
                        <li>Satu slip per karyawan per bulan — karyawan yang sudah punya slip dilewati otomatis</li>
                        <li>Perhitungan: <strong>gaji pokok + uang transport + uang makan + lembur − potongan</strong></li>
                        <li>Potongan: BPJS Kesehatan 1% gaji pokok, JHT 2% UMP, JPN 1% UMP, PPh 21 (manual), dan cicilan kasbon bulan ini</li>
                        <li>Hari Minggu otomatis berstatus <strong>L</strong> (Libur); centang hari libur lain pada periode ini di bagian <strong>Hari Libur</strong></li>
                        <li>Klik <strong>Lanjut</strong> untuk mengisi rekap absensi semua karyawan terpilih sekaligus (masih bisa diubah lewat <strong>Edit</strong> sebelum dibayar)</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <label class="block text-text-primary mb-1">Bulan <span class="text-error">*</span></label>
                <select name="period_month" id="period_month"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
                    oninvalid="this.setCustomValidity('Bulan tidak boleh kosong')" oninput="this.setCustomValidity('')">
                    <option value="">Pilih</option>
                    @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $m => $mName)
                        <option value="{{ $m }}" @selected($filterMonth == $m)>{{ $mName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-text-primary mb-1">Tahun <span class="text-error">*</span></label>
                <input type="number" name="period_year" id="period_year"
                    class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                    placeholder="2025" value="{{ $filterYear }}" required min="2000" max="2100">
            </div>
        </div>

        {{-- Multi-select karyawan bulanan yang belum punya slip periode ini.
             Opsi dimuat server-side lalu diperbarui via AJAX saat bulan/tahun
             berubah (loadEligibleEmployees). Nilai terkirim sebagai employee_codes[]. --}}
        <x-forms.searchable-multi-select
            name="employee_codes"
            id="generate-eligible-employees"
            label="Karyawan Kantor"
            :required="true"
            placeholder="Cari karyawan kantor..."
            :options="$eligibleEmployees->map(fn($e) => ['value' => $e->employee_code, 'label' => $e->name . ' - ' . $e->employee_code])->values()" />

        {{-- Cicilan Kasbon — satu baris per karyawan terpilih yang masih punya
             sisa kasbon. Dibuat dinamis oleh renderKasbonInstallments() setiap
             pilihan karyawan berubah. Nilai terkirim sebagai
             kasbon_installments[kode karyawan]. --}}
        <div class="mb-3 p-3 bg-surface-secondary border border-border rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-hand-holding-dollar text-primary"></i>
                <p class="text-sm font-semibold text-text-primary">Cicilan Kasbon Bulan Ini</p>
            </div>
            <p class="text-xs text-text-secondary mb-3">Kasbon karyawan kantor bisa dicicil. Default cicilan = seluruh sisa
                kasbon (maksimal sisa kasbon); sisanya terbawa ke slip bulan berikutnya. Masih bisa diubah lewat tombol
                Edit slip sebelum dibayar.</p>

            <div id="generate-kasbon-installments" class="space-y-2">
                <p class="text-xs text-text-label">Pilih karyawan untuk melihat sisa kasbon.</p>
            </div>
        </div>

        {{-- Hari Libur — kalender periode terpilih (minggu dimulai Senin, kolom
             Minggu paling kanan & merah). Admin mencentang tanggal merah lain
             (libur nasional, cuti bersama). Kalender dibuat dinamis oleh
             renderHolidayDays() saat bulan/tahun berubah. Nilai terkirim sebagai
             holidays[] (format Y-m-d). --}}
        <div class="mb-3 p-3 bg-surface-secondary border border-border rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-calendar-day text-primary"></i>
                <p class="text-sm font-semibold text-text-primary">Hari Libur (Opsional)</p>
            </div>
            <p class="text-xs text-text-secondary mb-3">Hari Minggu (merah) sudah otomatis berstatus Libur. Centang tanggal
                lain pada periode ini yang merupakan hari libur (mis. libur nasional, cuti bersama).</p>

            <div id="holiday-days-grid" class="max-w-md">
                {{-- Diisi oleh renderHolidayDays() pada salary-slip/index.js --}}
            </div>
        </div>

        {{-- Penanda Tangan — satu set (Disetujui/Diperiksa/Dibuat) untuk semua
             slip periode ini. Disimpan sebagai snapshot per slip. Opsional. --}}
        <div class="mb-3 p-3 bg-surface-secondary border border-border rounded-lg">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-pen-nib text-primary"></i>
                <p class="text-sm font-semibold text-text-primary">Penanda Tangan</p>
            </div>
            <p class="text-xs text-text-secondary mb-3">Opsional — tidak wajib diisi. Data diambil dari modul Data
                Penandatangan; jika dikosongkan, blok tanda tangan pada PDF ditampilkan sebagai garis putus-putus.</p>

            <x-forms.searchable-select name="signatures[disetujui]" label="Disetujui oleh" placeholder="Penandatangan..."
                :options="$executives->map(fn($e) => ['value' => $e->id, 'label' => $e->name . ($e->position ? ' - ' . $e->position : '')])->values()" />

            <x-forms.searchable-select name="signatures[diperiksa]" label="Diperiksa oleh" placeholder="Penandatangan..."
                :options="$executives->map(fn($e) => ['value' => $e->id, 'label' => $e->name . ($e->position ? ' - ' . $e->position : '')])->values()" />

            <x-forms.searchable-select name="signatures[dibuat]" label="Dibuat oleh" placeholder="Penandatangan..."
                :options="$executives->map(fn($e) => ['value' => $e->id, 'label' => $e->name . ($e->position ? ' - ' . $e->position : '')])->values()" />
        </div>
    </div>

    {{-- ===================== LANGKAH 2 ===================== --}}
    {{-- Rekap absensi semua karyawan terpilih sekaligus (tampilan seperti
         modal Edit). Grid dibuat renderGenerateAttendanceGrid(). --}}
    <div id="generate-step-2" class="generate-step hidden transition-all duration-300 ease-out">
        <div class="mb-3 p-3 bg-primary-light border border-primary rounded-lg">
            <div class="flex gap-2 text-sm text-text-primary">
                <i class="fa-solid fa-info-circle text-primary mt-1"></i>
                <p>Klik kotak tanggal untuk mengganti status: <strong>H</strong>=Hadir, <strong>I</strong>=Izin,
                    <strong>S</strong>=Sakit, <strong>C</strong>=Cuti, <strong>A</strong>=Alpha, <strong>L</strong>=Libur
                    (klik kanan untuk mundur). Minggu &amp; tanggal merah dari langkah 1 otomatis <strong>L</strong>.
                    Cukup ubah hari yang tidak hadir, mis. karyawan ke-2 cuti tanggal 5.</p>
            </div>
        </div>

        <div id="generate-attendance-grid" class="mb-3">
            {{-- Diisi oleh renderGenerateAttendanceGrid() pada salary-slip/index.js --}}
        </div>

        {{-- Legend (sama dengan modal Edit) --}}
        <div class="mb-3 flex items-center gap-3 text-xs text-text-label flex-wrap">
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-success-light text-success font-semibold">H</span> Hadir</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-warning-light text-warning font-semibold">I</span> Izin</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-error-light text-error font-semibold">S</span> Sakit</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-purple-100 text-purple-700 font-semibold">C</span> Cuti</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-surface-hover text-text-label font-semibold">A</span> Alpha</span>
            <span class="inline-flex items-center gap-1"><span class="w-4 h-4 inline-flex items-center justify-center rounded bg-primary-light text-primary font-semibold">L</span> Libur</span>
        </div>
    </div>
</x-modal>
