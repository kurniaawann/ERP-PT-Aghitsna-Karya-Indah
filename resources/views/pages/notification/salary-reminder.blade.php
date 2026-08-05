{{-- =====================================================================
     Halaman: Reminder Gaji Karyawan
     Tujuan: Menampilkan daftar pengingat gaji karyawan (salary reminder)
             dengan filter bulan/tahun/status/pencarian, kartu ringkasan
             (Total, Draft, Paid), tabel reminder, serta section terpisah
             "Pengingat Payroll Belum Dibuat" untuk karyawan yang sudah
             absen minggu 1-4 namun payrollnya belum dibuat.
     Data dari SalaryReminderController@index (logic di SalaryReminderService):
     - $reminders           : paginator salary reminder (employee_id, periode,
                              gaji, reminder_date, status, notification_sent_at)
     - $totalReminders      : total reminder sesuai filter
     - $totalDraft          : reminder berstatus draft (belum dibayar)
     - $totalPaid           : reminder berstatus paid
     - $attendanceReminders : karyawan sudah absen minggu 1-4 tapi payroll
                              belum dibuat (employee_id, employee_name,
                              period_month, period_year, week_number,
                              first/last_attendance_date)
     Filter (GET): month, year, status (draft/paid), search
     Komponen yang di-include:
     - layouts.app
     JS yang di-load:
     - resources/js/pages/notification/salary-reminder/index.js
     ===================================================================== --}}
@extends('layouts.app')

{{-- Judul Halaman --}}
@section('title', 'PT Aghitsna Karya Indah - Reminder Gaji Karyawan')

@section('content')
    <div class="space-y-6">

        {{-- Form Filter: Bulan, Tahun, Status, Pencarian --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <form id="filterForm" method="GET" action="{{ route('notification.salary-reminder') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                    {{-- Filter Bulan --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Bulan</label>
                        <select id="month-select" name="month"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Semua Bulan</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Filter Tahun --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Tahun</label>
                        <select id="year-select" name="year"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent">
                            @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                <option value="{{ $i }}"
                                    {{ request('year', date('Y')) == $i ? 'selected' : '' }}>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    {{-- Filter Status --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Status</label>
                        <select id="status-select" name="status"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>

                    {{-- Pencarian Karyawan --}}
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Cari Karyawan</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="ID atau Nama Karyawan"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                </div>

                {{-- Tombol Reset Filter --}}
                <div class="flex gap-2">
                    <a href="{{ route('notification.salary-reminder') }}"
                        class="px-4 py-2 bg-surface-secondary text-text-primary rounded-lg hover:bg-surface-hover transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Kartu Ringkasan: Total, Draft, Paid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Total Reminder --}}
            <div class="bg-surface-base p-6 rounded-xl shadow border border-border-light">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary font-medium">Total Reminder</p>
                        <p class="text-3xl font-bold text-text-primary">{{ $totalReminders }}</p>
                    </div>
                    <div class="bg-primary-light p-3 rounded-lg">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Draft (Belum Dibayar) --}}
            <div class="bg-surface-base p-6 rounded-xl shadow border border-border-light">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary font-medium">Draft (Belum Dibayar)</p>
                        <p class="text-3xl font-bold text-text-primary">{{ $totalDraft }}</p>
                    </div>
                    <div class="bg-warning-light p-3 rounded-lg">
                        <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Paid --}}
            <div class="bg-surface-base p-6 rounded-xl shadow border border-border-light">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary font-medium">Paid</p>
                        <p class="text-3xl font-bold text-text-primary">{{ $totalPaid }}</p>
                    </div>
                    <div class="bg-success-light p-3 rounded-lg">
                        <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel Data Salary Reminder --}}
        {{-- ============================================================
             SECTION: DAFTAR REMINDER GAJI
             Tabel daftar reminder gaji karyawan.
             - Kolom status menampilkan BADGE warna: Draft (kuning) jika
               $reminder->status === 'draft', selainnya Paid (hijau)
             - Gaji diambil dari relasi payroll->base_salary, fallback ke
               daily_wage/base_salary employee
             - Tanggal perubahan diisi dari notification_sent_at
             ============================================================ --}}
        <div class="bg-surface-base rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    {{-- Header Tabel --}}
                    <thead class="bg-surface-secondary border-b border-border-light">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">ID Karyawan</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Nama Karyawan</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Periode</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Gaji (Rp)</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Tanggal Reminder</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Tanggal Perubahan</th>
                        </tr>
                    </thead>

                    {{-- Baris Data --}}
                    <tbody class="divide-y divide-border-light">
                        @forelse($reminders as $reminder)
                            <tr class="hover:bg-surface-secondary transition">
                                {{-- ID Karyawan --}}
                                <td class="px-6 py-3 text-sm text-text-secondary">{{ $reminder->employee_id }}</td>

                                {{-- Nama Karyawan --}}
                                <td class="px-6 py-3 text-sm font-medium text-text-primary">
                                    {{ $reminder->employee->name ?? 'N/A' }}
                                </td>

                                {{-- Periode --}}
                                <td class="px-6 py-3 text-sm text-text-secondary">
                                    {{ date('F Y', mktime(0, 0, 0, $reminder->period_month, 1, $reminder->period_year)) }}
                                </td>

                                {{-- Gaji --}}
                                <td class="px-6 py-3 text-sm text-text-secondary">
                                    @if ($reminder->payroll)
                                        Rp {{ number_format($reminder->payroll->base_salary, 0, ',', '.') }}
                                    @elseif ($reminder->employee)
                                        Rp
                                        {{ number_format($reminder->employee->daily_wage ?? ($reminder->employee->base_salary ?? 0), 0, ',', '.') }}
                                    @else
                                        N/A
                                    @endif
                                </td>

                                {{-- Tanggal Reminder --}}
                                <td class="px-6 py-3 text-sm text-text-secondary">
                                    {{ $reminder->reminder_date->format('d/m/Y') }}
                                </td>

                                {{-- Status (Badge) --}}
                                <td class="px-6 py-3 text-sm">
                                    @if ($reminder->status === 'draft')
                                        <span
                                            class="px-3 py-1 bg-warning-light text-warning rounded-full text-xs font-semibold">
                                            Draft
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 bg-success-light text-success rounded-full text-xs font-semibold">
                                            Paid
                                        </span>
                                    @endif
                                </td>

                                {{-- Tanggal Perubahan --}}
                                <td class="px-6 py-3 text-sm text-text-secondary">
                                    @if ($reminder->notification_sent_at)
                                        {{ $reminder->notification_sent_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-text-secondary">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            {{-- Pesan jika tidak ada data --}}
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-text-secondary">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-text-secondary" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <p>Tidak ada data reminder gaji</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="bg-surface-secondary px-6 py-4 border-t border-border-light">
                {{ $reminders->links() }}
            </div>
        </div>

        {{-- Section: Pengingat Payroll Belum Dibuat --}}
        @if (count($attendanceReminders) > 0)
            <div class="space-y-4 mt-6">
                {{-- Header Section --}}
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4v2m0-11a9 9 0 110 18 9 9 0 010-18z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-text-primary">Pengingat Payroll Belum Dibuat</h3>
                </div>

                {{-- Deskripsi --}}
                <p class="text-sm text-text-secondary">Karyawan berikut sudah memiliki absensi minggu 1-4 namun payroll
                    belum
                    dibuatkan:</p>

                {{-- Tabel Attendance Reminder --}}
                <div class="bg-surface-base rounded-xl shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            {{-- Header Tabel --}}
                            <thead class="bg-surface-secondary border-b border-border-light">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">ID Karyawan
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Nama Karyawan
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Periode</th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Minggu Ke-
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Tanggal
                                        Absensi
                                    </th>
                                    <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Status</th>
                                </tr>
                            </thead>

                            {{-- Baris Data --}}
                            <tbody class="divide-y divide-border-light">
                                @forelse($attendanceReminders as $reminder)
                                    <tr class="hover:bg-surface-secondary transition">
                                        {{-- ID Karyawan --}}
                                        <td class="px-6 py-3 text-sm text-text-secondary">{{ $reminder->employee_id }}
                                        </td>

                                        {{-- Nama Karyawan --}}
                                        <td class="px-6 py-3 text-sm font-medium text-text-primary">
                                            {{ $reminder->employee_name }}
                                        </td>

                                        {{-- Periode --}}
                                        <td class="px-6 py-3 text-sm text-text-secondary">
                                            {{ date('F Y', mktime(0, 0, 0, $reminder->period_month, 1, $reminder->period_year)) }}
                                        </td>

                                        {{-- Minggu Ke --}}
                                        <td class="px-6 py-3 text-sm">
                                            <span
                                                class="px-3 py-1 bg-warning-light text-warning rounded-full text-xs font-semibold">
                                                Minggu {{ $reminder->week_number }}
                                            </span>
                                        </td>

                                        {{-- Tanggal Absensi --}}
                                        <td class="px-6 py-3 text-sm text-text-secondary">
                                            {{ $reminder->first_attendance_date->format('d/m/Y') }} -
                                            {{ $reminder->last_attendance_date->format('d/m/Y') }}
                                        </td>

                                        {{-- Status --}}
                                        <td class="px-6 py-3 text-sm">
                                            <span
                                                class="px-3 py-1 bg-error-light text-error rounded-full text-xs font-semibold">
                                                Payroll Belum Dibuat
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    {{-- Pesan jika semua payroll sudah dibuat --}}
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-text-secondary">
                                            <p>Semua payroll untuk absensi sudah dibuat</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- JavaScript Modular --}}
    @push('scripts')
        @vite('resources/js/pages/notification/salary-reminder/index.js')
    @endpush
@endsection
