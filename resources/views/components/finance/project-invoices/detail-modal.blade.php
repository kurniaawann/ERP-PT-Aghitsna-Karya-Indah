@php
    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
    $totalAmount = (int) ($invoice->total_amount ?? 0);
    $netAmount = $invoice->getNetAmount();
    $totalPaid = $invoice->getTotalPaidAmount();

    $discountAmount = (int) $invoice->getDiscountAmount();
    $dpAmount = (int) $invoice->getDpAmount();
    $ppnAmount = (int) $invoice->getPpnAmount();

    // Grand total PPN-inclusive, konsisten dengan PDF:
    // grandTotal = total_amount - discount + ppn; sisa = grandTotal - dp - terbayar.
    $grandTotal = $netAmount - $discountAmount + $ppnAmount;
    $remaining = max(0, $grandTotal - $dpAmount - $totalPaid);
    $isFullyPaid = $remaining <= 0;
    $progressPercent = $grandTotal > 0 ? min(100, (int) round((($totalPaid + $dpAmount) / $grandTotal) * 100)) : 0;

    $discountValueDisplay = rtrim(rtrim(number_format((float) $invoice->discount_value, 2, ',', '.'), '0'), ',');
    $dpValueDisplay = rtrim(rtrim(number_format((float) $invoice->dp_value, 2, ',', '.'), '0'), ',');
    $ppnValueDisplay = rtrim(rtrim(number_format((float) $invoice->ppn, 2, ',', '.'), '0'), ',');

    $paymentProofs = $invoice->relationLoaded('paymentProofs')
        ? $invoice->paymentProofs
        : $invoice->paymentProofs()->get();

    $hasInstallments = $paymentProofs->isNotEmpty();
    $installmentProofs = $paymentProofs
        ->sortBy(fn($p) => sprintf('%06d', (int) ($p->payment_stage ?? 999999)))
        ->values();

    $storedInstallments = [];
    if (!$hasInstallments) {
        $rawInstallments = $invoice->payment_installments;
        $storedInstallments = is_array($rawInstallments) ? $rawInstallments : [];
    }

    $dueDate = $invoice->getDueDate();
@endphp

<x-modal id="detailModal-{{ $invoice->invoice_number }}" title="{{ auth()->user()->isAdmin() ? 'Detail Invoice' : 'Detail Invoice Proyek' }}" :hideFooter="true" size="4xl">

    {{-- Card A: Informasi Invoice --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Informasi Invoice</h3>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $invoice->payment_status_badge_class }}">
                {{ $invoice->payment_status_label }}
            </span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">No Invoice</p>
                <p class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Tanggal Invoice</p>
                <p class="text-gray-900">{{ $invoice->invoice_date->format('d F Y') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Kepada</p>
                <p class="font-medium text-gray-900">{{ $invoice->recipient }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Hal / Regarding</p>
                <p class="text-gray-900">{{ $invoice->regarding ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Jatuh Tempo</p>
                <p class="text-gray-900">{{ $dueDate->format('d F Y') }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs text-gray-400 mb-0.5">Deskripsi Proyek</p>
                <p class="text-gray-900">{{ $invoice->project_description }}</p>
            </div>
        </div>
    </div>

    {{-- Card B: Item-Item Invoice --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Item-Item Invoice</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="py-2 pr-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                        <th class="py-2 px-2 text-right text-xs font-medium text-gray-500 uppercase">Volume</th>
                        <th class="py-2 px-2 text-left text-xs font-medium text-gray-500 uppercase">Satuan</th>
                        <th class="py-2 px-2 text-right text-xs font-medium text-gray-500 uppercase">Harga</th>
                        <th class="py-2 pl-2 text-right text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($items as $index => $item)
                        <tr>
                            <td class="py-2 pr-2 text-gray-500">{{ $index + 1 }}</td>
                            <td class="py-2 px-2 text-gray-900">{{ $item['keterangan'] ?? '-' }}</td>
                            <td class="py-2 px-2 text-right text-gray-900">{{ number_format($item['volume'] ?? 0, 2, ',', '.') }}</td>
                            <td class="py-2 px-2 text-gray-900">{{ $item['satuan'] ?? '-' }}</td>
                            <td class="py-2 px-2 text-right text-gray-900">Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                            <td class="py-2 pl-2 text-right font-semibold text-gray-900">
                                Rp {{ number_format(($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300 font-bold">
                        <td colspan="5" class="pt-3 pr-2 text-right text-sm text-gray-700">Subtotal</td>
                        <td class="pt-3 pl-2 text-right text-sm text-gray-900">
                            Rp {{ number_format($totalAmount, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Card C: Ringkasan Finansial --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Ringkasan Finansial</h3>
        <div class="space-y-2.5">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Subtotal</span>
                <span class="text-sm font-medium text-gray-900">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
            </div>

            @if ($invoice->discount_value && $invoice->discount_value > 0)
                <div class="flex justify-between items-center py-1.5 px-3 bg-red-50 rounded-lg">
                    <span class="text-sm text-red-700">
                        <i class="fa-solid fa-tag mr-1"></i>Discount
                        @if ($invoice->discount_type === 'percentage')
                            ({{ $discountValueDisplay }}%)
                        @else
                            (Nominal)
                        @endif
                    </span>
                    <span class="text-sm font-semibold text-red-600">-Rp {{ number_format($discountAmount, 0, ',', '.') }}</span>
                </div>
            @else
                <div class="flex justify-between items-center py-1.5 px-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-400"><i class="fa-regular fa-circle-xmark mr-1"></i>Discount</span>
                    <span class="text-sm text-gray-400 italic">Tidak ada discount</span>
                </div>
            @endif

            @if ($invoice->ppn && $invoice->ppn > 0)
                <div class="flex justify-between items-center py-1.5 px-3 bg-purple-50 rounded-lg">
                    <span class="text-sm text-purple-700">
                        <i class="fa-solid fa-percent mr-1"></i>PPN ({{ $ppnValueDisplay }}%)
                    </span>
                    <span class="text-sm font-semibold text-purple-700">+Rp {{ number_format($ppnAmount, 0, ',', '.') }}</span>
                </div>
            @else
                <div class="flex justify-between items-center py-1.5 px-3 bg-gray-50 rounded-lg">
                    <span class="text-sm text-gray-400"><i class="fa-regular fa-circle-xmark mr-1"></i>PPN</span>
                    <span class="text-sm text-gray-400 italic">Tidak ada PPN</span>
                </div>
            @endif

            <hr class="border-gray-200">

            <div class="flex justify-between items-center">
                <span class="text-base font-bold text-gray-800">Grand Total</span>
                <span class="text-base font-bold text-gray-900">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Card D: Pembayaran --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Pembayaran</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-400 mb-1">DP / Uang Muka</p>
                @if ($invoice->dp_value && $invoice->dp_value > 0)
                    <p class="text-lg font-bold text-blue-600">Rp {{ number_format($dpAmount, 0, ',', '.') }}</p>
                    @if ($invoice->dp_type === 'percentage')
                        <p class="text-xs text-gray-400">({{ $dpValueDisplay }}% dari Grand Total)</p>
                    @endif
                @else
                    <p class="text-sm text-gray-400 italic">Belum ada DP</p>
                @endif
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-400 mb-1">Total Terbayar</p>
                @if ($totalPaid > 0)
                    <p class="text-lg font-bold text-green-600">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                @else
                    <p class="text-sm text-gray-400 italic">Belum ada pembayaran</p>
                @endif
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-400 mb-1">Sisa Pembayaran</p>
                @if ($remaining > 0)
                    <p class="text-lg font-bold text-red-600">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
                @else
                    <p class="text-lg font-bold text-green-600">Rp 0</p>
                @endif
            </div>
        </div>

        {{-- Ringkasan Perhitungan --}}
        <div class="rounded-lg bg-gray-50 border border-gray-100 p-4 mb-4">
            <p class="text-xs text-gray-400 mb-2">Ringkasan Perhitungan</p>
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                <span class="font-medium text-gray-700">Total Keseluruhan</span>
                <span class="font-semibold">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>

                @if ($discountAmount > 0)
                    <span class="text-gray-400">→</span>
                    <span class="text-red-600 font-medium">Discount</span>
                    <span class="text-red-600 font-semibold">-Rp {{ number_format($discountAmount, 0, ',', '.') }}</span>
                @endif

                @if ($ppnAmount > 0)
                    <span class="text-gray-400">→</span>
                    <span class="text-purple-600 font-medium">PPN</span>
                    <span class="text-purple-600 font-semibold">+Rp {{ number_format($ppnAmount, 0, ',', '.') }}</span>
                @endif

                @if ($dpAmount > 0)
                    <span class="text-gray-400">→</span>
                    <span class="text-blue-600 font-medium">DP</span>
                    <span class="text-blue-600 font-semibold">-Rp {{ number_format($dpAmount, 0, ',', '.') }}</span>
                @endif

                <span class="text-gray-400">→</span>
                <span class="text-green-600 font-medium">Terbayar</span>
                <span class="text-green-600 font-semibold">-Rp {{ number_format($totalPaid, 0, ',', '.') }}</span>

                <span class="text-gray-400">→</span>
                <span class="font-medium {{ $remaining > 0 ? 'text-red-600' : 'text-green-600' }}">Sisa Pembayaran</span>
                <span class="font-semibold {{ $remaining > 0 ? 'text-red-600' : 'text-green-600' }}">Rp {{ number_format($remaining, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div>
            <div class="flex justify-between text-xs text-gray-500 mb-1.5">
                <span>Progress Pembayaran</span>
                <span class="font-semibold
                    {{ $progressPercent == 100 ? 'text-green-600' : ($progressPercent > 0 ? 'text-orange-500' : 'text-red-500') }}">
                    {{ $progressPercent }}%
                </span>
            </div>
            <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500 ease-in-out
                    {{ $progressPercent == 100 ? 'bg-green-500' : ($progressPercent > 0 ? 'bg-orange-400' : 'bg-red-400') }}"
                    style="width: {{ $progressPercent }}%">
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-1">
                @if ($isFullyPaid)
                    <span class="text-green-600 font-medium"><i class="fa-solid fa-circle-check mr-0.5"></i>Sudah Lunas</span>
                @elseif ($totalPaid > 0)
                    <span class="text-orange-500 font-medium"><i class="fa-solid fa-circle-half-stroke mr-0.5"></i>Sebagian ({{ $progressPercent }}% terbayar)</span>
                @else
                    <span class="text-red-500 font-medium"><i class="fa-solid fa-circle mr-0.5"></i>Belum ada pembayaran</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Card E: Pembayaran Bertahap --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Pembayaran Bertahap</h3>

        @if ($hasInstallments || !empty($storedInstallments))
            @php
                $installmentCount = $hasInstallments ? $installmentProofs->count() : count($storedInstallments);
            @endphp

            <div class="flex items-center gap-3 mb-4 p-3 bg-gray-50 rounded-lg border border-gray-100">
                <div class="flex-1">
                    <p class="text-xs text-gray-500 mb-1">Tahap Pembayaran</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $installmentCount }} tahap tersedia</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 mb-1">Status</p>
                    @if ($isFullyPaid)
                        <span class="text-xs font-semibold text-green-600"><i class="fa-solid fa-circle-check mr-0.5"></i>Semua lunas</span>
                    @else
                        <span class="text-xs font-semibold text-orange-500"><i class="fa-solid fa-clock mr-0.5"></i>Belum selesai</span>
                    @endif
                </div>
            </div>

            <div class="relative">
                <div class="absolute left-[15px] top-2 bottom-2 w-0.5 bg-gray-200"></div>

                <div class="space-y-0">
                    @if ($hasInstallments)
                        @foreach ($installmentProofs as $proofIndex => $proof)
                            @php
                                $stageNumber = $proofIndex + 1;
                                $isLast = $proofIndex === $installmentProofs->count() - 1;
                                $proofAmount = (int) ($proof->amount ?? 0);
                                $proofDate = $proof->created_at ? \Carbon\Carbon::parse($proof->created_at)->format('d M Y') : '-';
                                $isInstallmentPaid = $proofAmount > 0;
                            @endphp
                            <div class="relative flex items-start gap-4 pb-6 {{ $isLast ? '' : '' }}">
                                <div class="relative z-10 flex-shrink-0 w-[30px] h-[30px] rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $isInstallmentPaid ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                                    @if ($isInstallmentPaid)
                                        <i class="fa-solid fa-check text-xs"></i>
                                    @else
                                        {{ $stageNumber }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold {{ $isInstallmentPaid ? 'text-gray-900' : 'text-gray-500' }}">
                                            Pembayaran ke-{{ $proof->payment_stage ?? $stageNumber }}
                                        </p>
                                        <span class="text-sm font-semibold {{ $isInstallmentPaid ? 'text-green-600' : 'text-gray-400' }}">
                                            Rp {{ number_format($proofAmount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between mt-0.5">
                                        <p class="text-xs text-gray-400">{{ $proofDate }}</p>
                                        @if ($isInstallmentPaid)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700">
                                                Lunas
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-400">
                                                Belum dibayar
                                            </span>
                                        @endif
                                    </div>
                                    @if (!empty($proof->file_path))
                                        <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank"
                                            rel="noopener noreferrer" title="{{ $proof->file_name }}"
                                            class="inline-flex items-center gap-1 mt-1 text-xs text-blue-600 hover:underline">
                                            <i class="fa-solid fa-paperclip"></i> Lihat Bukti
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach ($storedInstallments as $instIndex => $installment)
                            @php
                                $isLast = $instIndex === count($storedInstallments) - 1;
                                $instAmount = (int) ($installment['amount'] ?? 0);
                                $instLabel = $installment['label'] ?? ('Pembayaran ke-' . ($instIndex + 1));
                                $isInstallmentPaid = $instAmount > 0;
                            @endphp
                            <div class="relative flex items-start gap-4 pb-6">
                                <div class="relative z-10 flex-shrink-0 w-[30px] h-[30px] rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $isInstallmentPaid ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                                    @if ($isInstallmentPaid)
                                        <i class="fa-solid fa-check text-xs"></i>
                                    @else
                                        {{ $instIndex + 1 }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-semibold {{ $isInstallmentPaid ? 'text-gray-900' : 'text-gray-500' }}">
                                            {{ $instLabel }}
                                        </p>
                                        <span class="text-sm font-semibold {{ $isInstallmentPaid ? 'text-green-600' : 'text-gray-400' }}">
                                            Rp {{ number_format($instAmount, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex justify-end mt-0.5">
                                        @if ($isInstallmentPaid)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700">
                                                Lunas
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-400">
                                                Belum dibayar
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        @else
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
                    <i class="fa-solid fa-clock text-gray-300 text-lg"></i>
                </div>
                <p class="text-sm text-gray-400 font-medium">Belum ada pembayaran bertahap</p>
                <p class="text-xs text-gray-300 mt-1">Pembayaran akan muncul setelah bukti pembayaran diupload</p>
            </div>
        @endif
    </div>

    {{-- Tanda Tangan --}}
    @if ($invoice->signedBy)
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Tanda Tangan</h3>
        <div class="space-y-2">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Nama Penandatangan</p>
                <p class="font-medium text-gray-900">{{ $invoice->signedBy->name }}</p>
            </div>
            @if ($invoice->division)
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Divisi</p>
                <p class="text-gray-900">{{ $invoice->division->name }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

</x-modal>
