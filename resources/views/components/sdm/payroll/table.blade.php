{{-- Payroll Table Component --}}
<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="inline-block min-w-full align-middle">
        <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-border-light">
                <thead class="bg-surface-secondary">
                    <tr>
                        <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                        <th class="p-2 text-left">Nama Karyawan</th>
                        <th class="p-2 text-center">Periode</th>
                        <th class="p-2 text-center">Upah/Hari</th>
                        <th class="p-2 text-center">Hari Masuk</th>
                        <th class="p-2 text-center">Kasbon</th>
                        <th class="p-2 text-center">Lembur</th>
                        <th class="p-2 text-center">Upah Bersih</th>
                        <th class="p-2 text-center">Status</th>
                        <th class="p-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls as $payroll)
                        <tr class="border-t hover:bg-surface-secondary">
                            <td class="p-2 text-center">
                                @if ($payroll->status === 'draft')
                                    <input type="checkbox" name="ids[]" value="{{ $payroll->id }}"
                                        class="payroll-checkbox w-4 h-4 accent-primary cursor-pointer">
                                @else
                                    <input type="checkbox" disabled class="w-4 h-4 cursor-not-allowed opacity-50">
                                @endif
                            </td>

                            <td class="p-2">{{ $payroll->employee->name }}</td>

                            {{-- Periode --}}
                            <td class="p-2 text-center text-sm">
                                {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->format('M Y') }}
                                @if ($payroll->week_number)
                                    <br><span class="text-xs text-text-label">Minggu {{ $payroll->week_number }}</span>
                                @endif
                            </td>

                            {{-- Upah Per Hari --}}
                            <td class="p-2 text-right text-sm">
                                {{ 'Rp ' . number_format($payroll->base_salary, 0, ',', '.') }}
                            </td>

                            {{-- Hari Masuk --}}
                            <td class="p-2 text-center font-medium">
                                {{ $payroll->present_days }} hari
                            </td>

                            {{-- Kasbon --}}
                            <td class="p-2 text-right text-error text-sm">
                                @if ($payroll->kasbon_deduction)
                                    {{ 'Rp ' . number_format($payroll->kasbon_deduction, 0, ',', '.') }}
                                @else
                                    <span class="text-text-label">-</span>
                                @endif
                            </td>

                            {{-- Lembur --}}
                            <td class="p-2 text-right text-success text-sm">
                                {{ 'Rp ' . number_format($payroll->overtime_total, 0, ',', '.') }}
                            </td>

                            {{-- Upah Bersih --}}
                            <td class="p-2 text-right font-semibold text-primary">
                                {{ 'Rp ' . number_format($payroll->net_salary, 0, ',', '.') }}
                            </td>

                            {{-- Status --}}
                            <td class="p-2 text-center">
                                @if ($payroll->status === 'draft')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning-light text-warning">
                                        Draft
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-success-light text-success">
                                        Paid
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="p-2 text-center">
                                <div class="flex justify-center gap-2">
                                    @if ($payroll->status === 'draft')
                                        <x-buttons.edit onclick="openModal('editModal-{{ $payroll->id }}')" />
                                    @endif

                                    <x-buttons.detail onclick="openModal('detailModal-{{ $payroll->id }}')" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center p-4 text-text-secondary">
                                Data tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Form untuk Bulk Delete --}}
<form id="deleteForm" method="POST" action="{{ route('payroll.destroy') }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>

{{-- Form untuk Bulk Pay --}}
<form id="bulkPayForm" method="POST" action="{{ route('payroll.bulk-pay') }}" style="display: none;">
    @csrf
    @method('PATCH')
</form>