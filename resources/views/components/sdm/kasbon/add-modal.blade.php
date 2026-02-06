{{-- Modal Tambah Kasbon --}}
<x-modal id="addModal" title="Tambah Kasbon" action="{{ route('kasbon.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex gap-2">
            <i class="fa-solid fa-info-circle text-blue-600 mt-1"></i>
            <div class="text-sm text-blue-800">
                <p class="font-semibold mb-1">Informasi Kasbon:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li><strong>Personal:</strong> Kasbon untuk 1 orang karyawan</li>
                    <li><strong>Tim:</strong> Kasbon untuk divisi tertentu (dibagi rata saat payroll)</li>
                    <li>Kasbon akan otomatis dipotong saat generate payroll</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jenis Kasbon <span class="text-error">*</span></label>
        <select name="kasbon_type" id="add_kasbon_type" class="w-full border rounded p-2" required
            onchange="toggleEmployeeSelect('add')" oninvalid="this.setCustomValidity('Jenis kasbon tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
            <option value="">Pilih Jenis</option>
            <option value="personal">Per Orang</option>
            <option value="team">Per Tim/Divisi</option>
        </select>
    </div>

    <div class="mb-3" id="add_employee_field">
        <label class="block text-text-primary mb-1">Karyawan <span class="text-error">*</span></label>
        <select name="employee_id" id="add_employee_id" class="w-full border rounded p-2">
            <option value="">Pilih Karyawan</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->employee_code }}">{{ $employee->name }} ({{ $employee->employee_code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3" id="add_division_field" style="display: none;">
        <label class="block text-text-primary mb-1">Divisi <span class="text-error">*</span></label>
        <select name="division" id="add_division" class="w-full border rounded p-2">
            <option value="">Pilih Divisi</option>
            @foreach ($divisions as $division)
                <option value="{{ $division->name }}">{{ $division->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Jumlah Kasbon <span class="text-error">*</span></label>
        <input type="number" name="amount" class="w-full border rounded p-2" placeholder="Masukkan jumlah kasbon"
            required min="1000" step="1000" oninvalid="this.setCustomValidity('Jumlah kasbon minimal Rp 1.000')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Kasbon <span class="text-error">*</span></label>
        <input type="date" name="kasbon_date" class="w-full border rounded p-2" value="{{ date('Y-m-d') }}" required
            oninvalid="this.setCustomValidity('Tanggal kasbon tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
    </div>

    <div class="grid grid-cols-3 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Bulan <span class="text-error">*</span></label>
            <select name="period_month" class="w-full border rounded p-2" required>
                <option value="">Pilih</option>
                @for ($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ date('n') == $i ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $i)->format('M') }}
                    </option>
                @endfor
            </select>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Tahun <span class="text-error">*</span></label>
            <input type="number" name="period_year" class="w-full border rounded p-2" value="{{ date('Y') }}"
                required min="2020" max="2100">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Minggu</label>
            <select name="week_number" class="w-full border rounded p-2">
                <option value="">-</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Catatan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Catatan tambahan" rows="3"
            maxlength="500"></textarea>
    </div>
</x-modal>
