@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <!-- Reminder Cards Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">

        @if ($shouldShowPayrollReminder)
            <!-- Reminder Gaji Karyawan -->
            <div class="bg-surface-base rounded-xl shadow-md border border-border-light overflow-hidden">

                {{-- Reminder Payroll Mingguan --}}
                <div class="bg-warning-light px-6 py-4 border-b border-warning-light">
                    <div class="flex items-center gap-3">
                        <div class="bg-white p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-warning" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-heading">Reminder Gaji Karyawan</h3>
                            <p class="text-sm text-text-secondary">Karyawan dengan gaji belum dibayar (Minggu 1 -
                                {{ $currentWeek }})</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    {{-- Info Periode Minggu Ini --}}
                    <div class="bg-info-light border border-info-light rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-info" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="font-semibold text-text-heading">Periode Payroll: Minggu {{ $currentWeek }}</span>
                        </div>
                        <p class="text-sm text-text-primary">
                            {{ $weekRange['start']->format('d M Y') }} - {{ $weekRange['end']->format('d M Y') }}
                        </p>
                        <p class="text-xs text-info mt-1">
                            Hari ini: {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                        </p>
                    </div>

                    @if (count($employeesWithoutSalary) > 0)
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-2xl font-bold text-warning">{{ count($employeesWithoutSalary) }}</span>
                                <span class="text-sm text-text-secondary">Total Karyawan</span>
                            </div>
                            <div class="w-full bg-warning-light rounded-full h-2">
                                <div class="bg-warning h-2 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            @foreach ($employeesWithoutSalary as $item)
                                <div
                                    class="flex items-start justify-between p-3 bg-warning-light rounded-lg border border-warning-light hover:bg-white transition-colors">
                                    <div class="flex items-start gap-3 flex-1">
                                        <div class="bg-white p-2 rounded-full mt-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-warning"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-text-heading">{{ $item['employee']->name }}</p>
                                            <p class="text-sm text-text-secondary">{{ $item['employee']->employee_code }} -
                                                {{ $item['employee']->position }}</p>

                                            {{-- Tampilkan minggu-minggu yang belum dibayar --}}
                                            <div class="flex flex-wrap gap-1 mt-2">
                                                @foreach ($item['unpaid_weeks'] as $unpaidWeek)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Minggu {{ $unpaidWeek['week_number'] }}
                                                        ({{ $unpaidWeek['start_date'] }}-{{ $unpaidWeek['end_date'] }})
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right ml-3">
                                        <span class="text-sm font-medium text-warning whitespace-nowrap">Rp
                                            {{ number_format($item['employee']->base_salary, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 bg-success-light rounded-full mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-text-heading font-medium text-lg">Semua Gaji Sudah Dibayarkan</p>
                            <p class="text-sm text-text-secondary mt-2">Seluruh karyawan telah menerima gaji minggu 1 -
                                {{ $currentWeek }}</p>
                            <p class="text-xs text-text-tertiary mt-1">Periode minggu {{ $currentWeek }}:
                                {{ $weekRange['start']->format('d M') }} - {{ $weekRange['end']->format('d M Y') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif


        @if ($shouldShowStockReminder && !auth()->user()?->isStafSDM() && !auth()->user()?->isSuperAdmin())
            <!-- Reminder Stok Menipis -->
            <div class="bg-surface-base rounded-xl shadow-md border border-border-light overflow-hidden">

                <div class="bg-error-light px-6 py-4 border-b border-error-light">
                    <div class="flex items-center gap-3">
                        <div class="bg-white p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-error" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-text-heading">Reminder Stok Menipis</h3>
                            <p class="text-sm text-text-secondary">Item dengan stok ≤ 5 unit</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if ($lowStockItems->count() > 0)
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-2xl font-bold text-error">{{ $lowStockItems->count() }}</span>
                                <span class="text-sm text-text-secondary">Item Menipis</span>
                            </div>
                            <div class="w-full bg-error-light rounded-full h-2">
                                <div class="bg-error h-2 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            @foreach ($lowStockItems as $item)
                                <div
                                    class="flex items-center justify-between p-3 bg-error-light rounded-lg border border-error-light hover:bg-white transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-white p-2 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-error"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-text-heading">{{ $item->name_item }}</p>
                                            <p class="text-sm text-text-secondary">ID: {{ $item->id_item }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                        {{ $item->quantity == 0 ? 'bg-error-light text-error' : ($item->quantity <= 2 ? 'bg-warning-light text-warning' : 'bg-info-light text-info') }}">
                                            {{ $item->quantity }} unit
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 bg-success-light rounded-full mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-text-heading font-medium text-lg">Semua Stok Aman</p>
                            <p class="text-sm text-text-secondary mt-2">Tidak ada item dengan stok menipis (≤ 5 unit)</p>
                            <p class="text-xs text-text-tertiary mt-1">Inventaris dalam kondisi baik</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection
