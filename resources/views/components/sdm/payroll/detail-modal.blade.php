{{--
    Detail Payroll Modal

    Read-only modal displaying complete payroll information for a single employee.

    Sections:
    1. Employee Info - Name, code, position, period, project
    2. Attendance Summary - Total work days, present, permission, sick, leave, overtime
    3. Salary Calculation - Daily wage, present days, total wage, overtime, kasbon, net salary
    4. Additional Expenses (if any) - Parsed from JSON additional_expenses_notes
    5. Payment Info (if paid) - Payment date and status

    Business logic: Payroll model accessors (formatted_period, total_payment)
    Format: Rupiah (Rp X.XXX)

    Included from: pages/sdm/payroll.blade.php (inside @foreach loop)
--}}

{{-- Modal Detail Payroll --}}
<div id="detailModal-{{ $payroll->id }}" class="modal-overlay hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-surface-base rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
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
                                @if ($payroll->period_start_date && $payroll->period_end_date)
                                    @php
                                        $start = \Carbon\Carbon::parse($payroll->period_start_date);
                                        $end = \Carbon\Carbon::parse($payroll->period_end_date);
                                    @endphp
                                    {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}
                                @else
                                    {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->format('F Y') }}
                                    @if ($payroll->week_number)
                                        - Minggu {{ $payroll->week_number }}
                                    @endif
                                @endif
                            </p>
                        </div>
                        @if ($payroll->project_name)
                            <div>
                                <p class="text-text-label">Proyek:</p>
                                <p class="font-semibold">{{ $payroll->project_name }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Attendance Summary --}}
                <div class="bg-primary-light p-4 rounded-lg">
                    <h3 class="font-semibold text-text-primary mb-3">Rekapitulasi Kehadiran</h3>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-text-label">Total Hari Kerja:</span>
                            <span class="font-semibold">{{ $payroll->total_work_days }} hari</span>
                        </div>
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
                <div class="bg-success-light p-4 rounded-lg">
                    <h3 class="font-semibold text-text-primary mb-3">Perhitungan Upah</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-text-label">Upah Per Hari:</span>
                            <span class="font-semibold">Rp
                                {{ number_format($payroll->base_salary, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-label">Hari Masuk:</span>
                            <span class="font-semibold">{{ $payroll->present_days }} hari</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-text-label">Total Upah:</span>
                            <span class="font-semibold">Rp
                                {{ number_format($payroll->base_salary * $payroll->present_days, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-success">
                            <span>Lembur:</span>
                            <span class="font-semibold">+ Rp
                                {{ number_format($payroll->overtime_total, 0, ',', '.') }}</span>
                        </div>
                        @if ($payroll->kasbon_deduction)
                            <div class="flex justify-between text-error">
                                <span>Potongan Kasbon:</span>
                                <span class="font-semibold">- Rp
                                    {{ number_format($payroll->kasbon_deduction, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <hr class="my-2">
                        <div class="flex justify-between text-lg font-bold text-primary">
                            <span>Upah Bersih:</span>
                            <span>Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Additional Expenses --}}
                @if ($payroll->additional_expenses)
                    <div class="bg-secondary-light p-4 rounded-lg">
                        <h3 class="font-semibold text-text-primary mb-3">Pengeluaran Tambahan PT</h3>

                        @php
                            $expenseItems = [];
                            if ($payroll->additional_expenses_notes) {
                                $decoded = json_decode($payroll->additional_expenses_notes, true);
                                if (is_array($decoded)) {
                                    $expenseItems = $decoded;
                                }
                            }
                        @endphp

                        @if (count($expenseItems) > 0)
                            {{-- List of expense items --}}
                            <div class="space-y-2 mb-3">
                                @foreach ($expenseItems as $item)
                                    <div
                                        class="flex justify-between text-sm py-1.5 px-2 bg-surface-base rounded border border-border-strong">
                                        <span class="text-text-label">{{ $item['name'] ?? 'Item' }}</span>
                                        <span class="font-semibold text-primary">Rp
                                            {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <hr class="my-3 border-border-strong">
                            <div class="flex justify-between font-semibold">
                                <span class="text-text-label">Total Pengeluaran:</span>
                                <span class="text-primary">Rp
                                    {{ number_format($payroll->additional_expenses, 0, ',', '.') }}</span>
                            </div>
                        @else
                            {{-- Fallback: jika format lama (text biasa) --}}
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-text-label">Jumlah:</span>
                                    <span class="font-semibold">Rp
                                        {{ number_format($payroll->additional_expenses, 0, ',', '.') }}</span>
                                </div>
                                @if ($payroll->additional_expenses_notes && trim($payroll->additional_expenses_notes) !== '[]')
                                    <div>
                                        <p class="text-text-label">Keterangan:</p>
                                        <p class="text-sm">{{ $payroll->additional_expenses_notes }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <hr class="my-3 border-border-strong">
                        <div class="flex justify-between text-lg font-bold text-primary">
                            <span>Total Yang Dibayarkan:</span>
                            <span>Rp
                                {{ number_format($payroll->net_salary + $payroll->additional_expenses, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endif

                {{-- Payment Info --}}
                @if ($payroll->status === 'paid')
                    <div class="bg-success-light p-4 rounded-lg">
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
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-success-light text-success">
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
