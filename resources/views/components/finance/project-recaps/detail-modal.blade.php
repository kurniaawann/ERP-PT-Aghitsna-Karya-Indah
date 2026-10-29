{{-- Modal Detail Rekap Proyek (gaya Filament, hanya baca).
     Dibuka dari No / menu Aksi "Lihat Detail" di tabel:
     openModal('detailModal-{id}'). Tanpa tombol "Tutup" — tutup lewat
     tombol X, klik di luar, atau Escape. --}}
@php
    $recapTotal = $recap->getTotalAmount();
    $recapDp = $recap->getDpAmount();
    $recapPaid = $recap->getTotalPaidAmount();
    $recapRemaining = $recap->getRemainingAmount();
    $recapProgress = $recap->getProgressPercent();
    $isFullyPaid = $recap->isFullyPaid();

    $paymentProofs = $recap->relationLoaded('paymentProofs')
        ? $recap->paymentProofs
        : $recap->paymentProofs()->get();

    $incomePayments = $recap->getIncomePayments();

    // Invoice Proyek yang ditautkan ke rekap ini. Nilai tagihan tiap invoice
    // = total item setelah diskon, sebelum PPN (sebanding dengan Total RAB).
    $recap->loadMissing('invoices.paymentProofs');
    $linkedInvoices = $recap->invoices;
    $recapInvoiced = (int) $linkedInvoices->sum(fn ($linkedInvoice) => $linkedInvoice->getBilledAmount());
    $recapUninvoiced = $recapTotal - $recapInvoiced;

    // Rincian Terbayar (setiap bukti/baris hanya dihitung sekali):
    // - bukti pembayaran yang diupload langsung ke rekap
    // - pembayaran invoice tertaut, dihitung tanpa PPN (sebanding Total RAB)
    // - uang masuk manual pada Laporan Keuangan Proyek
    $invoicePayments = $recap->getInvoicePayments();
    $recapDirectPaid = $recap->getDirectPaidAmount();
    $recapInvoicePaid = (int) $invoicePayments->sum('project_amount');
    $recapInvoicePpn = (int) $invoicePayments->sum('ppn_amount');
    $recapIncomePaid = (int) $incomePayments->sum('income_amount');
    $invoicePaymentsByInvoice = $invoicePayments->groupBy(fn ($payment) => $payment->invoice->invoice_number);

    // Invoice lama dengan nama proyek sama yang belum ditautkan (saran saja).
    $unlinkedCandidates = $recap->relationLoaded('unlinkedInvoiceCandidates')
        ? $recap->getRelation('unlinkedInvoiceCandidates')
        : collect();

    // Linimasa pembayaran: bukti rekap + bukti invoice tertaut.
    $paymentTimeline = $paymentProofs
        ->map(fn ($proof) => (object) [
            'proof' => $proof,
            'invoice' => null,
            'amount' => (int) ($proof->amount ?? 0),
            'project_amount' => (int) ($proof->amount ?? 0),
            'ppn_amount' => 0,
        ])
        ->concat($invoicePayments)
        ->sortBy(fn ($payment) => sprintf(
            '%010d-%06d',
            (int) (($payment->proof->payment_date ?? $payment->proof->created_at)?->timestamp ?? 0),
            (int) ($payment->proof->payment_stage ?? 0)
        ))
        ->values();

    $rupiah = fn ($value) => 'Rp ' . number_format($value, 0, ',', '.');
@endphp

<x-fi.modal id="detailModal-{{ $recap->id }}" heading="Rekap Proyek {{ $recap->id }}"
    :description="$recap->project_name" icon="building-office-2" width="4xl">
    <div class="grid gap-y-6">

        {{-- Informasi Rekap --}}
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-gray-500">No Rekap</dt>
                <dd class="mt-1 font-medium text-gray-950">{{ $recap->id }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">No RAB</dt>
                <dd class="mt-1 text-gray-950">{{ $recap->rab_number ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd class="mt-1">
                    <x-fi.badge :color="$isFullyPaid ? 'success' : 'warning'">{{ $isFullyPaid ? 'Sudah Lunas' : 'Belum Lunas' }}</x-fi.badge>
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-gray-500">Nama Proyek</dt>
                <dd class="mt-1 font-medium text-gray-950">{{ $recap->project_name }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Dibuat Oleh</dt>
                <dd class="mt-1 text-gray-950">{{ $recap->creator?->name ?? '-' }}</dd>
            </div>
        </dl>

        {{-- Ringkasan Finansial --}}
        <div class="grid gap-y-3">
            <h3 class="text-sm font-semibold text-gray-950">Ringkasan Finansial</h3>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Total RAB</p>
                    <p class="mt-1 text-lg font-semibold tabular-nums text-gray-950">{{ $rupiah($recapTotal) }}</p>
                </div>
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Total Terbayar</p>
                    @if ($recapPaid > 0)
                        <p class="mt-1 text-lg font-semibold tabular-nums text-emerald-600">{{ $rupiah($recapPaid) }}</p>
                    @else
                        <p class="mt-1 text-sm italic text-gray-400">Belum ada pembayaran</p>
                    @endif
                </div>
                <div class="rounded-xl p-4 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Sisa Pembayaran</p>
                    <p @class(['mt-1 text-lg font-semibold tabular-nums', 'text-red-600' => $recapRemaining > 0, 'text-emerald-600' => $recapRemaining <= 0])>
                        {{ $rupiah($recapRemaining > 0 ? $recapRemaining : 0) }}
                    </p>
                </div>
            </div>

            {{-- Rincian Total Terbayar --}}
            <dl class="grid gap-y-2 rounded-xl p-4 text-sm ring-1 ring-gray-950/10">
                <p class="text-sm font-medium text-gray-500">Rincian Terbayar</p>
                <div class="flex items-center justify-between gap-3">
                    <dt class="flex items-center gap-x-2 text-gray-700">
                        <x-fi.icon name="document-text" class="h-4 w-4 shrink-0 text-sky-500" /> Bukti pembayaran rekap
                    </dt>
                    <dd class="font-semibold tabular-nums text-gray-950">{{ $rupiah($recapDirectPaid) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-3">
                    <dt class="text-gray-700">
                        <span class="flex items-center gap-x-2">
                            <x-fi.icon name="document-currency-dollar" class="h-4 w-4 shrink-0 text-sky-500" /> Pembayaran invoice tertaut
                        </span>
                        <span class="block ps-6 text-xs text-gray-500">Tanpa PPN agar sebanding dengan Total RAB</span>
                    </dt>
                    <dd class="text-end">
                        <span class="block font-semibold tabular-nums text-gray-950">{{ $rupiah($recapInvoicePaid) }}</span>
                        @if ($recapInvoicePpn > 0)
                            <span class="block text-xs text-gray-500">
                                dibayar {{ $rupiah($recapInvoicePaid + $recapInvoicePpn) }}
                                (PPN {{ $rupiah($recapInvoicePpn) }})
                            </span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="flex items-center gap-x-2 text-gray-700">
                        <x-fi.icon name="inbox-arrow-down" class="h-4 w-4 shrink-0 text-emerald-500" /> Uang masuk manual (Laporan Keuangan)
                    </dt>
                    <dd class="font-semibold tabular-nums text-gray-950">{{ $rupiah($recapIncomePaid) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-2">
                    <dt class="font-semibold text-gray-950">Total Terbayar</dt>
                    <dd class="font-semibold tabular-nums text-emerald-600">{{ $rupiah($recapPaid) }}</dd>
                </div>
            </dl>

            @if (auth()->user()->role === 'superadmin')
                <div class="flex items-center justify-between gap-3 rounded-xl bg-sky-50 px-4 py-2.5 text-sm ring-1 ring-sky-600/10">
                    <span class="flex items-center gap-x-2 text-sky-700">
                        <x-fi.icon name="banknotes" class="h-4 w-4 shrink-0" /> Uang Masuk (DP)
                    </span>
                    <span class="font-semibold tabular-nums text-sky-700">{{ $rupiah($recapDp) }}</span>
                </div>
            @endif

            <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-4 py-2.5 text-sm">
                <span class="text-gray-700">Sisa setelah DP dan pembayaran</span>
                <span @class(['font-semibold tabular-nums', 'text-red-600' => $recapRemaining > 0, 'text-emerald-600' => $recapRemaining <= 0])>
                    {{ $rupiah($recapRemaining) }}
                </span>
            </div>
        </div>

        {{-- Invoice Proyek Tertaut --}}
        <div class="grid gap-y-3">
            <h3 class="text-sm font-semibold text-gray-950">Invoice Proyek Tertaut</h3>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Nilai Proyek (Total RAB)</p>
                    <p class="mt-1 font-semibold tabular-nums text-gray-950">{{ $rupiah($recapTotal) }}</p>
                </div>
                <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Total Ditagih ({{ $linkedInvoices->count() }} invoice)</p>
                    <p class="mt-1 font-semibold tabular-nums text-sky-600">{{ $rupiah($recapInvoiced) }}</p>
                </div>
                <div class="rounded-xl p-3 ring-1 ring-gray-950/10">
                    <p class="text-sm text-gray-500">Sisa Belum Ditagih</p>
                    <p @class(['mt-1 font-semibold tabular-nums', 'text-red-600' => $recapUninvoiced < 0, 'text-emerald-600' => $recapUninvoiced >= 0])>
                        {{ $recapUninvoiced < 0 ? '-' : '' }}{{ $rupiah(abs($recapUninvoiced)) }}
                    </p>
                </div>
            </div>

            @if ($recapUninvoiced < 0)
                <p class="text-sm text-red-600">
                    <x-fi.icon name="exclamation-triangle" class="-mt-0.5 inline h-4 w-4" />
                    Total tagihan invoice melebihi nilai proyek sebesar {{ $rupiah(abs($recapUninvoiced)) }}.
                </p>
            @endif

            @if ($linkedInvoices->isNotEmpty())
                <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10">
                    <div class="relative overflow-x-auto">
                        <table class="w-full table-auto divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-start font-semibold text-gray-950">No Invoice</th>
                                    <th class="px-3 py-2 text-start font-semibold text-gray-950">Tanggal</th>
                                    <th class="px-3 py-2 text-start font-semibold text-gray-950">Kepada</th>
                                    <th class="px-3 py-2 text-end font-semibold text-gray-950">Nilai Tagihan</th>
                                    <th class="px-3 py-2 text-end font-semibold text-gray-950">Terbayar</th>
                                    <th class="px-3 py-2 text-end font-semibold text-gray-950">Masuk ke Rekap</th>
                                    <th class="px-3 py-2 text-start font-semibold text-gray-950">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 whitespace-nowrap text-gray-950">
                                @foreach ($linkedInvoices as $linkedInvoice)
                                    @php
                                        $linkedInvoicePayments = $invoicePaymentsByInvoice->get($linkedInvoice->invoice_number, collect());
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 font-medium">
                                            <a href="{{ route('proyek-invoice.index', ['search' => $linkedInvoice->invoice_number]) }}"
                                                class="text-primary hover:underline" title="Buka di menu Invoice">
                                                {{ $linkedInvoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-gray-700">{{ $linkedInvoice->invoice_date?->format('d M Y') }}</td>
                                        <td class="min-w-[9rem] whitespace-normal px-3 py-2">{{ $linkedInvoice->recipient }}</td>
                                        <td class="px-3 py-2 text-end font-semibold tabular-nums">{{ $rupiah($linkedInvoice->getBilledAmount()) }}</td>
                                        <td class="px-3 py-2 text-end tabular-nums text-emerald-600">{{ $rupiah($linkedInvoice->getTotalPaidAmount()) }}</td>
                                        <td class="px-3 py-2 text-end font-semibold tabular-nums text-emerald-600">
                                            {{ $rupiah((int) $linkedInvoicePayments->sum('project_amount')) }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <x-fi.badge :color="$linkedInvoice->isFullyPaid() ? 'success' : ($linkedInvoice->getTotalPaidAmount() > 0 ? 'warning' : 'danger')">
                                                {{ $linkedInvoice->payment_status_label }}
                                            </x-fi.badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-gray-200 bg-gray-50 font-semibold text-gray-950">
                                <tr>
                                    <td colspan="3" class="px-3 py-2 text-end">Total</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-end tabular-nums">{{ $rupiah($recapInvoiced) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-end tabular-nums text-emerald-600">{{ $rupiah($recapInvoicePaid + $recapInvoicePpn) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-end tabular-nums text-emerald-600">{{ $rupiah($recapInvoicePaid) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <p class="text-sm text-gray-500">
                    Nilai tagihan dihitung dari total item invoice setelah diskon (sebelum PPN). Kolom
                    <strong class="font-medium text-gray-700">Terbayar</strong> adalah nominal bukti pembayaran invoice (termasuk PPN); kolom
                    <strong class="font-medium text-gray-700">Masuk ke Rekap</strong> adalah bagian nilai proyek tanpa PPN yang dihitung ke Total Terbayar rekap
                    dan ke uang masuk Laporan Keuangan Proyek.
                </p>
            @else
                <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 px-6 py-6 text-center">
                    <p class="text-sm font-medium text-gray-950">Belum ada invoice proyek yang ditautkan</p>
                    <p class="mt-1 text-sm text-gray-500">Pilih rekap ini pada form tambah/edit Invoice Proyek.</p>
                </div>
            @endif

            @if ($unlinkedCandidates->isNotEmpty())
                {{-- Invoice lama dengan nama proyek sama yang belum ditautkan (saran, tidak dihitung) --}}
                <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-600/20">
                    <x-fi.icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold">{{ $unlinkedCandidates->count() }} invoice dengan nama proyek sama belum ditautkan</p>
                        <p class="mt-0.5">
                            Pembayaran invoice berikut <strong>belum</strong> dihitung ke rekap ini. Bila memang milik proyek ini,
                            pilih rekap ini pada form Edit Invoice Proyek.
                        </p>
                        <ul class="mt-2 grid gap-y-1">
                            @foreach ($unlinkedCandidates as $candidate)
                                <li class="flex flex-wrap items-center justify-between gap-x-3">
                                    <a href="{{ route('proyek-invoice.index', ['search' => $candidate->invoice_number]) }}"
                                        class="font-medium text-primary hover:underline" title="Buka di menu Invoice">
                                        {{ $candidate->invoice_number }}
                                    </a>
                                    <span class="whitespace-nowrap text-xs text-amber-700">
                                        Tagihan {{ $rupiah($candidate->getBilledAmount()) }}
                                        &middot; Terbayar {{ $rupiah($candidate->getTotalPaidAmount()) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        {{-- Progress Pembayaran --}}
        <div class="grid gap-y-1.5">
            <div class="flex justify-between text-sm">
                <h3 class="font-semibold text-gray-950">Progress Pembayaran</h3>
                <span @class(['font-semibold tabular-nums', 'text-emerald-600' => $recapProgress == 100, 'text-amber-600' => $recapProgress > 0 && $recapProgress < 100, 'text-red-600' => $recapProgress <= 0])>
                    {{ $recapProgress }}%
                </span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                <div @class(['h-full rounded-full transition-all duration-500 ease-in-out', 'bg-emerald-500' => $recapProgress == 100, 'bg-amber-400' => $recapProgress > 0 && $recapProgress < 100, 'bg-red-400' => $recapProgress <= 0])
                    style="width: {{ $recapProgress }}%"></div>
            </div>
            <p class="text-sm">
                @if ($isFullyPaid)
                    <span class="inline-flex items-center gap-1 font-medium text-emerald-600">
                        <x-fi.icon name="check-circle" class="h-4 w-4" /> Sudah Lunas
                    </span>
                @elseif ($recapPaid > 0 || $recapDp > 0)
                    <span class="inline-flex items-center gap-1 font-medium text-amber-600">
                        <x-fi.icon name="clock" class="h-4 w-4" /> Sebagian ({{ $recapProgress }}% terbayar)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 font-medium text-red-600">
                        <x-fi.icon name="exclamation-circle" class="h-4 w-4" /> Belum ada pembayaran
                    </span>
                @endif
            </p>
        </div>

        {{-- Pembayaran Bertahap (bukti rekap + bukti invoice tertaut, urut tanggal bayar) --}}
        <div class="grid gap-y-3">
            <h3 class="text-sm font-semibold text-gray-950">Pembayaran Bertahap</h3>

            @if ($paymentTimeline->isNotEmpty())
                <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-4 py-3 text-sm">
                    <div>
                        <p class="text-gray-500">Tahap Pembayaran</p>
                        <p class="font-semibold text-gray-950">{{ $paymentTimeline->count() }} tahap terbayar</p>
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

                <ol class="divide-y divide-gray-200 rounded-xl ring-1 ring-gray-950/10">
                    @foreach ($paymentTimeline as $proofIndex => $payment)
                        @php
                            $proof = $payment->proof;
                            $stageNumber = $proofIndex + 1;
                            // Nominal yang dihitung ke rekap (bukti invoice: tanpa PPN).
                            $proofAmount = (int) $payment->project_amount;
                            $proofDate = $proof->payment_date ?? $proof->created_at;
                            $proofDateLabel = $proofDate ? $proofDate->format('d M Y') : '-';
                            $isInstallmentPaid = $proofAmount > 0;
                            $stageLabel = ($payment->invoice ? 'Invoice ' . $payment->invoice->invoice_number . ' — ' : '')
                                . 'Pembayaran ke-' . ($proof->payment_stage ?? $stageNumber);
                        @endphp
                        <li class="flex items-start gap-3 px-3 py-3">
                            <span @class(['flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold', 'bg-emerald-500 text-white' => $isInstallmentPaid, 'bg-gray-100 text-gray-500' => ! $isInstallmentPaid])>
                                @if ($isInstallmentPaid)
                                    <x-fi.icon name="check-circle" class="h-4 w-4" />
                                @else
                                    {{ $stageNumber }}
                                @endif
                            </span>
                            <div class="grid min-w-0 flex-1 gap-y-0.5 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <p @class(['font-medium', 'text-gray-950' => $isInstallmentPaid, 'text-gray-500' => ! $isInstallmentPaid])>
                                        {{ $stageLabel }}
                                    </p>
                                    <span @class(['font-semibold tabular-nums', 'text-emerald-600' => $isInstallmentPaid, 'text-gray-400' => ! $isInstallmentPaid])>
                                        {{ $rupiah($proofAmount) }}
                                    </span>
                                </div>
                                @if ($payment->ppn_amount > 0)
                                    <p class="text-end text-xs text-gray-500">
                                        dibayar {{ $rupiah($payment->amount) }}, PPN {{ $rupiah($payment->ppn_amount) }} tidak dihitung
                                    </p>
                                @endif
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-gray-500">{{ $proofDateLabel }}</p>
                                    <x-fi.badge :color="$isInstallmentPaid ? 'success' : 'gray'">{{ $isInstallmentPaid ? 'Lunas' : 'Belum dibayar' }}</x-fi.badge>
                                </div>
                                @if (!empty($proof->file_path))
                                    {{-- Bukti dibuka di modal pratinjau (tanpa tab baru) --}}
                                    <a href="{{ asset('storage/' . $proof->file_path) }}" title="{{ $proof->file_name }}"
                                        onclick="if (window.openFilePreview) { event.preventDefault(); window.openFilePreview(this.href, { title: @js('Bukti ' . $stageLabel), downloadName: @js($proof->file_name) }); }"
                                        class="inline-flex items-center gap-1 justify-self-start font-medium text-primary hover:underline">
                                        <x-fi.icon name="paper-clip" class="h-4 w-4" /> Lihat Bukti
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 px-6 py-8 text-center">
                    <div class="mb-3 rounded-full bg-gray-100 p-3">
                        <x-fi.icon name="clock" class="h-6 w-6 text-gray-500" />
                    </div>
                    <p class="text-sm font-medium text-gray-950">Belum ada pembayaran bertahap</p>
                    <p class="mt-1 text-sm text-gray-500">Pembayaran akan muncul setelah bukti pembayaran diupload</p>
                </div>
            @endif
        </div>

        {{-- Bukti Pembayaran Rekap --}}
        <div class="grid gap-y-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-950">Bukti Pembayaran Rekap</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Bukti yang diupload langsung ke rekap ini. Pembayaran lewat invoice tertaut sudah otomatis terhitung
                    (lihat Pembayaran Bertahap) — jangan diupload ulang di rekap agar tidak terhitung dua kali.
                </p>
            </div>

            @if ($paymentProofs->isNotEmpty())
                <ul class="divide-y divide-gray-200 rounded-xl ring-1 ring-gray-950/10">
                    @foreach ($paymentProofs as $proof)
                        <li>
                            <a href="{{ asset('storage/' . $proof->file_path) }}" title="{{ $proof->file_name }}"
                                onclick="if (window.openFilePreview) { event.preventDefault(); window.openFilePreview(this.href, { title: 'Bukti Pembayaran', downloadName: @js($proof->file_name) }); }"
                                class="flex items-center justify-between gap-3 px-3 py-3 text-sm transition duration-75 hover:bg-gray-50">
                                <span class="flex min-w-0 items-center gap-2">
                                    <x-fi.icon name="document-text" class="h-5 w-5 shrink-0 text-sky-500" />
                                    <span class="truncate font-medium text-gray-950">{{ $proof->file_name }}</span>
                                </span>
                                <span class="flex shrink-0 items-center gap-3">
                                    <span class="font-semibold tabular-nums text-emerald-600">{{ $rupiah($proof->amount ?? 0) }}</span>
                                    <span class="text-gray-500">{{ optional($proof->payment_date ?? $proof->created_at)->format('d M Y') }}</span>
                                    <span class="inline-flex items-center gap-1 font-medium text-primary">
                                        <x-fi.icon name="paper-clip" class="h-4 w-4" /> Lihat
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 px-6 py-8 text-center">
                    <div class="mb-3 rounded-full bg-gray-100 p-3">
                        <x-fi.icon name="document-text" class="h-6 w-6 text-gray-500" />
                    </div>
                    <p class="text-sm font-medium text-gray-950">Belum ada bukti pembayaran</p>
                    <p class="mt-1 text-sm text-gray-500">Upload melalui menu Bukti Pembayaran (kategori Rekap Proyek).</p>
                </div>
            @endif
        </div>

        {{-- Uang Masuk (Laporan Keuangan Proyek) --}}
        <div class="grid gap-y-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-950">Uang Masuk (Laporan Keuangan)</h3>
                <p class="mt-1 text-sm text-gray-500">Pembayaran dari transaksi kategori pemasukan yang diinput manual pada Laporan Keuangan Proyek (baris otomatis dari bukti pembayaran tidak dihitung dua kali).</p>
            </div>

            @if ($incomePayments->isNotEmpty())
                <ul class="divide-y divide-gray-200 rounded-xl ring-1 ring-gray-950/10">
                    @foreach ($incomePayments as $income)
                        @php
                            $incomeDate = $income->transaction_date ?? $income->created_at;
                        @endphp
                        <li class="flex items-center justify-between gap-3 px-3 py-3 text-sm">
                            <span class="flex min-w-0 items-center gap-2">
                                <x-fi.icon name="inbox-arrow-down" class="h-5 w-5 shrink-0 text-emerald-500" />
                                <span class="min-w-0">
                                    <span class="block truncate font-medium text-gray-950">{{ $income->category?->name ?? 'Uang Masuk' }}</span>
                                    @if (!empty($income->description))
                                        <span class="block truncate text-xs text-gray-500">{{ $income->description }}</span>
                                    @endif
                                </span>
                            </span>
                            <span class="flex shrink-0 items-center gap-3">
                                <span class="font-semibold tabular-nums text-emerald-600">{{ $rupiah($income->income_amount ?? 0) }}</span>
                                <span class="text-gray-500">{{ optional($incomeDate)->format('d M Y') }}</span>
                                @if (!empty($income->proof_file))
                                    <a href="{{ asset('storage/' . $income->proof_file) }}" title="{{ $income->proof_file_name }}"
                                        onclick="if (window.openFilePreview) { event.preventDefault(); window.openFilePreview(this.href, { title: 'Bukti Uang Masuk', downloadName: @js($income->proof_file_name ?: basename($income->proof_file)) }); }"
                                        class="inline-flex items-center gap-1 font-medium text-primary hover:underline">
                                        <x-fi.icon name="paper-clip" class="h-4 w-4" /> Lihat
                                    </a>
                                @else
                                    <span class="text-xs italic text-gray-400">Tanpa bukti</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 px-6 py-8 text-center">
                    <div class="mb-3 rounded-full bg-gray-100 p-3">
                        <x-fi.icon name="inbox-arrow-down" class="h-6 w-6 text-gray-500" />
                    </div>
                    <p class="text-sm font-medium text-gray-950">Belum ada transaksi uang masuk</p>
                    <p class="mt-1 text-sm text-gray-500">Transaksi kategori pemasukan akan muncul di sini.</p>
                </div>
            @endif
        </div>

        {{-- File Design --}}
        <div class="grid gap-y-3">
            <h3 class="text-sm font-semibold text-gray-950">File Design</h3>
            @if ($recap->hasDesignFile())
                <a href="{{ asset('storage/' . $recap->design_file) }}"
                    onclick="if (window.openFilePreview) { event.preventDefault(); window.openFilePreview(this.href, { title: 'File Design', downloadName: @js($recap->design_file_name ?: basename($recap->design_file)) }); }"
                    class="flex items-center gap-x-3 rounded-xl p-3 text-sm ring-1 ring-gray-950/10 transition duration-75 hover:bg-gray-50">
                    <x-fi.icon name="photo" class="h-5 w-5 shrink-0 text-primary" />
                    <span class="min-w-0 truncate font-medium text-primary">{{ $recap->design_file_name }}</span>
                </a>
            @else
                <p class="text-sm italic text-gray-400">Tidak ada file design.</p>
            @endif
        </div>
    </div>
</x-fi.modal>
