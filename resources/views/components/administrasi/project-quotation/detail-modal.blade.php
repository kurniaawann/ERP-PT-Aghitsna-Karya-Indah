{{-- Modal Detail Penawaran Proyek --}}
<x-modal id="detailModal-{{ $quotation->quotation_number }}" title="Detail Penawaran Proyek" :hideFooter="true">

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

        @if ($quotation->attachment)
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-1">Lampiran</label>
                <p class="text-gray-900">{{ $quotation->attachment }}</p>
            </div>
        @endif

        @if ($quotation->proyek)
            <div>
                <label class="block text-sm font-semibold text-text-primary mb-1">Nama Proyek</label>
                <p class="text-gray-900">{{ $quotation->proyek }}</p>
            </div>
        @endif
    </div>

    @if ($quotation->project_description)
        <div class="mb-4">
            <label class="block text-sm font-semibold text-text-primary mb-1">Deskripsi Proyek</label>
            <p class="text-gray-900">{{ $quotation->project_description }}</p>
        </div>
    @endif

    @if ($quotation->location || $quotation->city)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            @if ($quotation->location)
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1">Lokasi</label>
                    <p class="text-gray-900">{{ $quotation->location }}</p>
                </div>
            @endif
            @if ($quotation->city)
                <div>
                    <label class="block text-sm font-semibold text-text-primary mb-1">Kota</label>
                    <p class="text-gray-900">{{ $quotation->city }}</p>
                </div>
            @endif
        </div>
    @endif

    @php
        $detailItems = $quotation->items ?? [];
    @endphp
    <div class="mb-4">
        <label class="block text-sm font-semibold text-text-primary mb-1">Detail Item</label>
        <div class="overflow-x-auto border-2 border-gray-300 rounded-xl overflow-hidden">
            <table class="w-full border-collapse border border-border-strong">
                <thead class="bg-surface-hover">
                    <tr>
                        <th class="border border-border-strong px-2 py-2 text-left text-sm">Keterangan</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Volume</th>
                        <th class="border border-border-strong px-2 py-2 text-left text-sm">Satuan</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Harga Satuan</th>
                        <th class="border border-border-strong px-2 py-2 text-right text-sm">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($detailItems as $item)
                        <tr>
                            <td class="border border-border-strong px-2 py-2 text-sm">{{ $item['keterangan'] ?? '-' }}</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm">
                                {{ isset($item['volume']) && $item['volume'] !== null && $item['volume'] !== ''
                                    ? number_format((float) $item['volume'], 2, ',', '.')
                                    : '-' }}
                            </td>
                            <td class="border border-border-strong px-2 py-2 text-sm">{{ $item['satuan'] ?? '-' }}</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm">
                                Rp {{ number_format($item['harga'] ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm font-semibold">
                                Rp {{ number_format((float) ($item['volume'] ?? 0) * ($item['harga'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-border-strong px-2 py-4 text-center text-sm text-text-secondary">
                                Belum ada item.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4 border-t-2 border-gray-300 pt-4">
        @if ($quotation->discount_type && (float) $quotation->discount_value > 0)
            @php
                $discountAmount = $quotation->getDiscountAmount();
            @endphp
            <div class="flex justify-between items-center py-1">
                <span class="text-sm text-text-label">Discount
                    ({{ $quotation->discount_type === 'percentage' ? $quotation->discount_value . '%' : 'Nominal' }}):</span>
                <span class="text-sm font-semibold text-red-600">Rp
                    {{ number_format($discountAmount, 0, ',', '.') }}</span>
            </div>
        @endif

        @if ($quotation->total_after_discount !== null)
            <div class="flex justify-between items-center py-1">
                <span class="text-sm text-text-label">Total Setelah Discount:</span>
                <span class="text-sm font-semibold text-green-700">Rp
                    {{ number_format($quotation->total_after_discount, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="flex justify-between items-center bg-primary/10 p-4 rounded-lg mt-2">
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
