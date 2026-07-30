{{-- Alumunium Invoice Table Component --}}
<form id="deleteForm" method="POST" action="{{ route('alumunium-invoice.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <tr>
                            <th class="p-2 text-center"><input type="checkbox" id="selectAll"></th>
                            <th class="p-2 text-left">No Invoice</th>
                            <th class="p-2 text-left">Tanggal</th>
                            <th class="p-2 text-left">Kepada</th>
                            <th class="p-2 text-left">Proyek</th>
                            <th class="p-2 text-center">Total</th>
                            <th class="p-2 text-center">Terbayar</th>
                            <th class="p-2 text-center">Sisa</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php
                                $totalPaid = $invoice->getTotalPaidAmount();
                                $remaining = $invoice->getRemainingAmount();
                                $isFullyPaid = $invoice->isFullyPaid();
                                if ($isFullyPaid) {
                                    $statusLabel = 'Lunas';
                                    $statusClass = 'bg-green-100 text-green-800';
                                } elseif ($totalPaid > 0) {
                                    $statusLabel = 'Sebagian';
                                    $statusClass = 'bg-orange-100 text-orange-800';
                                } else {
                                    $statusLabel = 'Belum';
                                    $statusClass = 'bg-red-100 text-red-800';
                                }
                            @endphp
                            <tr class="border-t hover:bg-surface-secondary">
                                <td class="p-2 text-center">
                                    <input type="checkbox" name="selected_invoices[]"
                                        value="{{ $invoice->invoice_number }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>

                                <td class="p-2 font-medium text-primary">{{ $invoice->invoice_number }}</td>
                                <td class="p-2 text-sm">{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                                <td class="p-2">{{ $invoice->recipient }}</td>
                                <td class="p-2 text-sm text-text-label">
                                    {{ substr($invoice->project_description ?? '-', 0, 30) }}
                                </td>

                                <td class="p-2 text-right font-medium whitespace-nowrap">
                                    Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                </td>

                                <td class="p-2 text-right font-medium whitespace-nowrap">
                                    Rp {{ number_format($totalPaid, 0, ',', '.') }}
                                </td>

                                <td class="p-2 text-right font-semibold whitespace-nowrap
                                    {{ $remaining > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    Rp {{ number_format($remaining, 0, ',', '.') }}
                                </td>

                                <td class="p-2 text-center pr-4 md:pr-6">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                <td class="p-2 text-center pr-4 md:pr-6">
                                    <div class="flex justify-center gap-1 flex-wrap min-[1363px]:grid min-[1363px]:gap-1 min-[1363px]:grid-cols-2 min-[1363px]:justify-items-center min-[1436px]:grid-cols-4 min-[1436px]:gap-0 min-[1436px]:w-fit min-[1436px]:mx-auto">
                                        <button type="button"
                                            onclick="openModal('detailModal-{{ $invoice->invoice_number }}')"
                                            class="flex items-center gap-1 bg-info hover:bg-info/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Lihat Detail">
                                            <i class="fa-solid fa-eye w-3 h-3"></i>
                                            Lihat
                                        </button>

                                        <button type="button"
                                            onclick="openModal('editModal-{{ $invoice->invoice_number }}')"
                                            class="flex items-center gap-1 bg-btn-edit hover:bg-btn-edit-hover text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Edit Invoice">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>

                                        <a href="{{ route('alumunium-invoice.print.pdf', $invoice->invoice_number) }}"
                                            class="flex items-center gap-1 bg-error hover:bg-error/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Print PDF">
                                            <i class="fa-solid fa-file-pdf w-3 h-3"></i>
                                            PDF
                                        </a>

                                        <a href="{{ route('alumunium-invoice.print.excel', $invoice->invoice_number) }}"
                                            class="flex items-center gap-1 bg-success hover:bg-success/90 text-white px-2 py-1 rounded-lg transition-colors duration-200 text-xs"
                                            title="Print Excel">
                                            <i class="fa-solid fa-file-excel w-3 h-3"></i>
                                            Excel
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center p-4 text-text-secondary">
                                    Data invoice tidak ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
