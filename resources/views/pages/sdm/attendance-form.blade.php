{{-- =====================================================================
     Halaman: Tambah Absensi (gaya Filament) — pengganti modal Tambah lama.
     Pilih beberapa karyawan + rentang tanggal → grid karyawan × tanggal
     (default Hadir; bisa diubah Izin/Sakit/Cuti per hari) dirender JS.
     Submit → route('attendance.store') (logic sama seperti sebelumnya).
     Bila validasi gagal, karyawan, tanggal, status grid, dan keterangan
     dikembalikan dari old input.

     Data dari AttendanceController@create:
     - $employees          : karyawan harian (pilihan karyawan)
     - $existingAttendance : absensi yang sudah ada (validasi duplikat klien)

     Hook JS (resources/js/pages/sdm/attendance/index.js): form#attendanceAddForm,
     #start_date, #end_date, #date-error, #attendance-grid,
     #duplicate-warning / #duplicate-warning-text, .searchable-multi-*.
     ===================================================================== --}}
@extends('layouts.app')

@php
    $indexUrl = route('attendance.index');
@endphp

@section('title', 'PT Aghitsna Karya Indah - Tambah Absensi')

@section('content')
    <div class="flex flex-col gap-y-6">

        {{-- SECTION: Header Halaman --}}
        <x-fi.page-header heading="Tambah Absensi"
            :breadcrumbs="['Human Resource' => null, 'Data Absensi' => $indexUrl, 'Tambah' => null]" />

        {{-- SECTION: Ringkasan error validasi server --}}
        <x-fi.validation-errors />

        <form id="attendanceAddForm" method="POST" action="{{ route('attendance.store') }}" class="flex flex-col gap-y-6">
            @csrf

            {{-- SECTION: Karyawan & Periode --}}
            <x-fi.section heading="Karyawan & Periode">
                <div class="grid grid-cols-1 gap-x-4 gap-y-5 lg:grid-cols-2">
                    <x-fi.multi-select name="employee_ids" id="add-employee_ids" label="Pilih Karyawan" :required="true"
                        placeholder="Cari karyawan..." class="lg:col-span-2" :selected="old('employee_ids', [])"
                        :options="$employees->map(fn ($e) => ['value' => $e->employee_code, 'label' => $e->name . ' - ' . $e->employee_code])->values()" />

                    <x-fi.field label="Tanggal Mulai" for="start_date" required hint="Tanggal tidak boleh lebih dari hari ini">
                        <x-fi.input type="date" name="start_date" id="start_date" :max="date('Y-m-d')" required
                            :value="old('start_date')"
                            oninvalid="this.setCustomValidity('Tanggal mulai tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                    </x-fi.field>

                    <x-fi.field label="Tanggal Akhir" for="end_date" required hint="Untuk 1 hari, isi tanggal yang sama">
                        <x-fi.input type="date" name="end_date" id="end_date" :max="date('Y-m-d')" required
                            :value="old('end_date')"
                            oninvalid="this.setCustomValidity('Tanggal akhir tidak boleh kosong')" oninput="this.setCustomValidity('')" />
                        <p id="date-error" class="hidden text-sm text-red-600">Tanggal akhir tidak boleh lebih kecil dari tanggal mulai!</p>
                    </x-fi.field>
                </div>
            </x-fi.section>

            {{-- Info default hadir --}}
            <div class="flex gap-3 rounded-xl bg-blue-50 p-4 text-sm text-gray-700 ring-1 ring-inset ring-blue-600/10">
                <x-fi.icon name="information-circle" class="h-5 w-5 shrink-0 text-primary" />
                <p>Setiap hari untuk karyawan terpilih <strong>default Hadir</strong>. Ubah sesuai kebutuhan:
                    Izin, Sakit, atau Cuti. Karyawan kantor (gaji bulanan) tidak perlu absensi &mdash; kehadirannya
                    direkap di <strong>Slip Gaji</strong>.</p>
            </div>

            {{-- SECTION: Grid Absensi per karyawan × tanggal (dirender JS) --}}
            <div id="attendance-grid">
                <p class="text-sm text-gray-500" id="attendance-grid-hint">
                    Pilih karyawan dan rentang tanggal untuk menampilkan grid absensi.
                </p>
            </div>

            {{-- SECTION: Keterangan --}}
            <x-fi.section>
                <x-fi.field label="Keterangan" for="add-attendance-notes">
                    <x-fi.textarea id="add-attendance-notes" name="notes" rows="2"
                        placeholder="Masukkan keterangan (opsional)">{{ old('notes') }}</x-fi.textarea>
                </x-fi.field>
            </x-fi.section>

            {{-- Peringatan absensi duplikat (validasi sisi klien) --}}
            <div id="duplicate-warning" class="hidden rounded-xl bg-red-50 p-4 ring-1 ring-inset ring-red-600/10">
                <div class="flex gap-3">
                    <x-fi.icon name="exclamation-circle" class="h-5 w-5 shrink-0 text-red-600" />
                    <div>
                        <p class="text-sm font-semibold text-red-800">Data Absensi Sudah Ada!</p>
                        <p id="duplicate-warning-text" class="mt-1 text-sm text-red-700"></p>
                    </div>
                </div>
            </div>

            {{-- SECTION: Aksi Form (kanan bawah: [Batal] [Simpan]) --}}
            <div class="flex flex-wrap items-center justify-end gap-3">
                <x-fi.button color="gray" :href="$indexUrl">Batal</x-fi.button>
                <x-fi.button type="submit" id="submit-btn-attendanceAdd">Simpan</x-fi.button>
            </div>
        </form>
    </div>

    @push('scripts')
        {{-- Data absensi yang sudah ada (validasi duplikat) + status grid dari old input --}}
        @php
            $attendanceConfig = [
                'existingAttendance' => $existingAttendance ?? [],
                'getWeeksUrl' => route('payroll.get-weeks'),
                'oldAttendance' => old('attendance', []),
            ];
        @endphp
        <script>
            window.attendanceConfig = @json($attendanceConfig);
        </script>
        @vite('resources/js/pages/sdm/attendance/index.js')
    @endpush
@endsection
