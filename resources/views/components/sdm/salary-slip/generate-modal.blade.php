{{--
    Generate Slip Gaji Modal

    Membuat slip gaji draft untuk karyawan kantor (employment_type = bulanan)
    yang BELUM memiliki slip pada periode terpilih. Setiap slip dibuat dengan
    matriks absensi default: hari Minggu otomatis ditandai "L" (Libur), dan
    tanggal yang dicentang pada bagian Hari Libur juga ditandai "L" — admin
    tinggal mengubah hari tertentu pada modal Edit setelah generate.

    Proses:
    1. Pilih bulan + tahun periode.
    2. Daftar karyawan (multi-select searchable) otomatis dimuat sesuai periode
       via AJAX (SalarySlipController@eligibleEmployees).
    3. Centang hari libur pada kalender periode (opsional) — kalender dimulai
       hari Senin, kolom Minggu paling kanan berwarna merah & otomatis Libur.
    4. Atur cicilan kasbon bulan ini per karyawan yang masih punya sisa
       kasbon (default = seluruh sisa, maksimal = sisa) → kasbon_installments[kode].
    5. Pilih penanda tangan (opsional) — snapshot disimpan per slip.
    6. Submit → SalarySlipController@generate (POST).

    Frontend JS: resources/js/pages/sdm/salary-slip/index.js
    (loadEligibleEmployees, renderHolidayDays, renderKasbonInstallments)
--}}

<x-modal id="generateModal" title="Generate Slip Gaji" action="{{ route('salary-slips.generate') }}" method="POST"
    buttonText="Generate">

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
                    <li>Setelah generate, isi rekap absensi pada tombol <strong>Edit</strong> lalu bayar</li>
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
</x-modal>
