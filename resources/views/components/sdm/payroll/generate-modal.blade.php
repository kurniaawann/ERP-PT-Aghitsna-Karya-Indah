{{-- Modal Generate Payroll --}}
<x-modal id="generateModal" title="Generate Payroll" action="{{ route('payroll.generate') }}" method="POST"
    buttonText="Generate">

    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-info-circle text-blue-600 mt-1"></i>
            <div class="text-sm text-blue-800">
                <p class="font-semibold mb-1">Informasi:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Sistem akan menghitung payroll untuk semua karyawan aktif</li>
                    <li>Data diambil berdasarkan absensi pada periode yang dipilih</li>
                    <li>Potongan: Rp 30.000 per hari untuk Izin/Sakit (Cuti tidak dipotong)</li>
                    <li>Lembur dihitung otomatis dari data lembur yang ada</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Bulan <span class="text-error">*</span></label>
        <select name="period_month" id="period_month" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Bulan tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Bulan</option>
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

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Tahun <span class="text-error">*</span></label>
        <input type="number" name="period_year" id="period_year" class="w-full border rounded p-2"
            placeholder="Contoh: 2025" value="{{ date('Y') }}" required min="2000" max="2100"
            oninvalid="this.setCustomValidity('Tahun tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>

    {{-- Loading State --}}
    <div id="checking-loader" class="hidden mb-3 p-4 bg-gray-50 border border-gray-200 rounded-lg text-center">
        <i class="fa-solid fa-spinner fa-spin text-blue-600 text-2xl mb-2"></i>
        <p class="text-sm text-gray-600">Memeriksa kelengkapan data absensi...</p>
    </div>

    {{-- Warning - Payroll Sudah Ada --}}
    <div id="already-generated-warning" class="hidden mb-3 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-exclamation-triangle text-yellow-600 text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-yellow-800 text-sm mb-2">⚠️ Payroll Sudah Digenerate</p>
                <div id="already-generated-list" class="text-sm text-yellow-700 max-h-32 overflow-y-auto"></div>
            </div>
        </div>
    </div>

    {{-- Warning - Data Absensi Tidak Lengkap --}}
    <div id="incomplete-warning" class="hidden mb-3 p-4 bg-red-50 border-l-4 border-red-500 rounded">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-exclamation-circle text-red-600 text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-red-800 text-sm mb-2">⚠️ Data Absensi Belum Lengkap!</p>
                <div id="incomplete-list" class="max-h-80 overflow-y-auto space-y-3">
                    <!-- Will be filled by JavaScript -->
                </div>
                <div class="mt-3 p-2 bg-white rounded border border-red-300">
                    <p class="text-xs text-red-700"><strong>Catatan:</strong> Karyawan di atas belum memiliki data
                        absensi lengkap. Pastikan semua tanggal sudah diinput sebelum generate payroll.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Success - Semua Data Lengkap --}}
    <div id="all-complete" class="hidden mb-3 p-4 bg-green-50 border-l-4 border-green-500 rounded">
        <div class="flex items-start gap-2">
            <i class="fa-solid fa-check-circle text-green-600 text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-green-800 text-sm mb-1">✅ Semua Data Lengkap!</p>
                <p class="text-sm text-green-700">Semua karyawan memiliki data absensi lengkap untuk periode ini.</p>
            </div>
        </div>
    </div>
</x-modal>
