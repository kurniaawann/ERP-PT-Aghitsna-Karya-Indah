{{-- Modal Detail Invoice Barang --}}
<x-modal id="detailModal-{{ $invoice->invoice_number }}" title="Detail Invoice Barang" :hideFooter="true">
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
            <label class="block text-sm font-semibold text-text-primary mb-1">Hal / Keterangan</label>
            <p class="text-gray-900">{{ $invoice->regarding ?? '-' }}</p>
        </div>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-semibold text-text-primary mb-1">Keterangan / Proyek</label>
        <p class="text-gray-900">{{ $invoice->project_description }}</p>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-semibold text-text-primary mb-2">Item-Item Invoice</label>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-border-strong">
                <thead class="bg-surface-hover">
                    <tr>
                        <th class="border border-border-strong px-2 py-2 text-left text-sm">No</th>
                        <th class="border border-border-strong px-2 py-2 text-left text-sm">Nama Barang</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Qty</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Harga Modal</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Harga Jual</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Jumlah</th>
                        <th class="border border-border-strong px-2 py-2 text-left text-sm">Sumber</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                    @endphp
                    @foreach ($items as $index => $item)
                        <tr>
                            <td class="border border-border-strong px-2 py-2 text-sm">{{ $index + 1 }}</td>
                            <td class="border border-border-strong px-2 py-2 text-sm">{{ $item['name_item'] ?? '-' }}
                            </td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm">
                                {{ $item['quantity'] ?? 0 }}</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm">Rp
                                {{ number_format($item['capital_price'] ?? 0, 0, ',', '.') }}</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm">Rp
                                {{ number_format($item['selling_price'] ?? 0, 0, ',', '.') }}</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm font-semibold">Rp
                                {{ number_format(($item['selling_price'] ?? 0) * ($item['quantity'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="border border-border-strong px-2 py-2 text-sm">
                                {{ !empty($item['from_stock']) ? 'Dari Stok' : 'Manual' }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-primary/10 font-bold">
                        <td colspan="5" class="border border-border-strong px-2 py-2 text-right text-sm">TOTAL</td>
                        <td class="border border-border-strong px-2 py-2 text-right text-sm text-primary">Rp
                            {{ number_format($invoice->total_selling, 0, ',', '.') }}</td>
                        <td class="border border-border-strong px-2 py-2 text-sm">{{ $invoice->status_label }}</td>
                    </tr>
                    <tr class="bg-red-50 font-semibold">
                        <td colspan="5" class="border border-border-strong px-2 py-2 text-right text-sm">TOTAL MODAL
                        </td>
                        <td class="border border-border-strong px-2 py-2 text-right text-sm text-red-600">Rp
                            {{ number_format($invoice->total_capital, 0, ',', '.') }}</td>

                    </tr>
                    <tr class="bg-green-50 font-semibold">
                        <td colspan="5" class="border border-border-strong px-2 py-2 text-right text-sm">PROFIT</td>
                        <td class="border border-border-strong px-2 py-2 text-right text-sm text-green-600">Rp
                            {{ number_format($invoice->total_profit, 0, ',', '.') }}</td>
                        <td class="border border-border-strong px-2 py-2 text-sm"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    {{-- Tanda Tangan --}}
    @if ($invoice->signedBy)
    <div class="mb-4 p-4 border rounded bg-gray-50">
        <label class="block text-sm font-semibold text-text-primary mb-2">Tanda Tangan</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-text-label mb-0.5">Nama Penandatangan</label>
                <p class="text-gray-900 font-medium">{{ $invoice->signedBy->name }}</p>
            </div>
            @if ($invoice->division)
            <div>
                <label class="block text-xs text-text-label mb-0.5">Divisi</label>
                <p class="text-gray-900">{{ $invoice->division->name }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

</x-modal>
