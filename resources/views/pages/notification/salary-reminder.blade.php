@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Reminder Gaji Karyawan')

@section('content')
    <div class="space-y-6">

        {{-- Filter Section --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <form method="GET" action="{{ route('notification.salary-reminder') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    {{-- Filter Bulan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                        <select id="month-select" name="month"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                        <select id="year-select" name="year"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="status-select" name="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="notified" {{ request('status') == 'notified' ? 'selected' : '' }}>Notified
                            </option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>

                    {{-- Filter Search --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari Karyawan</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="ID atau Nama Karyawan"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>

                    {{-- Sort By Salary --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Urutkan Gaji</label>
                        <select id="sort-select" name="sort_by"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>
                                Terbaru
                            </option>
                            <option value="salary" {{ request('sort_by') == 'salary' ? 'selected' : '' }}>Gaji</option>
                        </select>
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Urutan</label>
                        <select id="order-select" name="sort_order"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Turun</option>
                            <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Naik</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition">
                        Cari
                    </button>
                    <a href="{{ route('notification.salary-reminder') }}"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-600 font-medium">Total Reminder</p>
                        <p class="text-3xl font-bold text-blue-900">{{ $totalReminders }}</p>
                    </div>
                    <div class="bg-blue-200 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a6 6 0 100 12A6 6 0 0010 2zM9 9a1 1 0 112 0 1 1 0 01-2 0z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-yellow-600 font-medium">Notified</p>
                        <p class="text-3xl font-bold text-yellow-900">{{ $totalNotified }}</p>
                    </div>
                    <div class="bg-yellow-200 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 9a1 1 0 112 0 1 1 0 01-2 0z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-600 font-medium">Paid</p>
                        <p class="text-3xl font-bold text-green-900">{{ $totalPaid }}</p>
                    </div>
                    <div class="bg-green-200 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10 2a8 8 0 100 16 8 8 0 000-16zm3.707 9.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L9 13.586l3.293-3.293a1 1 0 011.414 0z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-purple-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">ID Karyawan</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Nama Karyawan</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Periode</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Gaji (Rp)</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tanggal Reminder</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Notifikasi Dikirim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($reminders as $reminder)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $reminder->employee_id }}</td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                    {{ $reminder->employee->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    {{ date('F Y', mktime(0, 0, 0, $reminder->period_month, 1, $reminder->period_year)) }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    @if ($reminder->employee)
                                        Rp {{ number_format($reminder->employee->base_salary, 0, ',', '.') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    {{ $reminder->reminder_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($reminder->status === 'pending')
                                        <span
                                            class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                            Pending
                                        </span>
                                    @elseif ($reminder->status === 'notified')
                                        <span
                                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                                            Notified
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                            Paid
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    @if ($reminder->notification_sent_at)
                                        {{ $reminder->notification_sent_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
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
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                {{ $reminders->links() }}
            </div>
        </div>

    </div>
@endsection
