{{-- Modal Detail Invoice Alumunium --}}
<x-modal id="detailModal-{{ $invoice->invoice_number }}" title="Detail Invoice" :hideFooter="true">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">No Invoice</label>
            <p class="text-gray-900 font-medium">{{ $invoice->invoice_number }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">Tanggal Invoice</label>
            <p class="text-gray-900">{{ $invoice->invoice_date->format('d F Y') }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">Kepada</label>
            <p class="text-gray-900">{{ $invoice->recipient }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">Hal / Regarding</label>
            <p class="text-gray-900">{{ $invoice->regarding }}</p>
        </div>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-semibold text-text-primary mb-1">Deskripsi Proyek</label>
        <p class="text-gray-900">{{ $invoice->project_description }}</p>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-semibold text-text-primary mb-2">Item-Item Invoice</label>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-border-strong">
                <thead class="bg-surface-hover">
                    <tr>
                        <th class="border border-border-strong px-2 py-2 text-left text-sm">No</th>
                        <th class="border border-border-strong px-2 py-2 text-left text-sm">Keterangan</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Volume</th>
                        <th class="border border-border-strong px-2 py-2 text-left text-sm">Satuan</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Harga</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                    @endphp
                    @foreach ($items as $index => $item)
                        <tr>
                            <td class="border border-border-strong px-2 py-2 text-sm">{{ $index + 1 }}</td>
                            <td class="border border-border-strong px-2 py-2 text-sm">
                                {{ $item['keterangan'] ?? '-' }}
                            </td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm">
                                {{ number_format($item['volume'] ?? 0, 2, ',', '.') }}</td>
                            <td class="border border-border-strong px-2 py-2 text-sm">
                                {{ $item['satuan'] ?? '-' }}</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm">
                                Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm font-semibold">
                                Rp
                                {{ number_format(($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-primary/10 font-bold">
                        <td colspan="5" class="border border-border-strong px-2 py-2 text-right text-sm">
                            TOTAL</td>
                        <td class="border border-border-strong px-2 py-2 text-right text-sm text-primary">
                            Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>

                    @php
                        $totalAfterDiscount = $invoice->total_amount;
                        $discountAmount = 0;
                        $dpAmount = 0;
                        $discountValueDisplay = rtrim(
                            rtrim(number_format((float) $invoice->discount_value, 2, ',', '.'), '0'),
                            ',',
                        );
                        $dpValueDisplay = rtrim(
                            rtrim(number_format((float) $invoice->dp_value, 2, ',', '.'), '0'),
                            ',',
                        );
                    @endphp

                    @if ($invoice->discount_value && $invoice->discount_value > 0)
                        @php
                            if ($invoice->discount_type === 'percentage') {
                                $discountAmount = ($invoice->total_amount * $invoice->discount_value) / 100;
                            } else {
                                $discountAmount = $invoice->discount_value;
                            }
                            $totalAfterDiscount = $invoice->total_amount - $discountAmount;
                        @endphp
                        <tr class="bg-red-50 font-semibold">
                            <td colspan="5" class="border border-border-strong px-2 py-2 text-right text-sm">
                                DISCOUNT
                                @if ($invoice->discount_type === 'percentage')
                                    ({{ $discountValueDisplay }}%)
                                @else
                                    (Nominal)
                                @endif
                            </td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm text-red-600">
                                Rp {{ number_format($discountAmount, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="bg-green-50 font-bold">
                            <td colspan="5" class="border border-border-strong px-2 py-2 text-right text-sm">
                                TOTAL SETELAH DISCOUNT</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm text-green-600">
                                Rp {{ number_format($totalAfterDiscount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif

                    @if ($invoice->dp_value && $invoice->dp_value > 0)
                        @php
                            $baseForDP = $totalAfterDiscount;
                            if ($invoice->dp_type === 'percentage') {
                                $dpAmount = ($baseForDP * $invoice->dp_value) / 100;
                            } else {
                                $dpAmount = $invoice->dp_value;
                            }
                        @endphp
                        <tr class="bg-blue-50 font-semibold">
                            <td colspan="5" class="border border-border-strong px-2 py-2 text-right text-sm">
                                DP
                                @if ($invoice->dp_type === 'percentage')
                                    ({{ $dpValueDisplay }}%)
                                @else
                                    (Nominal)
                                @endif
                            </td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm text-blue-600">
                                Rp {{ number_format($dpAmount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Discount Section -->
    @php
        if (!isset($totalAfterDiscount)) {
            $totalAfterDiscount = $invoice->total_amount;
            $discountAmount = 0;
        }
    @endphp

    @if ($invoice->discount_value && $invoice->discount_value > 0)
        <div class="mb-4 p-3 border rounded bg-yellow-50">
            <label class="block text-sm font-semibold text-text-primary mb-2">Discount (Opsional)</label>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-xs text-text-label mb-1">Tipe Discount</label>
                    <p class="text-sm font-medium text-gray-900">
                        @if ($invoice->discount_type === 'percentage')
                            Persentase (%)
                        @else
                            Nominal (Rp)
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-xs text-text-label mb-1">Nilai Discount</label>
                    <p class="text-sm font-medium text-gray-900">
                        @if ($invoice->discount_type === 'percentage')
                            {{ $discountValueDisplay }}%
                        @else
                            Rp {{ number_format($invoice->discount_value, 0, ',', '.') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="space-y-2 p-2 bg-white rounded">
                <div class="flex justify-between text-sm">
                    <span class="text-text-label">Discount:</span>
                    <span class="font-semibold text-red-600">Rp
                        {{ number_format($discountAmount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold">
                    <span class="text-text-primary">Total Setelah Discount:</span>
                    <span class="text-green-600">Rp {{ number_format($totalAfterDiscount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    @else
        <div class="mb-4 p-3 border rounded bg-yellow-50">
            <label class="block text-sm font-semibold text-text-primary mb-2">Discount (Opsional)</label>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-xs text-text-label mb-1">Tipe Discount</label>
                    <p class="text-sm font-medium text-gray-900">Tidak Ada Discount</p>
                </div>
                <div>
                    <label class="block text-xs text-text-label mb-1">Nilai Discount</label>
                    <p class="text-sm font-medium text-gray-900">0</p>
                </div>
            </div>
            <div class="space-y-2 p-2 bg-white rounded">
                <div class="flex justify-between text-sm">
                    <span class="text-text-label">Discount:</span>
                    <span class="font-semibold text-red-600">Rp 0</span>
                </div>
                <div class="flex justify-between text-sm font-bold">
                    <span class="text-text-primary">Total Setelah Discount:</span>
                    <span class="text-green-600">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- DP Section -->
    @if ($invoice->dp_value && $invoice->dp_value > 0)
        @php
            $baseForDP = $totalAfterDiscount;
            if ($invoice->dp_type === 'percentage') {
                $dpAmount = ($baseForDP * $invoice->dp_value) / 100;
            } else {
                $dpAmount = $invoice->dp_value;
            }
        @endphp
        <div class="mb-4 p-3 border rounded bg-blue-50">
            <label class="block text-sm font-semibold text-text-primary mb-2">DP / Uang Muka (Opsional)</label>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-xs text-text-label mb-1">Tipe DP</label>
                    <p class="text-sm font-medium text-gray-900">
                        @if ($invoice->dp_type === 'percentage')
                            Persentase (%)
                        @else
                            Nominal (Rp)
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-xs text-text-label mb-1">Nilai DP</label>
                    <p class="text-sm font-medium text-gray-900">
                        @if ($invoice->dp_type === 'percentage')
                            {{ $dpValueDisplay }}%
                        @else
                            Rp {{ number_format($invoice->dp_value, 0, ',', '.') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="space-y-2 p-2 bg-white rounded">
                <div class="flex justify-between text-sm font-bold">
                    <span class="text-text-primary">Nilai DP:</span>
                    <span class="text-blue-600">Rp {{ number_format($dpAmount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    @else
        <div class="mb-4 p-3 border rounded bg-blue-50">
            <label class="block text-sm font-semibold text-text-primary mb-2">DP / Uang Muka (Opsional)</label>
            <div class="grid grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-xs text-text-label mb-1">Tipe DP</label>
                    <p class="text-sm font-medium text-gray-900">Tidak Ada DP</p>
                </div>
                <div>
                    <label class="block text-xs text-text-label mb-1">Nilai DP</label>
                    <p class="text-sm font-medium text-gray-900">0</p>
                </div>
            </div>
            <div class="space-y-2 p-2 bg-white rounded">
                <div class="flex justify-between text-sm font-bold">
                    <span class="text-text-primary">Nilai DP:</span>
                    <span class="text-blue-600">Rp 0</span>
                </div>
            </div>
        </div>
    @endif

    @php
        if (!isset($terbilangAmount)) {
            $terbilangAmount = $totalAfterDiscount ?? $invoice->total_amount;
        }
    @endphp
    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-sm text-text-primary"><span class="font-semibold">Terbilang:</span> <span
                class="italic">{{ terbilang($terbilangAmount) }} Rupiah</span></p>
    </div>

</x-modal>
