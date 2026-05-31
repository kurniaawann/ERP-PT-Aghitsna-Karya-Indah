@extends('layouts.app')

@section('title', 'PT Aghitsna Karya Indah - Reminder Jatuh Tempo Invoice Proyek')

@section('content')
    <div class="space-y-6">

        {{-- Filter Section --}}
        <div class="bg-surface-base p-6 rounded-xl shadow">
            <form id="filterForm" method="GET" action="{{ route('notification.invoice-proyek-reminder') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Bulan</label>
                        <select id="month-select" name="month"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
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
                        <label class="block text-sm font-medium text-text-primary mb-2">Tahun</label>
                        <select id="year-select" name="year"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
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
                        <label class="block text-sm font-medium text-text-primary mb-2">Status</label>
                        <select id="status-select" name="status"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            onchange="document.getElementById('filterForm').submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="notified" {{ request('status') == 'notified' ? 'selected' : '' }}>Kadaluarsa
                            </option>
                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">Cari Invoice/Penerima</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="No Invoice atau Nama Penerima"
                            class="w-full px-4 py-2 border border-border-strong rounded-lg bg-surface-base text-text-input focus:ring-2 focus:ring-primary focus:border-transparent"
                            oninput="this.form.requestSubmit()">
                    </div>
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('notification.invoice-proyek-reminder') }}"
                        class="px-4 py-2 bg-surface-secondary text-text-primary rounded-lg hover:bg-surface-hover transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-surface-base p-6 rounded-xl shadow border border-border-light">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary font-medium">Total Reminder</p>
                        <p class="text-3xl font-bold text-text-primary">{{ $totalReminders }}</p>
                    </div>
                    <div class="bg-primary-light p-3 rounded-lg">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-surface-base p-6 rounded-xl shadow border border-border-light">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary font-medium">Pending</p>
                        <p class="text-3xl font-bold text-text-primary">{{ $totalPending }}</p>
                    </div>
                    <div class="bg-warning-light p-3 rounded-lg">
                        <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-surface-base p-6 rounded-xl shadow border border-border-light">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-text-secondary font-medium">Kadaluarsa</p>
                        <p class="text-3xl font-bold text-text-primary">{{ $totalExpired }}</p>
                    </div>
                    <div class="bg-warning-light p-3 rounded-lg">
                        <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

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

        {{-- Alert untuk invoice yang sudah jatuh tempo --}}
        @if ($totalExpired > 0)
            <div class="bg-error-light border-l-4 border-error p-4 rounded-lg shadow">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-error" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-error">
                            <strong>⚠️ Ada {{ $totalExpired }} invoice yang sudah kadaluarsa!</strong> Segera lakukan
                            pelunasan.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Table Section --}}
        <div class="bg-surface-base rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-secondary border-b border-border-light">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">No Invoice</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Penerima</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Tgl Invoice</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Tgl Jatuh Tempo</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-text-secondary">Total Invoice (Rp)
                            </th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-text-secondary">Terbayar (Rp)</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-text-secondary">Sisa Pembayaran (Rp)
                            </th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Status</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-text-secondary">Tgl Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-light">
                        @forelse($reminders as $reminder)
                            <tr class="hover:bg-surface-secondary transition">
                                <td class="px-6 py-3 text-sm font-medium text-text-primary">
                                    {{ $reminder->invoice_number }}
                                </td>
                                <td class="px-6 py-3 text-sm text-text-secondary">{{ $reminder->recipient ?? '-' }}</td>
                                <td class="px-6 py-3 text-sm text-text-secondary">
                                    {{ $reminder->invoice_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-text-secondary">
                                    <span class="{{ $reminder->is_overdue ? 'font-bold text-error' : '' }}">
                                        {{ $reminder->reminder_date->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right text-text-secondary">
                                    Rp {{ number_format($reminder->net_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-sm text-right text-text-secondary">
                                    Rp {{ number_format($reminder->paid_amount, 0, ',', '.') }}
                                </td>
                                <td
                                    class="px-6 py-3 text-sm text-right font-semibold {{ $reminder->remaining_amount > 0 ? 'text-error' : 'text-success' }}">
                                    Rp {{ number_format($reminder->remaining_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($reminder->display_status === 'paid')
                                        <span
                                            class="px-3 py-1 bg-success-light text-success rounded-full text-xs font-semibold">Paid</span>
                                    @elseif($reminder->display_status === 'expired')
                                        <span
                                            class="px-3 py-1 bg-warning-light text-warning rounded-full text-xs font-semibold">Kadaluarsa</span>
                                    @else
                                        <span
                                            class="px-3 py-1 bg-warning-light text-warning rounded-full text-xs font-semibold">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm text-text-secondary">
                                    @if ($reminder->notification_sent_at)
                                        {{ $reminder->notification_sent_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-text-secondary">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-text-secondary">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-12 h-12 text-text-secondary" fill="none" stroke="currentColor"
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

            <div class="bg-surface-secondary px-6 py-4 border-t border-border-light">
                {{ $reminders->links() }}
            </div>
        </div>

    </div>
@endsection
