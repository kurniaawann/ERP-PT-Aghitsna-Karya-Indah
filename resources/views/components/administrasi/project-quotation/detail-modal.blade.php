{{-- Modal Detail Penawaran Proyek --}}
<x-modal id="detailModal-{{ $quotation->quotation_number }}" title="Detail Penawaran Proyek" :readOnly="true">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">No. Penawaran</label>
            <p class="text-gray-900 font-medium">{{ $quotation->quotation_number }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">Tanggal</label>
            <p class="text-gray-900">{{ \Carbon\Carbon::parse($quotation->date)->isoFormat('DD MMMM YYYY') }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">Kepada</label>
            <p class="text-gray-900">{{ $quotation->recipient }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">Perihal</label>
            <p class="text-gray-900">{{ $quotation->subject }}</p>
        </div>
    </div>

    @if ($quotation->recipient_address)
        <div class="mb-4">
            <label class="block text-sm font-semibold text-text-primary mb-1">Alamat</label>
            <p class="text-gray-900">{{ $quotation->recipient_address }}</p>
        </div>
    @endif

    <div class="mb-4">
        <label class="block text-sm font-semibold text-text-primary mb-2">Detail Kelompok & Item</label>
        <div class="space-y-4">
            @foreach ($quotation->groups as $group)
                <div class="border-2 border-gray-300 rounded-xl bg-gray-50 overflow-hidden">
                    <div class="bg-gray-200 px-4 py-2">
                        <span class="font-bold text-sm text-gray-700">{{ $group->name }}</span>
                    </div>
                    <div class="p-4">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse border border-border-strong">
                                <thead class="bg-surface-hover">
                                    <tr>
                                        <th class="border border-border-strong px-2 py-2 text-left text-sm">Keterangan
                                        </th>
                                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Volume</th>
                                        <th class="border border-border-strong px-2 py-2 text-left text-sm">Satuan</th>
                                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Harga
                                            Satuan</th>
                                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group->items as $item)
                                        <tr>
                                            <td class="border border-border-strong px-2 py-2 text-sm">
                                                {{ $item->description }}
                                            </td>
                                            <td class="border border-border-strong px-2 py-2 text-right text-sm">
                                                @if ($item->volume)
                                                    {{ number_format($item->volume, 2, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="border border-border-strong px-2 py-2 text-sm">
                                                {{ $item->unit ?? '-' }}
                                            </td>
                                            <td class="border border-border-strong px-2 py-2 text-right text-sm">
                                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                            </td>
                                            <td
                                                class="border border-border-strong px-2 py-2 text-right text-sm font-semibold">
                                                Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-gray-100 font-bold">
                                        <td colspan="4"
                                            class="border border-border-strong px-2 py-2 text-right text-sm">
                                            Subtotal Kelompok
                                        </td>
                                        <td
                                            class="border border-border-strong px-2 py-2 text-right text-sm text-green-700">
                                            Rp {{ number_format($group->subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-4 border-t-2 border-gray-300 pt-4">
        <div class="flex justify-between items-center bg-primary/10 p-4 rounded-lg">
            <span class="text-lg font-bold text-gray-700">TOTAL PENAWARAN</span>
            <span class="text-xl font-bold text-primary">Rp
                {{ number_format($quotation->total_amount, 0, ',', '.') }}</span>
        </div>
    </div>

    @if ($quotation->amount_in_words)
        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <p class="text-sm text-text-primary">
                <span class="font-semibold">Terbilang:</span>
                <span class="italic">{{ $quotation->amount_in_words }}</span>
            </p>
        </div>
    @endif

</x-modal>
