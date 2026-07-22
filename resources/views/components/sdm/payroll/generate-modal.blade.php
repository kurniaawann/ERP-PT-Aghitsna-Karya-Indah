{{--
    Generate Payroll Modal

    Modal form for generating weekly payroll for all active employees.

    Process:
    1. User selects month, year, week, and optional project name
    2. Auto-checks attendance completeness via AJAX (PayrollController@checkAttendanceCompleteness)
    3. Shows validation results:
       - Complete employees (all days filled)
       - Incomplete employees (missing attendance days)
       - Already generated employees (skip)
       - Kasbon issues (exceeds salary)
    4. Generate button only enabled if all conditions pass (can_generate = true)
    5. Additional expenses can be added dynamically (token listrik, air, etc.)
    6. On submit, posts to PayrollController@generate

    Frontend JS: partials/sdm/payroll-scripts.blade.php (checkAttendanceData function)
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
                    <li>Sistem menghitung upah harian × hari masuk untuk pekerja harian</li>
                    <li>Data diambil dari absensi minggu yang dipilih (Senin-Sabtu)</li>
                    <li><strong>Tidak masuk = tidak dapat upah hari itu</strong></li>
                    <li><strong class="text-error">Setiap karyawan harus memiliki absensi lengkap sesuai hari
                            kerjanya</strong></li>
                    <li>Kasbon hanya dipotong jika karyawan sudah membayar</li>
                    <li>Bisa menambahkan pengeluaran (token listrik/air, dll)</li>
                </ul>
            </div>
        </div>
    </div>

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

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Nama Proyek <span
                class="text-text-tertiary text-sm">(Opsional)</span></label>
        <input type="text" name="project_name" id="project_name"
            class="w-full border border-border-strong rounded p-2 bg-surface-base text-text-input"
            placeholder="Contoh: Lampsr Tanap 3, Proyek ABC, dll" maxlength="255">
        <p class="text-xs text-text-secondary mt-1">Kosongkan jika tidak ada proyek tertentu</p>
    </div>

    <div class="mb-3">
        <div class="flex justify-between items-center mb-2">
            <label class="block text-text-primary font-semibold">Pengeluaran Tambahan (Opsional)</label>
            <button type="button" onclick="addExpenseItem()"
                class="text-sm bg-success hover:bg-success-hover text-white px-3 py-1 rounded-lg flex items-center gap-1">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        <div id="expense-items-container" class="space-y-2">
            <!-- Expense items will be added here dynamically -->
            <p class="text-sm text-text-secondary text-center py-4" id="no-expense-text">
                Belum ada pengeluaran tambahan. Klik "Tambah Item" untuk menambahkan.
            </p>
        </div>

        <!-- Hidden inputs for form submission -->
        <input type="hidden" name="additional_expenses" id="total_additional_expenses" value="0">
        <input type="hidden" name="additional_expenses_notes" id="additional_expenses_notes" value="">

        <!-- Total Display -->
        <div class="mt-3 p-3 bg-surface-secondary rounded-lg border border-border-strong">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-text-primary">Total Pengeluaran Tambahan:</span>
                <span class="text-lg font-bold text-primary" id="total-expense-display">Rp 0</span>
            </div>
        </div>
    </div>

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
