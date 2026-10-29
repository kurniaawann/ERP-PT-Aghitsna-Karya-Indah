{{-- Modal Detail Invoice Proyek (gaya Filament, hanya baca).
     Dibuka dari no. invoice / menu Aksi "Lihat Detail" di tabel:
     openModal('detailModal-{invoice_number}'). Tanpa tombol "Tutup" —
     tutup lewat tombol X, klik di luar, atau Escape. --}}
@props(['invoice'])

@php
    $isAdmin = auth()->user()->isAdmin();

    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : ($invoice->items ?? []);
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
    $progressTone = $progressPercent == 100 ? 'emerald' : ($progressPercent > 0 ? 'amber' : 'red');

    // Angka persen tanpa desimal tak berguna (12,50 → 12,5; 10,00 → 10)
    $discountValueDisplay = format_angka((float) $invoice->discount_value);
    $dpValueDisplay = format_angka((float) $invoice->dp_value);
    $ppnValueDisplay = format_angka((float) $invoice->ppn);

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

    // Rekap Proyek yang ditautkan (opsional): ringkasan nilai proyek vs total
    // tagihan seluruh invoice tertaut (setelah diskon, sebelum PPN).
    $linkedRecap = $invoice->projectRecap;
    $recapTotal = $linkedRecap ? $linkedRecap->getTotalAmount() : 0;
    $recapInvoiced = $linkedRecap ? $linkedRecap->getInvoicedAmount() : 0;
    $recapThisBilled = $linkedRecap ? $invoice->getBilledAmount() : 0;
    $recapRemaining = $recapTotal - $recapInvoiced;

    $rupiah = fn ($value) => 'Rp ' . number_format($value, 0, ',', '.');
@endphp

<x-fi.modal id="detailModal-{{ $invoice->invoice_number }}" heading="Invoice {{ $invoice->invoice_number }}"
    :description="$isAdmin ? 'Detail invoice' : 'Detail invoice proyek'" icon="document-text" width="4xl">
    <div class="grid gap-y-6">

        {{-- Informasi Invoice --}}
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-gray-500">Tanggal Invoice</dt>
                <dd class="mt-1 font-medium text-gray-950">{{ $invoice->invoice_date->format('d F Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Jatuh Tempo</dt>
                <dd class="mt-1 text-gray-950">{{ $dueDate->format('d F Y') }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd class="mt-1">
                    <x-fi.badge :color="$invoice->isFullyPaid() ? 'success' : 'warning'">{{ $invoice->payment_status_label }}</x-fi.badge>
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">Kepada</dt>
                <dd class="mt-1 font-medium text-gray-950">{{ $invoice->recipient }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Hal / Regarding</dt>
                <dd class="mt-1 text-gray-950">{{ $invoice->regarding ?? '-' }}</dd>
            </div>
            @if (filled($invoice->proyek))
                <div>
                    <dt class="text-gray-500">Nama Proyek</dt>
                    <dd class="mt-1 text-gray-950">{{ $invoice->proyek }}</dd>
                </div>
            @endif
            @if (filled($invoice->location))
                <div>
                    <dt class="text-gray-500">Lokasi</dt>
                    <dd class="mt-1 text-gray-950">{{ $invoice->location }}</dd>
                </div>
            @endif
            <div class="sm:col-span-3">
                <dt class="text-gray-500">Deskripsi Proyek</dt>
                <dd class="mt-1 text-gray-950">{{ $invoice->project_description ?: '-' }}</dd>
            </div>
            <div class="sm:col-span-3">
                <dt class="text-gray-500">Rekap Proyek</dt>
                @if ($linkedRecap)
                    <dd class="mt-1 font-medium text-gray-950">{{ $linkedRecap->id }} — {{ $linkedRecap->project_name }}</dd>
                @else
                    <dd class="mt-1 italic text-gray-400">Tidak ditautkan ke Rekap Proyek</dd>
                @endif
            </div>
        </dl>

        {{-- Rekap Proyek Tertaut (hanya bila invoice ditautkan ke rekap) --}}
        @if ($linkedRecap)
            <div class="grid gap-y-3">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-950">Rekap Proyek Tertaut</h3>
                    <x-fi.badge color="primary">{{ $linkedRecap->id }}</x-fi.badge>
                </div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                        <p class="text-sm text-gray-500">Nilai Proyek</p>
                        <p class="mt-1 font-semibold tabular-nums text-gray-950">{{ $rupiah($recapTotal) }}</p>
                    </div>
                    <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                        <p class="text-sm text-gray-500">Invoice Ini</p>
                        <p class="mt-1 font-semibold tabular-nums text-primary">{{ $rupiah($recapThisBilled) }}</p>
                    </div>
                    <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                        <p class="text-sm text-gray-500">Total Ditagih ({{ $linkedRecap->invoices->count() }} invoice)</p>
                        <p class="mt-1 font-semibold tabular-nums text-sky-600">{{ $rupiah($recapInvoiced) }}</p>
                    </div>
                    <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                        <p class="text-sm text-gray-500">Sisa Tagihan</p>
                        <p @class(['mt-1 font-semibold tabular-nums', 'text-red-600' => $recapRemaining < 0, 'text-emerald-600' => $recapRemaining >= 0])>
                            {{ $recapRemaining < 0 ? '-' : '' }}{{ $rupiah(abs($recapRemaining)) }}
                        </p>
                    </div>
                </div>
                @if ($recapRemaining < 0)
                    <p class="text-sm text-red-600">
                        <x-fi.icon name="exclamation-triangle" class="-mt-0.5 inline h-4 w-4" />
                        Total tagihan melebihi nilai proyek sebesar {{ $rupiah(abs($recapRemaining)) }}.
                    </p>
                @endif
                <p class="text-sm text-gray-500">Nilai tagihan dihitung dari total item setelah diskon (sebelum PPN).</p>
            </div>
        @endif

        {{-- Item Invoice + Ringkasan Finansial (subtotal, diskon, PPN, grand total) --}}
        <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10">
            <div class="relative overflow-x-auto">
                <table class="w-full table-auto divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-start font-semibold text-gray-950">No</th>
                            @if ($isAdmin)
                                <th class="px-3 py-2 text-start font-semibold text-gray-950">Deskripsi</th>
                                <th class="px-3 py-2 text-end font-semibold text-gray-950">Harga</th>
                                <th class="px-3 py-2 text-end font-semibold text-gray-950">%</th>
                            @else
                                <th class="px-3 py-2 text-start font-semibold text-gray-950">Keterangan</th>
                                <th class="px-3 py-2 text-end font-semibold text-gray-950">Volume</th>
                                <th class="px-3 py-2 text-start font-semibold text-gray-950">Satuan</th>
                                <th class="px-3 py-2 text-end font-semibold text-gray-950">Harga</th>
                            @endif
                            <th class="px-3 py-2 text-end font-semibold text-gray-950">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                        @foreach ($items as $index => $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-500">{{ $index + 1 }}</td>
                                @if ($isAdmin)
                                    <td class="min-w-[12rem] whitespace-normal px-3 py-2 font-medium">{{ $item['deskripsi'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-end tabular-nums">{{ $rupiah($item['harga'] ?? 0) }}</td>
                                    <td class="px-3 py-2 text-end tabular-nums">
                                        {{-- Persentase opsional: kosong → "-" dan Jumlah = Harga --}}
                                        @if (isset($item['persentase']) && $item['persentase'] !== '')
                                            {{ format_angka($item['persentase']) }}%
                                        @else
                                            -
                                        @endif
                                    </td>
                                @else
                                    <td class="min-w-[12rem] whitespace-normal px-3 py-2 font-medium">{{ $item['keterangan'] ?? '-' }}</td>
                                    {{-- Volume & satuan opsional: kosong → "-" dan Jumlah = Harga (borongan) --}}
                                    <td class="px-3 py-2 text-end tabular-nums">
                                        {{ \App\Models\Finance\InvoiceProyek::hasEmptyVolume($item) ? '-' : format_angka($item['volume']) }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ filled($item['satuan'] ?? null) ? $item['satuan'] : '-' }}</td>
                                    <td class="px-3 py-2 text-end tabular-nums">{{ $rupiah($item['harga'] ?? 0) }}</td>
                                @endif
                                <td class="px-3 py-2 text-end font-medium tabular-nums">
                                    {{ $rupiah(\App\Models\Finance\InvoiceProyek::itemAmount($item)) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50 text-gray-950">
                        @php $labelColspan = $isAdmin ? 4 : 5; @endphp
                        <tr>
                            <td colspan="{{ $labelColspan }}" class="px-3 py-2 text-end text-gray-500">Subtotal</td>
                            <td class="whitespace-nowrap px-3 py-2 text-end tabular-nums">{{ $rupiah($totalAmount) }}</td>
                        </tr>
                        <tr>
                            @if ($invoice->discount_value && $invoice->discount_value > 0)
                                <td colspan="{{ $labelColspan }}" class="px-3 py-2 text-end text-red-600">
                                    Discount {{ $invoice->discount_type === 'percentage' ? '(' . $discountValueDisplay . '%)' : '(Nominal)' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-end font-medium tabular-nums text-red-600">-{{ $rupiah($discountAmount) }}</td>
                            @else
                                <td colspan="{{ $labelColspan }}" class="px-3 py-2 text-end text-gray-400">Discount</td>
                                <td class="whitespace-nowrap px-3 py-2 text-end italic text-gray-400">Tidak ada discount</td>
                            @endif
                        </tr>
                        <tr>
                            @if ($invoice->ppn && $invoice->ppn > 0)
                                <td colspan="{{ $labelColspan }}" class="px-3 py-2 text-end text-purple-700">PPN ({{ $ppnValueDisplay }}%)</td>
                                <td class="whitespace-nowrap px-3 py-2 text-end font-medium tabular-nums text-purple-700">+{{ $rupiah($ppnAmount) }}</td>
                            @else
                                <td colspan="{{ $labelColspan }}" class="px-3 py-2 text-end text-gray-400">PPN</td>
                                <td class="whitespace-nowrap px-3 py-2 text-end italic text-gray-400">Tidak ada PPN</td>
                            @endif
                        </tr>
                        <tr class="border-t border-gray-200">
                            <td colspan="{{ $labelColspan }}" class="px-3 py-2.5 text-end font-semibold">Grand Total</td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-end text-base font-semibold tabular-nums">{{ $rupiah($grandTotal) }}</td>
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
                        {{ $rupiah($remaining) }}
                    </p>
                </div>
            </div>

            {{-- Ringkasan perhitungan: total → diskon → PPN → DP → terbayar → sisa --}}
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

                    @if ($ppnAmount > 0)
                        <span class="text-gray-400">→</span>
                        <span class="font-medium text-purple-600">PPN</span>
                        <span class="font-semibold tabular-nums text-purple-600">+{{ $rupiah($ppnAmount) }}</span>
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

            {{-- Progress pembayaran ((terbayar + DP) / grand total) --}}
            <div>
                <div class="mb-1.5 flex justify-between text-sm text-gray-500">
                    <span>Progress Pembayaran</span>
                    <span @class(['font-semibold tabular-nums', 'text-emerald-600' => $progressTone === 'emerald', 'text-amber-600' => $progressTone === 'amber', 'text-red-600' => $progressTone === 'red'])>
                        {{ $progressPercent }}%
                    </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                    <div @class(['h-full rounded-full transition-all duration-500 ease-in-out', 'bg-emerald-500' => $progressTone === 'emerald', 'bg-amber-400' => $progressTone === 'amber', 'bg-red-400' => $progressTone === 'red'])
                        style="width: {{ $progressPercent }}%"></div>
                </div>
                <p class="mt-1.5 text-sm">
                    @if ($isFullyPaid)
                        <span class="inline-flex items-center gap-1 font-medium text-emerald-600">
                            <x-fi.icon name="check-circle" class="h-4 w-4" /> Sudah Lunas
                        </span>
                    @elseif ($totalPaid > 0)
                        <span class="inline-flex items-center gap-1 font-medium text-amber-600">
                            <x-fi.icon name="clock" class="h-4 w-4" /> Sebagian ({{ $progressPercent }}% terbayar)
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 font-medium text-red-600">
                            <x-fi.icon name="exclamation-circle" class="h-4 w-4" /> Belum ada pembayaran
                        </span>
                    @endif
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            {{-- Pembayaran Bertahap --}}
            <div class="md:col-span-2">
                <h3 class="text-sm font-semibold text-gray-950">Pembayaran Bertahap</h3>

                @if ($hasInstallments || !empty($storedInstallments))
                    @php
                        $installmentCount = $hasInstallments ? $installmentProofs->count() : count($storedInstallments);
                    @endphp

                    <div class="mt-2 flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-4 py-3 text-sm">
                        <div>
                            <p class="text-gray-500">Tahap Pembayaran</p>
                            <p class="font-semibold text-gray-950">{{ $installmentCount }} tahap tersedia</p>
                        </div>
                        <div class="text-end">
                            <p class="text-gray-500">Status</p>
                            @if ($isFullyPaid)
                                <p class="font-semibold text-emerald-600">Semua lunas</p>
                            @else
                                <p class="font-semibold text-amber-600">Belum selesai</p>
                            @endif
                        </div>
                    </div>

                    <ol class="mt-3 divide-y divide-gray-200 rounded-xl ring-1 ring-gray-950/10">
                        @if ($hasInstallments)
                            @foreach ($installmentProofs as $proofIndex => $proof)
                                @php
                                    $stageNumber = $proofIndex + 1;
                                    $proofAmount = (int) ($proof->amount ?? 0);
                                    $proofDate = $proof->created_at ? \Carbon\Carbon::parse($proof->created_at)->format('d M Y') : '-';
                                    $isInstallmentPaid = $proofAmount > 0;
                                @endphp
                                <li class="flex items-start gap-3 px-3 py-3">
                                    <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold', 'bg-emerald-500 text-white' => $isInstallmentPaid, 'bg-gray-100 text-gray-500' => ! $isInstallmentPaid])>
                                        {{ $proof->payment_stage ?? $stageNumber }}
                                    </span>
                                    <div class="grid min-w-0 flex-1 gap-y-0.5 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <p @class(['font-medium', 'text-gray-950' => $isInstallmentPaid, 'text-gray-500' => ! $isInstallmentPaid])>
                                                Pembayaran ke-{{ $proof->payment_stage ?? $stageNumber }}
                                            </p>
                                            <span @class(['font-semibold tabular-nums', 'text-emerald-600' => $isInstallmentPaid, 'text-gray-400' => ! $isInstallmentPaid])>
                                                {{ $rupiah($proofAmount) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-gray-500">{{ $proofDate }}</p>
                                            <x-fi.badge :color="$isInstallmentPaid ? 'success' : 'gray'">{{ $isInstallmentPaid ? 'Lunas' : 'Belum dibayar' }}</x-fi.badge>
                                        </div>
                                        @if (!empty($proof->file_path))
                                            {{-- Bukti dibuka di modal pratinjau (tanpa tab baru) --}}
                                            <a href="{{ asset('storage/' . $proof->file_path) }}" title="{{ $proof->file_name }}"
                                                onclick="if (window.openFilePreview) { event.preventDefault(); window.openFilePreview(this.href, { title: @js('Bukti Pembayaran ke-' . ($proof->payment_stage ?? $stageNumber)), downloadName: @js($proof->file_name) }); }"
                                                class="inline-flex items-center gap-1 justify-self-start font-medium text-primary hover:underline">
                                                <x-fi.icon name="paper-clip" class="h-4 w-4" /> Lihat Bukti
                                            </a>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        @else
                            @foreach ($storedInstallments as $instIndex => $installment)
                                @php
                                    $instAmount = (int) ($installment['amount'] ?? 0);
                                    $instLabel = $installment['label'] ?? ('Pembayaran ke-' . ($instIndex + 1));
                                    $isInstallmentPaid = $instAmount > 0;
                                @endphp
                                <li class="flex items-start gap-3 px-3 py-3">
                                    <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold', 'bg-emerald-500 text-white' => $isInstallmentPaid, 'bg-gray-100 text-gray-500' => ! $isInstallmentPaid])>
                                        {{ $instIndex + 1 }}
                                    </span>
                                    <div class="grid min-w-0 flex-1 gap-y-0.5 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <p @class(['font-medium', 'text-gray-950' => $isInstallmentPaid, 'text-gray-500' => ! $isInstallmentPaid])>{{ $instLabel }}</p>
                                            <span @class(['font-semibold tabular-nums', 'text-emerald-600' => $isInstallmentPaid, 'text-gray-400' => ! $isInstallmentPaid])>
                                                {{ $rupiah($instAmount) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-end">
                                            <x-fi.badge :color="$isInstallmentPaid ? 'success' : 'gray'">{{ $isInstallmentPaid ? 'Lunas' : 'Belum dibayar' }}</x-fi.badge>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        @endif
                    </ol>
                @else
                    <div class="mt-2 rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center">
                        <x-fi.icon name="clock" class="mx-auto h-6 w-6 text-gray-300" />
                        <p class="mt-2 text-sm font-medium text-gray-500">Belum ada pembayaran bertahap</p>
                        <p class="mt-0.5 text-sm text-gray-400">Pembayaran akan muncul setelah bukti pembayaran diupload</p>
                    </div>
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
            <x-fi.button color="gray" icon="printer" :href="route('proyek-invoice.print.pdf', $invoice->invoice_number)">Cetak PDF</x-fi.button>
            <x-fi.button icon="pencil-square" :href="route('proyek-invoice.edit', $invoice->invoice_number)">Edit Invoice</x-fi.button>
        </div>
    </div>
</x-fi.modal>
