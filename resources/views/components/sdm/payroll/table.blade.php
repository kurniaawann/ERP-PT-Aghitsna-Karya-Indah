{{-- Payroll Table Component --}}
<div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="inline-block min-w-full align-middle">
        <div class="border-2 border-gray-300 rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                        <th class="p-2 text-left">Nama Karyawan</th>
                        <th class="p-2 text-center">Periode</th>
                        <th class="p-2 text-center">Gaji Pokok</th>
                        <th class="p-2 text-center">Potongan</th>
                        <th class="p-2 text-center">Lembur</th>
                        <th class="p-2 text-center">Gaji Bersih</th>
                        <th class="p-2 text-center">Status</th>
                        <th class="p-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls as $payroll)
                        <tr class="border-t hover:bg-gray-50">
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
                            <td class="p-2 text-center">
                                {{ \Carbon\Carbon::create($payroll->period_year, $payroll->period_month, 1)->format('F Y') }}
                            </td>

                            {{-- Gaji Pokok --}}
                            <td class="p-2 text-right">
                                {{ 'Rp ' . number_format($payroll->base_salary, 0, ',', '.') }}
                            </td>

                            {{-- Potongan --}}
                            <td class="p-2 text-right text-red-600">
                                {{ 'Rp ' . number_format($payroll->deduction_amount, 0, ',', '.') }}
                            </td>

                            {{-- Lembur --}}
                            <td class="p-2 text-right text-green-600">
                                {{ 'Rp ' . number_format($payroll->overtime_total, 0, ',', '.') }}
                            </td>

                            {{-- Gaji Bersih --}}
                            <td class="p-2 text-right font-semibold text-primary">
                                {{ 'Rp ' . number_format($payroll->net_salary, 0, ',', '.') }}
                            </td>

                            {{-- Status --}}
                            <td class="p-2 text-center">
                                @if ($payroll->status === 'draft')
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Draft
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Paid
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="p-2 text-center">
                                <div class="flex justify-center gap-2">
                                    <button type="button" onclick="openModal('detailModal-{{ $payroll->id }}')"
                                        class="flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                        title="Detail Payroll">
                                        <i class="fa-solid fa-eye w-3 h-3"></i>
                                        Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center p-4 text-gray-500">
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
