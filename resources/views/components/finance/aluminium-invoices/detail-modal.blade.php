@php
    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
    $totalAmount = (int) ($invoice->total_amount ?? 0);
    $netAmount = $invoice->getNetAmount();
    $totalPaid = $invoice->getTotalPaidAmount();
    $remaining = $invoice->getRemainingAmount();
    $isFullyPaid = $invoice->isFullyPaid();

    $discountAmount = (int) $invoice->getDiscountAmount();
    $dpAmount = (int) $invoice->getDpAmount();

    // Clean display values
    $discountValueDisplay = rtrim(rtrim(number_format((float) $invoice->discount_value, 2, ',', '.'), '0'), ',');
    $dpValueDisplay = rtrim(rtrim(number_format((float) $invoice->dp_value, 2, ',', '.'), '0'), ',');
@endphp

<x-modal id="detailModal-{{ $invoice->invoice_number }}" title="Detail Invoice" :hideFooter="true" size="4xl">

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
            {{-- Subtotal --}}
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Subtotal</span>
                <span class="text-sm font-medium text-gray-900">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
            </div>

            {{-- Discount --}}
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

            {{-- Separator --}}
            <hr class="border-gray-200">

            {{-- Grand Total (after discount) --}}
            <div class="flex justify-between items-center">
                <span class="text-base font-bold text-gray-800">Grand Total</span>
                <span class="text-base font-bold text-gray-900">Rp {{ number_format($netAmount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Card D: Pembayaran --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Pembayaran</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            {{-- DP --}}
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

            {{-- Total Terbayar --}}
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-400 mb-1">Total Terbayar</p>
                @if ($totalPaid > 0)
                    <p class="text-lg font-bold text-green-600">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                @else
                    <p class="text-sm text-gray-400 italic">Belum ada pembayaran</p>
                @endif
            </div>

            {{-- Sisa Pembayaran --}}
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-400 mb-1">Sisa Pembayaran</p>
                @if ($remaining > 0)
                    <p class="text-lg font-bold text-red-600">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
                @else
                    <p class="text-lg font-bold text-green-600">Rp 0</p>
                @endif
            </div>
        </div>

        {{-- Payment Flow Summary --}}
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

    </div>

    {{-- Card E: Bukti Pembayaran --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Bukti Pembayaran</h3>

        @php $proofs = $invoice->paymentProofs()->get(); @endphp
        @if ($proofs->isNotEmpty())
            <div class="space-y-2">
                @foreach ($proofs as $proof)
                    <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank" rel="noopener noreferrer"
                        title="{{ $proof->file_name }}"
                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3 hover:border-blue-300 hover:bg-blue-50">
                        <span class="flex items-center gap-2 min-w-0">
                            <i class="fa-solid fa-file-invoice text-blue-500"></i>
                            <span class="truncate text-sm font-medium text-gray-900">{{ $proof->file_name }}</span>
                        </span>
                        <span class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-sm font-semibold text-green-600">
                                Rp {{ number_format($proof->amount ?? 0, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ optional($proof->payment_date ?? $proof->created_at)->format('d M Y') }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">
                                <i class="fa-solid fa-paperclip"></i> Lihat
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                <p class="text-sm text-gray-400 font-medium">Belum ada bukti pembayaran</p>
                <p class="text-xs text-gray-300 mt-1">Upload melalui menu Bukti Pembayaran.</p>
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
