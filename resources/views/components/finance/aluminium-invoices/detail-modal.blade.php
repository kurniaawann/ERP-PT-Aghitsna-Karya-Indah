{{-- Modal Detail Invoice Alumunium (gaya Filament, hanya baca).
     Dibuka dari no. invoice / menu Aksi "Lihat Detail" di tabel:
     openModal('detailModal-{invoice_number}'). Tanpa tombol "Tutup" —
     tutup lewat tombol X, klik di luar, atau Escape. --}}
@props(['invoice'])

@php
    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : ($invoice->items ?? []);
    $totalAmount = (int) ($invoice->total_amount ?? 0);
    $netAmount = $invoice->getNetAmount();
    $totalPaid = $invoice->getTotalPaidAmount();
    $remaining = $invoice->getRemainingAmount();
    $isFullyPaid = $invoice->isFullyPaid();

    $discountAmount = (int) $invoice->getDiscountAmount();
    $dpAmount = (int) $invoice->getDpAmount();

    // Nilai tampilan tanpa nol berlebih (mis. 10.50 → 10,5)
    $discountValueDisplay = format_angka((float) $invoice->discount_value);
    $dpValueDisplay = format_angka((float) $invoice->dp_value);

    $proofs = $invoice->paymentProofs;
    $rupiah = fn ($value) => 'Rp ' . number_format($value, 0, ',', '.');
@endphp

<x-fi.modal id="detailModal-{{ $invoice->invoice_number }}" heading="Invoice {{ $invoice->invoice_number }}"
    description="Detail invoice alumunium" icon="document-text" width="4xl">
    <div class="grid gap-y-6">

        {{-- Informasi Invoice --}}
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-gray-500">Tanggal Invoice</dt>
                <dd class="mt-1 font-medium text-gray-950">{{ $invoice->invoice_date?->format('d F Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Kepada</dt>
                <dd class="mt-1 font-medium text-gray-950">{{ $invoice->recipient }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd class="mt-1">
                    <x-fi.badge :color="$isFullyPaid ? 'success' : 'warning'">{{ $invoice->payment_status_label }}</x-fi.badge>
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

        {{-- Item Invoice + Ringkasan Finansial (subtotal, diskon, grand total) --}}
        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10">
            <div class="relative overflow-x-auto">
                <table class="w-full table-auto divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950">No</th>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950">Keterangan</th>
                            <th class="px-3 py-2 text-end font-semibold text-gray-950">Volume</th>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950">Satuan</th>
                            <th class="px-3 py-2 text-end font-semibold text-gray-950">Harga</th>
                            <th class="px-3 py-2 text-end font-semibold text-gray-950">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                        @foreach ($items as $index => $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-500">{{ $index + 1 }}</td>
                                <td class="min-w-[12rem] whitespace-normal px-3 py-2 font-medium">{{ $item['keterangan'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-end tabular-nums">{{ format_angka($item['volume'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $item['satuan'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-end tabular-nums">{{ $rupiah($item['harga'] ?? 0) }}</td>
                                <td class="px-3 py-2 text-end font-medium tabular-nums">
                                    {{ $rupiah(($item['volume'] ?? 0) * ($item['harga'] ?? 0)) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50 text-gray-950">
                        <tr>
                            <td colspan="5" class="px-3 py-2 text-end text-gray-500">Subtotal</td>
                            <td class="whitespace-nowrap px-3 py-2 text-end tabular-nums">{{ $rupiah($totalAmount) }}</td>
                        </tr>
                        <tr>
                            @if ($invoice->discount_value && $invoice->discount_value > 0)
                                <td colspan="5" class="px-3 py-2 text-end text-red-600">
                                    Discount {{ $invoice->discount_type === 'percentage' ? '(' . $discountValueDisplay . '%)' : '(Nominal)' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-end font-medium tabular-nums text-red-600">-{{ $rupiah($discountAmount) }}</td>
                            @else
                                <td colspan="5" class="px-3 py-2 text-end text-gray-400">Discount</td>
                                <td class="whitespace-nowrap px-3 py-2 text-end italic text-gray-400">Tidak ada discount</td>
                            @endif
                        </tr>
                        <tr class="border-t border-gray-200">
                            <td colspan="5" class="px-3 py-2.5 text-end font-semibold">Grand Total</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-end text-base font-semibold tabular-nums">{{ $rupiah($netAmount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Pembayaran --}}
        <div class="grid gap-y-3">
            <h3 class="text-sm font-semibold text-gray-950">Pembayaran</h3>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">DP / Uang Muka</p>
                    @if ($invoice->dp_value && $invoice->dp_value > 0)
                        <p class="mt-1 text-lg font-semibold tabular-nums text-primary">{{ $rupiah($dpAmount) }}</p>
                        @if ($invoice->dp_type === 'percentage')
                            <p class="text-sm text-gray-500">({{ $dpValueDisplay }}% dari Grand Total)</p>
                        @endif
                    @else
                        <p class="mt-1 text-sm italic text-gray-400">Belum ada DP</p>
                    @endif
                </div>
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Total Terbayar</p>
                    @if ($totalPaid > 0)
                        <p class="mt-1 text-lg font-semibold tabular-nums text-emerald-600">{{ $rupiah($totalPaid) }}</p>
                    @else
                        <p class="mt-1 text-sm italic text-gray-400">Belum ada pembayaran</p>
                    @endif
                </div>
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Sisa Pembayaran</p>
                    <p @class(['mt-1 text-lg font-semibold tabular-nums', 'text-red-600' => $remaining > 0, 'text-emerald-600' => $remaining <= 0])>
                        {{ $rupiah(max($remaining, 0)) }}
                    </p>
                </div>
            </div>

            {{-- Ringkasan perhitungan: total → diskon → DP → terbayar → sisa --}}
            <div class="rounded-xl bg-gray-50 p-4">
                <p class="mb-2 text-sm text-gray-500">Ringkasan Perhitungan</p>
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                    <span class="font-medium text-gray-700">Total Keseluruhan</span>
                    <span class="font-semibold tabular-nums text-gray-950">{{ $rupiah($totalAmount) }}</span>

                    @if ($discountAmount > 0)
                        <span class="text-gray-400">→</span>
                        <span class="font-medium text-red-600">Discount</span>
                        <span class="font-semibold tabular-nums text-red-600">-{{ $rupiah($discountAmount) }}</span>
                    @endif

                    @if ($dpAmount > 0)
                        <span class="text-gray-400">→</span>
                        <span class="font-medium text-primary">DP</span>
                        <span class="font-semibold tabular-nums text-primary">-{{ $rupiah($dpAmount) }}</span>
                    @endif

                    <span class="text-gray-400">→</span>
                    <span class="font-medium text-emerald-600">Terbayar</span>
                    <span class="font-semibold tabular-nums text-emerald-600">-{{ $rupiah($totalPaid) }}</span>

                    <span class="text-gray-400">→</span>
                    <span @class(['font-medium', 'text-red-600' => $remaining > 0, 'text-emerald-600' => $remaining <= 0])>Sisa Pembayaran</span>
                    <span @class(['font-semibold tabular-nums', 'text-red-600' => $remaining > 0, 'text-emerald-600' => $remaining <= 0])>{{ $rupiah($remaining) }}</span>
                </div>
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
                                        <span class="font-medium tabular-nums text-emerald-600">{{ $rupiah($proof->amount ?? 0) }}</span>
                                        <span class="font-medium text-primary">Lihat</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-2 rounded-xl border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-500">
                        Belum ada bukti pembayaran. Upload melalui menu Aksi “Upload Bukti Bayar”.
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
            <x-fi.button color="gray" icon="printer" :href="route('alumunium-invoice.print.pdf', $invoice->invoice_number)">Cetak PDF</x-fi.button>
            <x-fi.button icon="pencil-square" :href="route('alumunium-invoice.edit', $invoice->invoice_number)">Edit Invoice</x-fi.button>
        </div>
    </div>
</x-fi.modal>
