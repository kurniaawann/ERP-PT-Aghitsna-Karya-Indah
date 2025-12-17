{{-- Modal Detail Payroll --}}
<div id="detailModal-{{ $payroll->id }}" class="modal-overlay hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            {{-- Header --}}
            <div class="flex justify-between items-center p-6 border-b">
                <h2 class="text-xl font-semibold text-text-heading">Detail Payroll</h2>
                <button type="button" onclick="closeModal('detailModal-{{ $payroll->id }}')"
                    class="text-text-secondary hover:text-text-primary">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-6 space-y-4">
                {{-- Employee Info --}}
                <div class="bg-surface-secondary p-4 rounded-lg">
                    <h3 class="font-semibold text-text-primary mb-3">Informasi Karyawan</h3>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-text-label">Nama:</p>
                            <p class="font-semibold">{{ $payroll->employee->name }}</p>
                        </div>
                        <div>
                            <p class="text-text-label">Kode Karyawan:</p>
                            <p class="font-semibold">{{ $payroll->employee->employee_code }}</p>
                        </div>
                        <div>
                            <p class="text-text-label">Jabatan:</p>
                            <p class="font-semibold">{{ $payroll->employee->position }}</p>
                        </div>
                        <div>
                            <p class="text-text-label">Periode:</p>
                            <p class="font-semibold">
                                {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->format('F Y') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Attendance Summary --}}
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-text-primary mb-3">Rekapitulasi Kehadiran</h3>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-text-label">Hadir:</span>
                            <span class="font-semibold">{{ $payroll->present_days }} hari</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-label">Izin:</span>
                            <span class="font-semibold">{{ $payroll->permission_days }} hari</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-label">Sakit:</span>
                            <span class="font-semibold">{{ $payroll->sick_days }} hari</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-label">Cuti:</span>
                            <span class="font-semibold">{{ $payroll->leave_days }} hari</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-label">Lembur:</span>
                            <span class="font-semibold">{{ $payroll->overtime_days }} hari</span>
                        </div>
                    </div>
                </div>

                {{-- Salary Calculation --}}
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-text-primary mb-3">Perhitungan Gaji</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-text-label">Gaji Pokok:</span>
                            <span class="font-semibold">Rp
                                {{ number_format($payroll->base_salary, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-red-600">
                            <span>Potongan
                                ({{ $payroll->permission_days + $payroll->sick_days + $payroll->leave_days }}
                                hari × Rp 30.000):</span>
                            <span class="font-semibold">- Rp
                                {{ number_format($payroll->deduction_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-green-600">
                            <span>Lembur:</span>
                            <span class="font-semibold">+ Rp
                                {{ number_format($payroll->overtime_total, 0, ',', '.') }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between text-lg font-bold text-primary">
                            <span>Gaji Bersih:</span>
                            <span>Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Payment Info --}}
                @if ($payroll->status === 'paid')
                    <div class="bg-green-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-text-primary mb-3">Informasi Pembayaran</h3>
                        <div class="text-sm">
                            <div class="flex justify-between">
                                <span class="text-text-label">Tanggal Bayar:</span>
                                <span class="font-semibold">
                                    {{ \Carbon\Carbon::parse($payroll->payment_date)->format('d/m/Y') }}
                                </span>
                            </div>
                            <div class="flex justify-between mt-2">
                                <span class="text-text-label">Status:</span>
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Paid
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-2 p-6 border-t">
                <button type="button" onclick="closeModal('detailModal-{{ $payroll->id }}')"
                    class="px-4 py-2 bg-button-cancel hover:bg-button-cancel-hover text-text-heading rounded-lg transition-colors duration-200">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
