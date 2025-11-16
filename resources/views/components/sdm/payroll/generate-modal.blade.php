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
                    <li>Potongan: Rp 30.000 per hari untuk Izin/Sakit/Cuti</li>
                    <li>Lembur dihitung otomatis dari data lembur yang ada</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-gray-700 mb-1">Bulan <span class="text-error">*</span></label>
        <select name="period_month" class="w-full border rounded p-2" required
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
        <input type="number" name="period_year" class="w-full border rounded p-2" placeholder="Contoh: 2025"
            value="{{ date('Y') }}" required min="2000" max="2100"
            oninvalid="this.setCustomValidity('Tahun tidak boleh kosong')" oninput="this.setCustomValidity('')">
    </div>
</x-modal>
