{{-- Modal Detail Invoice Barang (gaya Filament, hanya baca).
     Dibuka dari no. invoice / menu Aksi "Lihat Detail" di tabel:
     openModal('detailModal-{invoice_number}'). --}}
@props(['invoice'])

@php
    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : ($invoice->items ?? []);
    $proofs = $invoice->paymentProofs;
    $isLunas = $invoice->salesRecap?->status === 'Lunas';
@endphp

<x-fi.modal id="detailModal-{{ $invoice->invoice_number }}" heading="Invoice {{ $invoice->invoice_number }}"
    description="Detail invoice barang" icon="document-text" width="4xl">
    <div class="grid gap-y-6">

        {{-- Informasi Invoice --}}
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-gray-500">Tanggal</dt>
                <dd class="mt-1 font-medium text-gray-950">{{ $invoice->invoice_date?->format('d M Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Kepada</dt>
                <dd class="mt-1 font-medium text-gray-950">{{ $invoice->recipient }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd class="mt-1">
                    <x-fi.badge :color="$isLunas ? 'success' : 'warning'">{{ $invoice->status_label }}</x-fi.badge>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Hal</dt>
                <dd class="mt-1 text-gray-950">{{ $invoice->regarding ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Nama Proyek</dt>
                <dd class="mt-1 text-gray-950">{{ $invoice->proyek ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Deskripsi Proyek</dt>
                <dd class="mt-1 text-gray-950">{{ $invoice->project_description ?: '-' }}</dd>
            </div>
        </dl>

        {{-- Item Invoice --}}
        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10">
            <div class="overflow-x-auto">
                <table class="w-full table-auto divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950">No</th>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950">Nama Barang</th>
                            <th class="px-3 py-2 text-end font-semibold text-gray-950">Qty</th>
                            <th class="px-3 py-2 text-end font-semibold text-gray-950">Harga Modal</th>
                            <th class="px-3 py-2 text-end font-semibold text-gray-950">Harga Jual</th>
                            <th class="px-3 py-2 text-end font-semibold text-gray-950">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                        @foreach ($items as $index => $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-500">{{ $index + 1 }}</td>
                                <td class="min-w-[12rem] whitespace-normal px-3 py-2">
                                    <div class="font-medium">{{ $item['name_item'] ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ ! empty($item['from_stock']) ? 'Dari stok' : 'Manual' }}</div>
                                </td>
                                <td class="px-3 py-2 text-end tabular-nums">
                                    {{ number_format($item['quantity'] ?? 0, 0, ',', '.') }}
                                    <span class="text-gray-500">{{ $item['satuan'] ?? '' }}</span>
                                </td>
                                <td class="px-3 py-2 text-end tabular-nums text-gray-700">Rp {{ number_format($item['capital_price'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-end tabular-nums">Rp {{ number_format($item['selling_price'] ?? 0, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-end font-medium tabular-nums">
                                    Rp {{ number_format(($item['selling_price'] ?? 0) * ($item['quantity'] ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50 text-gray-950">
                        <tr>
                            <td colspan="5" class="px-3 py-2 text-end text-gray-500">Total Modal</td>
                            <td class="px-3 py-2 text-end tabular-nums">Rp {{ number_format($invoice->total_capital, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="px-3 py-2 text-end text-gray-500">Profit</td>
                            <td class="px-3 py-2 text-end font-medium tabular-nums text-emerald-600">Rp {{ number_format($invoice->total_profit, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="border-t border-gray-200">
                            <td colspan="5" class="px-3 py-2.5 text-end font-semibold">Total Invoice</td>
                            <td class="px-3 py-2.5 text-end text-base font-semibold tabular-nums">Rp {{ number_format($invoice->total_selling, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            {{-- Bukti Pembayaran --}}
            <div class="md:col-span-2">
                <h3 class="text-sm font-semibold text-gray-950">Bukti Pembayaran</h3>
                @if ($proofs->isNotEmpty())
                    <ul class="mt-2 divide-y divide-gray-200 rounded-xl ring-1 ring-gray-950/10">
                        @foreach ($proofs as $proof)
                            <li>
                                <a href="{{ asset('storage/' . $proof->file_path) }}" data-file-preview data-preview-title="Bukti Pembayaran"
                                    title="{{ $proof->file_name }}"
                                    class="flex items-center justify-between gap-3 px-3 py-2.5 text-sm transition duration-75 hover:bg-gray-50">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <x-fi.icon name="paper-clip" class="h-4 w-4 shrink-0 text-gray-400" />
                                        <span class="truncate font-medium text-gray-950">{{ $proof->file_name }}</span>
                                    </span>
                                    <span class="flex shrink-0 items-center gap-3">
                                        <span class="text-gray-500">{{ optional($proof->payment_date ?? $proof->created_at)->format('d M Y') }}</span>
                                        <span class="font-medium tabular-nums text-emerald-600">Rp {{ number_format($proof->amount ?? 0, 0, ',', '.') }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 rounded-xl border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-500">
                        Belum ada bukti pembayaran yang tersimpan.
                    </p>
                @endif
            </div>

            {{-- Tanda Tangan --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-950">Tanda Tangan</h3>
                @if ($invoice->signedBy)
                    <dl class="mt-2 grid gap-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500">Penandatangan</dt>
                            <dd class="font-medium text-gray-950">{{ $invoice->signedBy->name }}</dd>
                        </div>
                        @if ($invoice->division)
                            <div>
                                <dt class="text-gray-500">Divisi</dt>
                                <dd class="text-gray-950">{{ $invoice->division->name }}</dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="mt-2 text-sm text-gray-500">Tidak ada penandatangan.</p>
                @endif
            </div>
        </div>

        {{-- Aksi (kanan; tanpa tombol Tutup — tutup lewat X / klik di luar modal) --}}
        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-4">
            <x-fi.button color="gray" icon="printer" :href="route('item-invoice.print.pdf', $invoice->invoice_number)">Cetak PDF</x-fi.button>
            <x-fi.button :icon="$isLunas ? 'lock-closed' : 'pencil-square'" :href="route('item-invoice.edit', $invoice->invoice_number)">
                {{ $isLunas ? 'Buka Invoice' : 'Edit Invoice' }}
            </x-fi.button>
        </div>
    </div>
</x-fi.modal>
