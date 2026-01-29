@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- <div class="bg-white p-6 rounded-xl shadow">
        <h1 class="text-2xl font-semibold text-text-primary mb-4">Selamat Datang, {{ auth()->user()->name }}</h1>
        <p class="text-text-secondary">Anda login sebagai <strong>{{ auth()->user()->role }}</strong>.</p>
    </div> --}}

    <!-- Reminder Cards Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Reminder Gaji Karyawan -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            @if (!$isPayrollPeriod)
                {{-- Belum periode penggajian (sebelum tanggal 26) --}}
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-100">
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-blue-900">Info Penggajian</h3>
                            <p class="text-sm text-blue-700">Status periode penggajian bulan ini</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-gray-800 font-medium text-lg">Menunggu Periode Penggajian</p>
                        <p class="text-sm text-gray-500 mt-2">Periode penggajian dimulai setiap tanggal 26</p>
                        <p class="text-xs text-gray-400 mt-1">Saat ini: {{ \Carbon\Carbon::now()->format('d F Y') }}</p>
                    </div>
                </div>
            @else
                {{-- Sudah periode penggajian (tanggal 26 atau lebih) --}}
                <div class="bg-amber-50 px-6 py-4 border-b border-amber-100">
                    <div class="flex items-center gap-3">
                        <div class="bg-amber-100 p-2 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-amber-900">Reminder Gaji Karyawan</h3>
                            <p class="text-sm text-amber-700">Karyawan yang belum menerima gaji bulan ini</p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    @if ($employeesWithoutSalary->count() > 0)
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <span
                                    class="text-2xl font-bold text-amber-600">{{ $employeesWithoutSalary->count() }}</span>
                                <span class="text-sm text-gray-500">Total Karyawan</span>
                            </div>
                            <div class="w-full bg-amber-100 rounded-full h-2">
                                <div class="bg-amber-500 h-2 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            @foreach ($employeesWithoutSalary as $employee)
                                <div
                                    class="flex items-center justify-between p-3 bg-amber-50 rounded-lg border border-amber-100 hover:bg-amber-100 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-white p-2 rounded-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ $employee->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $employee->employee_code }} -
                                                {{ $employee->position }}</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium text-amber-600">Rp
                                        {{ number_format($employee->base_salary, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-gray-800 font-medium text-lg">Semua Gaji Sudah Dibayarkan</p>
                            <p class="text-sm text-gray-500 mt-2">Seluruh karyawan telah menerima gaji bulan ini</p>
                            <p class="text-xs text-gray-400 mt-1">Periode: {{ \Carbon\Carbon::now()->format('F Y') }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Reminder Stok Menipis -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <div class="bg-rose-50 px-6 py-4 border-b border-rose-100">
                <div class="flex items-center gap-3">
                    <div class="bg-rose-100 p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-rose-600" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-rose-900">Reminder Stok Menipis</h3>
                        <p class="text-sm text-rose-700">Item dengan stok ≤ 5 unit</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if ($lowStockItems->count() > 0)
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-2xl font-bold text-rose-600">{{ $lowStockItems->count() }}</span>
                            <span class="text-sm text-gray-500">Item Menipis</span>
                        </div>
                        <div class="w-full bg-rose-100 rounded-full h-2">
                            <div class="bg-rose-500 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        @foreach ($lowStockItems as $item)
                            <div
                                class="flex items-center justify-between p-3 bg-rose-50 rounded-lg border border-rose-100 hover:bg-rose-100 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="bg-white p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $item->name_item }}</p>
                                        <p class="text-sm text-gray-500">ID: {{ $item->id_item }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                        {{ $item->quantity == 0 ? 'bg-red-100 text-red-700' : ($item->quantity <= 2 ? 'bg-rose-100 text-rose-700' : 'bg-orange-100 text-orange-700') }}">
                                        {{ $item->quantity }} unit
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-gray-800 font-medium text-lg">Semua Stok Aman</p>
                        <p class="text-sm text-gray-500 mt-2">Tidak ada item dengan stok menipis (≤ 5 unit)</p>
                        <p class="text-xs text-gray-400 mt-1">Inventaris dalam kondisi baik</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
