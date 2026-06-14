{{-- Modal Tambah Absensi (Multi Select) --}}
<x-modal id="addModal" title="Tambah Absensi" action="{{ route('attendance.store') }}" method="POST" buttonText="Simpan">

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Pilih Karyawan <span class="text-error">*</span></label>
        <input type="text" id="employee-search" placeholder="Cari nama atau divisi..."
            class="w-full border rounded p-2 mb-2 text-sm" oninput="filterEmployees(this.value)">
        <div class="border rounded p-3 max-h-48 overflow-y-auto bg-surface-secondary">
            <div class="mb-2">
                <label class="flex items-center gap-2 cursor-pointer hover:bg-surface-hover p-2 rounded">
                    <input type="checkbox" id="selectAllEmployees" class="w-4 h-4 accent-primary">
                    <span class="font-semibold">Pilih Semua</span>
                </label>
            </div>
            <hr class="my-2">
            @foreach ($employees as $employee)
                <label class="flex items-center gap-2 cursor-pointer hover:bg-surface-hover p-2 rounded employee-item"
                    data-name="{{ strtolower($employee->name) }}"
                    data-division="{{ strtolower($employee->division ?? '') }}">
                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->employee_code }}"
                        class="w-4 h-4 accent-primary employee-checkbox">
                    <span>{{ $employee->name }} - {{ $employee->employee_code }}
                        @if ($employee->division)
                            <span class="text-xs text-text-secondary">({{ $employee->division }})</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-text-secondary mt-1">Pilih satu atau lebih karyawan</p>
        <p id="employee-error" class="text-xs text-red-600 mt-1 hidden">Silakan pilih minimal 1 karyawan!</p>
    </div>

    <script>
        function filterEmployees(query) {
            const q = query.toLowerCase().trim();
            document.querySelectorAll('.employee-item').forEach(item => {
                const name = item.dataset.name || '';
                const division = item.dataset.division || '';
                const match = !q || name.includes(q) || division.includes(q);
                item.style.display = match ? '' : 'none';
            });
        }
    </script>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Mulai <span class="text-error">*</span></label>
        <input type="date" name="start_date" id="start_date" class="w-full border rounded p-2"
            max="{{ date('Y-m-d') }}" required oninvalid="this.setCustomValidity('Tanggal mulai tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Tanggal tidak boleh lebih dari hari ini</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Tanggal Akhir <span class="text-error">*</span></label>
        <input type="date" name="end_date" id="end_date" class="w-full border rounded p-2" max="{{ date('Y-m-d') }}"
            required oninvalid="this.setCustomValidity('Tanggal akhir tidak boleh kosong')"
            oninput="this.setCustomValidity('')">
        <p class="text-xs text-text-secondary mt-1">Untuk 1 hari, isi tanggal yang sama</p>
        <p id="date-error" class="text-xs text-red-600 mt-1 hidden">Tanggal akhir tidak boleh lebih kecil dari tanggal
            mulai!</p>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Status <span class="text-error">*</span></label>
        <select name="status" class="w-full border rounded p-2" required
            oninvalid="this.setCustomValidity('Status tidak boleh kosong')" oninput="this.setCustomValidity('')">
            <option value="">Pilih Status</option>
            <option value="Hadir">Hadir</option>
            <option value="Izin">Izin</option>
            <option value="Sakit">Sakit</option>
            <option value="Alfa">Alfa</option>
            <option value="Cuti">Cuti</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Masukkan keterangan (opsional)" rows="3"></textarea>
    </div>

    {{-- Error Message untuk Duplicate Attendance --}}
    <div id="duplicate-warning" class="hidden mb-3 p-3 bg-red-50 border-l-4 border-red-500 rounded">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-red-500 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <div>
                <p class="font-semibold text-red-800 text-sm mb-1">Data Absensi Sudah Ada!</p>
                <p id="duplicate-warning-text" class="text-sm text-red-700"></p>
            </div>
        </div>
    </div>
</x-modal>
