{{--
    Generate Payroll Modal

    Modal form for generating weekly payroll for employees of selected project(s).

    Process:
    1. User selects one or more projects (multi-select searchable, same as the
       employee multi-select in the Attendance module), then month, year, and week
    2. Auto-checks attendance completeness via AJAX (PayrollController@checkAttendanceCompleteness)
       — hanya karyawan pada proyek-proyek terpilih yang diperiksa.
    3. Shows validation results:
       - Complete employees (all days filled)
       - Incomplete employees (missing attendance days)
       - Already generated employees (skip)
       - Employees without project (blocked)
    4. Generate button only enabled if all conditions pass (can_generate = true)
    5. On submit, posts project_name[] (array) + period to PayrollController@generate

    Frontend JS: resources/js/pages/sdm/payroll/index.js (checkAttendanceData function)
--}}

{{-- Modal Generate Payroll --}}
<x-modal id="generateModal" title="Generate Payroll" action="{{ route('payroll.generate') }}" method="POST"
    buttonText="Generate">

    <div class="mb-4 p-4 bg-primary-light border border-primary rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-info-circle text-primary mt-1"></i>
            <div class="text-sm text-text-primary">
                <p class="font-semibold mb-1">Informasi Payroll Mingguan:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Payroll dibuat <strong>per proyek (bisa lebih dari satu)</strong> — hanya karyawan pada proyek terpilih yang diproses</li>
                    <li>Sistem menghitung upah harian × hari masuk untuk pekerja harian</li>
                    <li>Data diambil dari absensi minggu yang dipilih (Senin-Sabtu)</li>
                    <li><strong>Tidak masuk = tidak dapat upah hari itu</strong></li>
                    <li><strong class="text-error">Setiap karyawan harus memiliki absensi lengkap sesuai hari
                            kerjanya</strong></li>
                    <li>Kasbon hanya dipotong jika karyawan sudah membayar</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Multi-select proyek (searchable), cara kerjanya sama seperti
         pemilihan karyawan pada modul Absensi. Pilihan disimpan sebagai
         hidden input project_name[] dan dibaca oleh JS (checkAttendanceData). --}}
    <x-forms.searchable-multi-select
        name="project_name"
        id="generate-project_name"
        label="Proyek"
        :required="true"
        placeholder="Cari proyek..."
        :options="$projects->map(fn($p) => ['value' => $p, 'label' => $p])->values()" />

    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Bulan <span class="text-error">*</span></label>
            <select name="period_month" id="period_month"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
                oninvalid="this.setCustomValidity('Bulan tidak boleh kosong')" oninput="this.setCustomValidity('')">
                <option value="">Pilih</option>
                <option value="1">Januari</option>
                <option value="2">Februari</option>
                <option value="3">Maret</option>
                <option value="4">April</option>
                <option value="5">Mei</option>
                <option value="6">Juni</option>
                <option value="7">Juli</option>
                <option value="8">Agustus</option>
                <option value="9">September</option>
                <option value="10">Oktober</option>
                <option value="11">November</option>
                <option value="12">Desember</option>
            </select>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tahun <span class="text-error">*</span></label>
            <input type="number" name="period_year" id="period_year"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
                placeholder="2025" value="{{ date('Y') }}" required min="2000" max="2100">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Minggu <span class="text-error">*</span></label>
            <select name="week_number" id="week_number"
                class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input" required
                oninvalid="this.setCustomValidity('Minggu tidak boleh kosong')" oninput="this.setCustomValidity('')">
                <option value="">Pilih bulan & tahun terlebih dahulu</option>
            </select>
        </div>
    </div>

    {{-- Hidden inputs for period date range (populated by JS when week is selected) --}}
    <input type="hidden" name="period_start_date" id="period_start_date" value="">
    <input type="hidden" name="period_end_date" id="period_end_date" value="">

    {{-- Loading State --}}
    <div id="checking-loader" class="hidden mb-3 p-4 bg-surface-secondary border border-border rounded-lg text-center">
        <i class="fa-solid fa-spinner fa-spin text-primary text-2xl mb-2"></i>
        <p class="text-sm text-text-label">Memeriksa kelengkapan data absensi...</p>
    </div>

    {{-- Warning - Payroll Sudah Ada --}}
    <div id="already-generated-warning" class="hidden mb-3 p-4 bg-warning-light border-l-4 border-warning rounded">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-exclamation-triangle text-warning text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-warning text-sm mb-2">⚠️ Payroll Sudah Digenerate</p>
                <div id="already-generated-list" class="text-sm text-warning max-h-32 overflow-y-auto"></div>
            </div>
        </div>
    </div>

    {{-- Warning - Data Absensi Tidak Lengkap --}}
    <div id="incomplete-warning" class="hidden mb-3 p-4 bg-error-light border-l-4 border-error rounded">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-exclamation-circle text-error text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-error text-sm mb-2">⚠️ Data Absensi Belum Lengkap!</p>
                <div id="incomplete-list" class="max-h-60 overflow-y-auto space-y-2">
                    <!-- Will be filled by JavaScript -->
                </div>
                <div class="mt-3 p-2 bg-surface-base rounded border border-error">
                    <p class="text-xs text-error"><strong>Catatan:</strong> Karyawan di atas belum memiliki absensi
                        lengkap untuk semua hari kerjanya. Pastikan data sudah diinput sebelum generate payroll.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Warning - Karyawan Belum Punya Proyek --}}
    <div id="no-project-warning" class="hidden mb-3 p-4 bg-warning-light border-l-4 border-warning rounded">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-exclamation-triangle text-warning text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-warning text-sm mb-2">⚠️ Karyawan Belum Memiliki Proyek!</p>
                <div id="no-project-list" class="text-sm text-warning max-h-40 overflow-y-auto space-y-1"></div>
                <div class="mt-3 p-2 bg-surface-base rounded border border-warning">
                    <p class="text-xs text-warning"><strong>Catatan:</strong> Pastikan setiap karyawan sudah
                        memiliki proyek sebelum generate payroll. Lengkapi data proyek di menu <strong>Human Resource → Data
                            Karyawan</strong>.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Info - Karyawan dengan Data Lengkap --}}
    <div id="complete-info" class="hidden mb-3 p-4 bg-success-light border-l-4 border-success rounded">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-check-circle text-success text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-success text-sm mb-2">✅ Karyawan dengan Data Lengkap</p>
                <div id="complete-list" class="max-h-40 overflow-y-auto space-y-1">
                    <!-- Will be filled by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    {{-- Success - Semua Data Lengkap --}}
    <div id="all-complete" class="hidden mb-3 p-4 bg-success-light border-l-4 border-success rounded">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-check-circle text-success text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-success text-sm mb-1">✅ Semua Data Lengkap!</p>
                <p class="text-sm text-success">Semua karyawan memiliki data absensi lengkap untuk periode ini.</p>
            </div>
        </div>
    </div>
</x-modal>
