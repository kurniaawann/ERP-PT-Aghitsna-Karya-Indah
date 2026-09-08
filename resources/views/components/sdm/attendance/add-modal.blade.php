{{--
    Modal Tambah Absensi (Bulk Create per Hari)
    Memilih beberapa karyawan menggunakan searchable multi-select, rentang
    tanggal, lalu grid per karyawan × per tanggal ditampilkan. Setiap sel
    default ke 'Hadir' dan bisa diubah ke Izin/Sakit/Cuti per hari.
--}}
<x-modal id="addModal" title="Tambah Absensi" action="{{ route('attendance.store') }}" method="POST" buttonText="Simpan" size="6xl">

    {{-- Pilih Karyawan (Searchable Multi-Select) --}}
    <x-forms.searchable-multi-select name="employee_ids"
        id="add-employee_ids" label="Pilih Karyawan" :required="true"
        placeholder="Cari karyawan..."
        :options="$employees->map(fn($e) => ['value' => $e->employee_code, 'label' => $e->name . ' - ' . $e->employee_code])->values()" />

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Tanggal Mulai --}}
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tanggal Mulai <span class="text-error">*</span></label>
            <input type="date" name="start_date" id="start_date" class="w-full border rounded p-2"
                max="{{ date('Y-m-d') }}" required oninvalid="this.setCustomValidity('Tanggal mulai tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
            <p class="text-xs text-text-secondary mt-1">Tanggal tidak boleh lebih dari hari ini</p>
        </div>

        {{-- Tanggal Akhir --}}
        <div class="mb-3">
            <label class="block text-text-primary mb-1">Tanggal Akhir <span class="text-error">*</span></label>
            <input type="date" name="end_date" id="end_date" class="w-full border rounded p-2" max="{{ date('Y-m-d') }}"
                required oninvalid="this.setCustomValidity('Tanggal akhir tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
            <p class="text-xs text-text-secondary mt-1">Untuk 1 hari, isi tanggal yang sama</p>
            <p id="date-error" class="text-xs text-red-600 mt-1 hidden">Tanggal akhir tidak boleh lebih kecil dari tanggal
                mulai!</p>
        </div>
    </div>

    {{-- Info default hadir --}}
    <div class="mb-3 rounded-lg bg-surface-secondary border border-border p-3 text-sm text-text-secondary">
        <i class="fa-solid fa-circle-info text-primary mr-1"></i>
        Setiap hari untuk karyawan terpilih <strong>default Hadir</strong>. Ubah sesuai kebutuhan:
        Izin, Sakit, atau Cuti.
    </div>

    {{-- Grid Absensi per karyawan × tanggal --}}
    <div id="attendance-grid" class="mb-3">
        <p class="text-sm text-text-secondary" id="attendance-grid-hint">
            Pilih karyawan dan rentang tanggal untuk menampilkan grid absensi.
        </p>
    </div>

    {{-- Keterangan --}}
    <div class="mb-3">
        <label class="block text-text-primary mb-1">Keterangan</label>
        <textarea name="notes" class="w-full border rounded p-2" placeholder="Masukkan keterangan (opsional)" rows="2"></textarea>
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
