{{-- Modal Detail Invoice Alumunium --}}
<x-modal id="detailModal-{{ $invoice->invoice_number }}" title="Detail Invoice" :readOnly="true">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">No Invoice</label>
            <p class="text-gray-900 font-medium">{{ $invoice->invoice_number }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Invoice</label>
            <p class="text-gray-900">{{ $invoice->invoice_date->format('d F Y') }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Kepada</label>
            <p class="text-gray-900">{{ $invoice->recipient }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Hal / Regarding</label>
            <p class="text-gray-900">{{ $invoice->regarding }}</p>
        </div>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi Proyek</label>
        <p class="text-gray-900">{{ $invoice->project_description }}</p>
    </div>

    <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-2">Item-Item Invoice</label>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border border-gray-300 px-2 py-2 text-left text-sm">No</th>
                        <th class="border border-gray-300 px-2 py-2 text-left text-sm">Keterangan</th>
                        <th class="border border-gray-300 px-2 py-2 text-right text-sm">Volume</th>
                        <th class="border border-gray-300 px-2 py-2 text-left text-sm">Satuan</th>
                        <th class="border border-gray-300 px-2 py-2 text-right text-sm">Harga</th>
                        <th class="border border-gray-300 px-2 py-2 text-right text-sm">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                    @endphp
                    @foreach ($items as $index => $item)
                        <tr>
                            <td class="border border-gray-300 px-2 py-2 text-sm">{{ $index + 1 }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-sm">
                                {{ $item['keterangan'] ?? '-' }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right text-sm">
                                {{ number_format($item['volume'] ?? 0, 2, ',', '.') }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-sm">
                                {{ $item['satuan'] ?? '-' }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right text-sm">
                                Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}</td>
                            <td class="border border-gray-300 px-2 py-2 text-right text-sm font-semibold">
                                Rp
                                {{ number_format(($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-primary/10 font-bold">
                        <td colspan="5" class="border border-gray-300 px-2 py-2 text-right text-sm">
                            TOTAL</td>
                        <td class="border border-gray-300 px-2 py-2 text-right text-sm text-primary">
                            Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p class="text-sm text-gray-700"><span class="font-semibold">Terbilang:</span> <span
                class="italic">{{ terbilang($invoice->total_amount) }} Rupiah</span></p>
    </div>

</x-modal>
