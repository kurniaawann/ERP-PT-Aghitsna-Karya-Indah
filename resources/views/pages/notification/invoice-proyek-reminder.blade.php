@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Reminder Jatuh Tempo Invoice Proyek')

@section('content')
    <div class="space-y-6">

        {{-- Filter Section --}}
        <div class="bg-white p-6 rounded-xl shadow">
            <form id="filterForm" method="GET" action="{{ route('notification.invoice-proyek-reminder') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bulan</label>
                        <select id="month-select" name="month"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            onchange="document.getElementById('filterForm').submit()">
                            <option value="">Semua Bulan</option>
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                        <select id="year-select" name="year"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            onchange="document.getElementById('filterForm').submit()">
                            @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                <option value="{{ $i }}"
                                    {{ request('year', date('Y')) == $i ? 'selected' : '' }}>
                                    {{ $i }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="status-select" name="status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            onchange="document.getElementById('filterForm').submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="notified" {{ request('status') == 'notified' ? 'selected' : '' }}>Kadaluarsa
                            </option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cari Invoice/Penerima</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="No Invoice atau Nama Penerima"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            onkeyup="this.form.submit()">
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('notification.invoice-proyek-reminder') }}"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-600 font-medium">Total Reminder</p>
                        <p class="text-3xl font-bold text-blue-900">{{ $totalReminders }}</p>
                    </div>
                    <div class="bg-blue-200 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-yellow-600 font-medium">Pending</p>
                        <p class="text-3xl font-bold text-yellow-900">{{ $totalPending }}</p>
                    </div>
                    <div class="bg-yellow-200 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-xl shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-orange-600 font-medium">Kadaluarsa</p>
                        <p class="text-3xl font-bold text-orange-900">{{ $totalExpired }}</p>
                    </div>
                    <div class="bg-orange-200 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alert untuk invoice yang sudah jatuh tempo --}}
        @if ($totalExpired > 0)
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg shadow">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">
                            <strong>⚠️ Ada {{ $totalExpired }} invoice yang sudah kadaluarsa!</strong> Segera lakukan
                            pelunasan.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Table Section --}}
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-purple-100 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">No Invoice</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Penerima</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tgl Invoice</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tgl Jatuh Tempo</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Total Invoice (Rp)</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Terbayar (Rp)</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Sisa Pembayaran (Rp)</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tgl Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($reminders as $reminder)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $reminder->invoice_number }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $reminder->recipient ?? '-' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $reminder->invoice_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    <span class="{{ $reminder->is_overdue ? 'font-bold text-red-600' : '' }}">
                                        {{ $reminder->reminder_date->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right text-gray-600">
                                    Rp {{ number_format($reminder->net_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-right text-gray-600">
                                    Rp {{ number_format($reminder->paid_amount, 0, ',', '.') }}
                                </td>
                                <td
                                    class="px-6 py-3 text-sm text-right font-semibold {{ $reminder->remaining_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    Rp {{ number_format($reminder->remaining_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($reminder->display_status === 'paid')
                                        <span
                                            class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Paid</span>
                                    @elseif($reminder->display_status === 'expired')
                                        <span
                                            class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-xs font-semibold">Kadaluarsa</span>
                                    @else
                                        <span
                                            class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">Pending</span>
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
                                <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <p>Tidak ada data reminder invoice proyek</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white px-6 py-4 border-t border-gray-200">
                {{ $reminders->links() }}
            </div>
        </div>

    </div>
@endsection
