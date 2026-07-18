{{-- =====================================================================
     Komponen Modal Detail Surat Jalan (Delivery Note)

     Menampilkan data surat jalan secara lengkap dalam mode read-only.
     Satu modal per data surat jalan.
     ===================================================================== --}}

<x-modal id="detailModal-{{ $deliveryNote->id_delivery_note }}" title="Detail Surat Jalan" :readonly="true" size="4xl">

    {{-- Info Dokumen --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <div class="bg-surface-secondary p-3 rounded-lg">
            <p class="text-xs font-semibold text-text-label mb-1">No. Dokumen</p>
            <p class="text-sm font-medium text-primary">{{ $deliveryNote->id_delivery_note }}</p>
        </div>
        <div class="bg-surface-secondary p-3 rounded-lg">
            <p class="text-xs font-semibold text-text-label mb-1">Nomor Dokumen</p>
            <p class="text-sm font-medium text-text-primary">{{ $deliveryNote->document_number }}</p>
        </div>
        <div class="bg-surface-secondary p-3 rounded-lg">
            <p class="text-xs font-semibold text-text-label mb-1">Tanggal Pengiriman</p>
            <p class="text-sm font-medium text-text-primary">{{ \Carbon\Carbon::parse($deliveryNote->delivery_date)->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Pengirim & Penerima --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
        <div class="border border-border-strong rounded-lg p-3">
            <h4 class="text-xs font-semibold text-text-primary mb-2 pb-1 border-b border-border-strong">
                <i class="fa-solid fa-truck mr-1"></i> Pengirim
            </h4>
            <p class="text-sm text-text-primary font-medium">{{ $deliveryNote->shipper_name }}</p>
            <p class="text-xs text-text-secondary whitespace-pre-wrap">{{ $deliveryNote->shipper_address }}</p>
        </div>
        <div class="border border-border-strong rounded-lg p-3">
            <h4 class="text-xs font-semibold text-text-primary mb-2 pb-1 border-b border-border-strong">
                <i class="fa-solid fa-box-open mr-1"></i> Penerima
            </h4>
            <p class="text-sm text-text-primary font-medium">{{ $deliveryNote->receiver_name }}</p>
            <p class="text-xs text-text-secondary whitespace-pre-wrap">{{ $deliveryNote->receiver_address }}</p>
        </div>
    </div>

    {{-- Deskripsi --}}
    @if ($deliveryNote->description)
        <div class="mb-4">
            <p class="text-xs font-semibold text-text-label mb-1">Deskripsi</p>
            <div class="bg-surface-secondary p-3 rounded-lg">
                <p class="text-sm text-text-primary whitespace-pre-wrap">{{ $deliveryNote->description }}</p>
            </div>
        </div>
    @endif

    {{-- Daftar Barang --}}
    <div class="mb-4">
        <p class="text-xs font-semibold text-text-label mb-2">Daftar Barang</p>
        <div class="overflow-x-auto border border-border-strong rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-text-label">No</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-text-label">Nama Barang</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-text-label">Jumlah</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-text-label">Satuan</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-text-label">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($deliveryNote->items as $item)
                        <tr class="hover:bg-surface-secondary">
                            <td class="px-3 py-2 text-center text-text-primary">{{ $item['no'] }}</td>
                            <td class="px-3 py-2 font-medium text-text-primary">
                                {{ data_get($item, 'item_name', data_get($item, 'name', '-')) }}
                            </td>
                            <td class="px-3 py-2 text-center text-text-primary">{{ $item['quantity'] }}</td>
                            <td class="px-3 py-2 text-center text-text-primary">{{ $item['unit'] ?? 'pcs' }}</td>
                            <td class="px-3 py-2 text-text-secondary">{{ $item['notes'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-text-secondary">Tidak ada barang</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-surface-secondary">
                    <tr>
                        <td colspan="2" class="px-3 py-2 text-right font-bold text-text-primary">Total</td>
                        <td class="px-3 py-2 text-center font-bold text-primary">{{ $deliveryNote->total_quantity }}</td>
                        <td colspan="2" class="px-3 py-2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Sopir & Kendaraan --}}
    @if ($deliveryNote->driver_name || $deliveryNote->vehicle_number)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
            @if ($deliveryNote->driver_name)
                <div class="bg-surface-secondary p-3 rounded-lg">
                    <p class="text-xs font-semibold text-text-label mb-1">Nama Sopir</p>
                    <p class="text-sm text-text-primary">{{ $deliveryNote->driver_name }}</p>
                </div>
            @endif
            @if ($deliveryNote->vehicle_number)
                <div class="bg-surface-secondary p-3 rounded-lg">
                    <p class="text-xs font-semibold text-text-label mb-1">Nomor Kendaraan</p>
                    <p class="text-sm text-text-primary">{{ $deliveryNote->vehicle_number }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Catatan Tambahan --}}
    @if ($deliveryNote->notes)
        <div class="mb-4">
            <p class="text-xs font-semibold text-text-label mb-1">Catatan Tambahan</p>
            <div class="bg-surface-secondary p-3 rounded-lg">
                <p class="text-sm text-text-primary whitespace-pre-wrap">{{ $deliveryNote->notes }}</p>
            </div>
        </div>
    @endif

    {{-- Info Sistem --}}
    <div class="pt-3 border-t border-border-strong flex gap-4 text-xs text-text-secondary">
        <span><i class="fa-solid fa-clock mr-1"></i> Dibuat: {{ $deliveryNote->created_at->format('d/m/Y H:i') }}</span>
        <span><i class="fa-solid fa-edit mr-1"></i> Diperbarui: {{ $deliveryNote->updated_at->format('d/m/Y H:i') }}</span>
    </div>

</x-modal>
