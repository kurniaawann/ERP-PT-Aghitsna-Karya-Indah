{{--
    Generate Payroll Modal (gaya Filament)

    Form generate payroll mingguan untuk karyawan pada proyek terpilih.

    Proses:
    1. Pilih satu/lebih proyek (multi-select searchable), lalu bulan, tahun, minggu.
    2. Kelengkapan absensi dicek otomatis via AJAX
       (PayrollController@checkAttendanceCompleteness) — hanya karyawan pada
       proyek terpilih yang diperiksa.
    3. Hasil validasi: karyawan lengkap, belum lengkap, sudah digenerate,
       dan karyawan tanpa proyek.
    4. Tombol Generate hanya aktif bila can_generate = true.
    5. Submit → project_name[] + periode ke PayrollController@generate.

    Frontend JS: resources/js/pages/sdm/payroll/index.js (checkAttendanceData,
    renderSignatorySections, renderAdditionalCostSections). Hook id yang
    dipakai JS: #generateModal form / button[type=submit], #period_month,
    #period_year, #week_number, #period_start_date, #period_end_date,
    #apply-common-signatories, #signatory-sections, #additional-costs-sections,
    #checking-loader, #already-generated-warning(-list), #incomplete-warning,
    #incomplete-list, #no-project-warning, #no-project-list, #complete-info,
    #complete-list, #all-complete.
--}}

<x-fi.modal id="generateModal" heading="Generate Payroll" icon="calculator" color="success" width="4xl"
    :action="route('payroll.generate')" submitLabel="Generate">

    {{-- Informasi --}}
    <div class="flex gap-3 rounded-lg bg-blue-50 p-4 text-sm ring-1 ring-inset ring-blue-600/10">
        <x-fi.icon name="information-circle" class="h-5 w-5 shrink-0 text-primary" />
        <div class="text-gray-700">
            <p class="font-semibold text-gray-950">Informasi Payroll Mingguan</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                <li>Payroll dibuat <strong>per proyek (bisa lebih dari satu)</strong> — hanya karyawan pada proyek terpilih yang diproses</li>
                <li>Sistem menghitung upah harian × hari masuk untuk pekerja harian</li>
                <li>Data diambil dari absensi minggu yang dipilih (Minggu-Sabtu)</li>
                <li><strong class="text-red-600">Hari Minggu ikut dihitung</strong> — absensi Minggu wajib terisi untuk semua karyawan</li>
                <li><strong>Tidak masuk = tidak dapat upah hari itu</strong></li>
                <li><strong class="text-red-600">Setiap karyawan harus memiliki absensi lengkap sesuai hari kerjanya</strong></li>
                <li>Kasbon hanya dipotong jika karyawan sudah membayar</li>
                <li><strong>Pilih penanda tangan</strong> per proyek untuk dokumen payroll (Disetujui/Diperiksa/Dibuat oleh)</li>
            </ul>
        </div>
    </div>

    {{-- Proyek (multi-select searchable) → hidden project_name[] (dibaca JS) --}}
    <x-fi.multi-select name="project_name" id="generate-project_name" label="Proyek" :required="true"
        placeholder="Cari proyek..." :options="$projects->map(fn ($p) => ['value' => $p, 'label' => $p])->values()" />

    {{-- Periode --}}
    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-3 [&>*]:min-w-0">
        <x-fi.field label="Bulan" for="period_month" required>
            <x-fi.select name="period_month" id="period_month" required data-fi-select placeholder="Pilih"
                :options="[1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember']"
                oninvalid="this.setCustomValidity('Bulan tidak boleh kosong')" oninput="this.setCustomValidity('')" />
        </x-fi.field>

        <x-fi.field label="Tahun" for="period_year" required>
            <x-fi.input type="number" name="period_year" id="period_year" placeholder="2025" :value="date('Y')"
                required min="2000" max="2100" />
        </x-fi.field>

        <x-fi.field label="Minggu" for="week_number" required>
            <x-fi.select name="week_number" id="week_number" required data-fi-select
                oninvalid="this.setCustomValidity('Minggu tidak boleh kosong')" oninput="this.setCustomValidity('')">
                <option value="">Pilih bulan & tahun terlebih dahulu</option>
            </x-fi.select>
        </x-fi.field>
    </div>

    {{-- Rentang tanggal periode (diisi JS saat minggu dipilih) --}}
    <input type="hidden" name="period_start_date" id="period_start_date" value="">
    <input type="hidden" name="period_end_date" id="period_end_date" value="">

    {{-- Penanda Tangan — per proyek, blok dirender JS (renderSignatorySections).
         Field: signatories[NAMA_PROYEK][disetujui|diperiksa|dibuat] = ID petinggi. --}}
    <section class="rounded-lg ring-1 ring-gray-950/5">
        <header class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3">
            <x-fi.icon name="pencil-square" class="h-5 w-5 text-gray-400" />
            <h3 class="text-sm font-semibold text-gray-950">Penanda Tangan</h3>
            <button type="button" id="apply-common-signatories" disabled
                title="Salin penanda tangan proyek pertama ke semua proyek yang dipilih"
                class="ms-auto inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                Tanda Tangan Sama Semua
            </button>
        </header>
        <div class="grid gap-y-3 p-4">
            <p class="text-sm text-gray-500">Opsional — tidak wajib diisi. Setiap proyek bisa memiliki penanda tangan yang
                berbeda. Data diambil dari modul Data Penandatangan; jika dikosongkan, blok tanda tangan pada PDF
                ditampilkan sebagai garis putus-putus. Gunakan tombol <strong>"Tanda Tangan Sama Semua"</strong> untuk
                menyalin penanda tangan proyek pertama ke seluruh proyek yang dipilih.</p>
            <div id="signatory-sections" class="grid gap-y-4"></div>
        </div>
    </section>

    {{-- Biaya Lain-lain — per proyek, blok dirender JS (renderAdditionalCostSections).
         Field: additional_costs[NAMA_PROYEK][INDEX][name|amount]; total ditambahkan
         ke TOTAL DIBAYARKAN pada cetakan payroll. --}}
    <section class="rounded-lg ring-1 ring-gray-950/5">
        <header class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
            <x-fi.icon name="receipt-percent" class="h-5 w-5 text-gray-400" />
            <h3 class="text-sm font-semibold text-gray-950">Biaya Lain-lain</h3>
        </header>
        <div class="grid gap-y-3 p-4">
            <p class="text-sm text-gray-500">Opsional — tambahkan biaya tambahan per proyek (misal konsumsi, transport,
                dll). Jumlahnya akan ditambahkan ke TOTAL DIBAYARKAN pada cetakan payroll.</p>
            <div id="additional-costs-sections" class="grid gap-y-4"></div>
        </div>
    </section>

    {{-- Loading pengecekan absensi --}}
    <div id="checking-loader" class="hidden rounded-lg bg-gray-50 p-4 text-center ring-1 ring-inset ring-gray-950/5">
        <span class="mx-auto mb-2 block h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent"></span>
        <p class="text-sm text-gray-500">Memeriksa kelengkapan data absensi...</p>
    </div>

    {{-- Payroll sudah ada --}}
    <div id="already-generated-warning" class="hidden rounded-lg bg-amber-50 p-4 ring-1 ring-inset ring-amber-600/20">
        <div class="flex gap-3">
            <x-fi.icon name="exclamation-triangle" class="h-5 w-5 shrink-0 text-amber-600" />
            <div class="min-w-0 flex-1">
                <p class="mb-2 text-sm font-semibold text-amber-800">Payroll Sudah Digenerate</p>
                <div id="already-generated-list" class="max-h-32 overflow-y-auto text-sm text-amber-800"></div>
            </div>
        </div>
    </div>

    {{-- Absensi belum lengkap --}}
    <div id="incomplete-warning" class="hidden rounded-lg bg-red-50 p-4 ring-1 ring-inset ring-red-600/10">
        <div class="flex gap-3">
            <x-fi.icon name="exclamation-circle" class="h-5 w-5 shrink-0 text-red-600" />
            <div class="min-w-0 flex-1">
                <p class="mb-2 text-sm font-semibold text-red-800">Data Absensi Belum Lengkap!</p>
                <div id="incomplete-list" class="max-h-60 space-y-2 overflow-y-auto"></div>
                <p class="mt-3 rounded-md bg-white p-2 text-xs text-red-700 ring-1 ring-inset ring-red-600/20"><strong>Catatan:</strong>
                    Karyawan di atas belum memiliki absensi lengkap untuk semua hari kerjanya. Pastikan data sudah diinput
                    sebelum generate payroll.</p>
            </div>
        </div>
    </div>

    {{-- Karyawan belum punya proyek --}}
    <div id="no-project-warning" class="hidden rounded-lg bg-amber-50 p-4 ring-1 ring-inset ring-amber-600/20">
        <div class="flex gap-3">
            <x-fi.icon name="exclamation-triangle" class="h-5 w-5 shrink-0 text-amber-600" />
            <div class="min-w-0 flex-1">
                <p class="mb-2 text-sm font-semibold text-amber-800">Karyawan Belum Memiliki Proyek!</p>
                <div id="no-project-list" class="max-h-40 space-y-1 overflow-y-auto text-sm text-amber-800"></div>
                <p class="mt-3 rounded-md bg-white p-2 text-xs text-amber-800 ring-1 ring-inset ring-amber-600/20"><strong>Catatan:</strong>
                    Pastikan setiap karyawan sudah memiliki proyek sebelum generate payroll. Lengkapi data proyek di menu
                    <strong>Human Resource → Data Karyawan</strong>.</p>
            </div>
        </div>
    </div>

    {{-- Karyawan dengan data lengkap --}}
    <div id="complete-info" class="hidden rounded-lg bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-600/20">
        <div class="flex gap-3">
            <x-fi.icon name="check-circle" class="h-5 w-5 shrink-0 text-emerald-600" />
            <div class="min-w-0 flex-1">
                <p class="mb-2 text-sm font-semibold text-emerald-800">Karyawan dengan Data Lengkap</p>
                <div id="complete-list" class="max-h-40 space-y-1 overflow-y-auto"></div>
            </div>
        </div>
    </div>

    {{-- Semua data lengkap --}}
    <div id="all-complete" class="hidden rounded-lg bg-emerald-50 p-4 ring-1 ring-inset ring-emerald-600/20">
        <div class="flex gap-3">
            <x-fi.icon name="check-circle" class="h-5 w-5 shrink-0 text-emerald-600" />
            <div>
                <p class="text-sm font-semibold text-emerald-800">Semua Data Lengkap!</p>
                <p class="text-sm text-emerald-700">Semua karyawan memiliki data absensi lengkap untuk periode ini.</p>
            </div>
        </div>
    </div>
</x-fi.modal>
