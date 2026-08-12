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
@endphp

<x-modal id="detailModal-{{ $recap->id }}" title="Detail Rekap Proyek" :hideFooter="true" size="4xl">

    {{-- Card A: Informasi Rekap --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Informasi Rekap</h3>
            @if ($isFullyPaid)
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-green-100 text-green-800">
                    Sudah Lunas
                </span>
            @else
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800">
                    Belum Lunas
                </span>
            @endif
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
            <div>
                <p class="text-xs text-gray-400 mb-0.5">No Rekap</p>
                <p class="font-semibold text-gray-900">{{ $recap->id }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">No RAB</p>
                <p class="text-gray-900">{{ $recap->rab_number ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Nama Proyek</p>
                <p class="font-medium text-gray-900">{{ $recap->project_name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 mb-0.5">Dibuat Oleh</p>
                <p class="text-gray-900">{{ $recap->creator?->name ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Card B: Ringkasan Finansial --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Ringkasan Finansial</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-400 mb-1">Total RAB</p>
                <p class="text-lg font-bold text-gray-900">Rp {{ number_format($recapTotal, 0, ',', '.') }}</p>
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-400 mb-1">Total Terbayar</p>
                @if ($recapPaid > 0)
                    <p class="text-lg font-bold text-green-600">Rp {{ number_format($recapPaid, 0, ',', '.') }}</p>
                @else
                    <p class="text-sm text-gray-400 italic">Belum ada pembayaran</p>
                @endif
            </div>

            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                <p class="text-xs text-gray-400 mb-1">Sisa Pembayaran</p>
                @if ($recapRemaining > 0)
                    <p class="text-lg font-bold text-red-600">Rp {{ number_format($recapRemaining, 0, ',', '.') }}</p>
                @else
                    <p class="text-lg font-bold text-green-600">Rp 0</p>
                @endif
            </div>
        </div>

        @if (auth()->user()->role === 'superadmin')
            <div class="flex justify-between items-center py-1.5 px-3 bg-blue-50 rounded-lg">
                <span class="text-sm text-blue-700">
                    <i class="fa-solid fa-hand-holding-dollar mr-1"></i>Uang Masuk (DP)
                </span>
                <span class="text-sm font-semibold text-blue-600">Rp {{ number_format($recapDp, 0, ',', '.') }}</span>
            </div>
        @endif

        <div class="flex justify-between items-center py-1.5 px-3 bg-gray-50 rounded-lg mt-2">
            <span class="text-sm text-gray-600">Sisa setelah DP dan pembayaran</span>
            <span class="text-sm font-semibold {{ $recapRemaining > 0 ? 'text-red-600' : 'text-green-600' }}">
                Rp {{ number_format($recapRemaining, 0, ',', '.') }}
            </span>
        </div>
    </div>

    {{-- Card C: Progress Bar --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Progress Pembayaran</h3>

        <div class="flex justify-between text-xs text-gray-500 mb-1.5">
            <span>Progress Pembayaran</span>
            <span class="font-semibold
                {{ $recapProgress == 100 ? 'text-green-600' : ($recapProgress > 0 ? 'text-orange-500' : 'text-red-500') }}">
                {{ $recapProgress }}%
            </span>
        </div>
        <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500 ease-in-out
                {{ $recapProgress == 100 ? 'bg-green-500' : ($recapProgress > 0 ? 'bg-orange-400' : 'bg-red-400') }}"
                style="width: {{ $recapProgress }}%">
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-1">
            @if ($isFullyPaid)
                <span class="text-green-600 font-medium"><i class="fa-solid fa-circle-check mr-0.5"></i>Sudah Lunas</span>
            @elseif ($recapPaid > 0 || $recapDp > 0)
                <span class="text-orange-500 font-medium"><i class="fa-solid fa-circle-half-stroke mr-0.5"></i>Sebagian ({{ $recapProgress }}% terbayar)</span>
            @else
                <span class="text-red-500 font-medium"><i class="fa-solid fa-circle mr-0.5"></i>Belum ada pembayaran</span>
            @endif
        </p>
    </div>

    {{-- Card D: Pembayaran Bertahap --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Pembayaran Bertahap</h3>

        @if ($paymentProofs->isNotEmpty())
            @php
                $sortedProofs = $paymentProofs
                    ->sortBy(fn ($p) => sprintf('%06d', (int) ($p->payment_stage ?? 999999)))
                    ->values();
            @endphp

            <div class="flex items-center gap-3 mb-4 p-3 bg-gray-50 rounded-lg border border-gray-100">
                <div class="flex-1">
                    <p class="text-xs text-gray-500 mb-1">Tahap Pembayaran</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $sortedProofs->count() }} tahap terbayar</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 mb-1">Status</p>
                    @if ($isFullyPaid)
                        <span class="text-xs font-semibold text-green-600"><i class="fa-solid fa-circle-check mr-0.5"></i>Semua lunas</span>
                    @else
                        <span class="text-xs font-semibold text-orange-500"><i class="fa-solid fa-clock mr-0.5"></i>Belum selesai</span>
                    @endif
                </div>
            </div>

            <div class="relative">
                <div class="absolute left-[15px] top-2 bottom-2 w-0.5 bg-gray-200"></div>

                <div class="space-y-0">
                    @foreach ($sortedProofs as $proofIndex => $proof)
                        @php
                            $stageNumber = $proofIndex + 1;
                            $isLast = $proofIndex === $sortedProofs->count() - 1;
                            $proofAmount = (int) ($proof->amount ?? 0);
                            $proofDate = $proof->payment_date ?? $proof->created_at;
                            $proofDateLabel = $proofDate ? $proofDate->format('d M Y') : '-';
                            $isInstallmentPaid = $proofAmount > 0;
                        @endphp
                        <div class="relative flex items-start gap-4 pb-6">
                            <div class="relative z-10 flex-shrink-0 w-[30px] h-[30px] rounded-full flex items-center justify-center text-xs font-bold
                                {{ $isInstallmentPaid ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                                @if ($isInstallmentPaid)
                                    <i class="fa-solid fa-check text-xs"></i>
                                @else
                                    {{ $stageNumber }}
                                @endif
                            </div>
                            <div class="flex-1 min-w-0 pt-0.5">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold {{ $isInstallmentPaid ? 'text-gray-900' : 'text-gray-500' }}">
                                        Pembayaran ke-{{ $proof->payment_stage ?? $stageNumber }}
                                    </p>
                                    <span class="text-sm font-semibold {{ $isInstallmentPaid ? 'text-green-600' : 'text-gray-400' }}">
                                        Rp {{ number_format($proofAmount, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-0.5">
                                    <p class="text-xs text-gray-400">{{ $proofDateLabel }}</p>
                                    @if ($isInstallmentPaid)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700">
                                            Lunas
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-400">
                                            Belum dibayar
                                        </span>
                                    @endif
                                </div>
                                @if (!empty($proof->file_path))
                                    <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank"
                                        rel="noopener noreferrer" title="{{ $proof->file_name }}"
                                        class="inline-flex items-center gap-1 mt-1 text-xs text-blue-600 hover:underline">
                                        <i class="fa-solid fa-paperclip"></i> Lihat Bukti
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
                    <i class="fa-solid fa-clock text-gray-300 text-lg"></i>
                </div>
                <p class="text-sm text-gray-400 font-medium">Belum ada pembayaran bertahap</p>
                <p class="text-xs text-gray-300 mt-1">Pembayaran akan muncul setelah bukti pembayaran diupload</p>
            </div>
        @endif
    </div>

    {{-- Card E: Bukti Pembayaran --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">Bukti Pembayaran</h3>

        @if ($paymentProofs->isNotEmpty())
            <div class="space-y-2">
                @foreach ($paymentProofs as $proof)
                    <a href="{{ asset('storage/' . $proof->file_path) }}" target="_blank" rel="noopener noreferrer"
                        title="{{ $proof->file_name }}"
                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3 hover:border-blue-300 hover:bg-blue-50">
                        <span class="flex items-center gap-2 min-w-0">
                            <i class="fa-solid fa-file-invoice text-blue-500"></i>
                            <span class="truncate text-sm font-medium text-gray-900">{{ $proof->file_name }}</span>
                        </span>
                        <span class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-sm font-semibold text-green-600">
                                Rp {{ number_format($proof->amount ?? 0, 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ optional($proof->payment_date ?? $proof->created_at)->format('d M Y') }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">
                                <i class="fa-solid fa-paperclip"></i> Lihat
                            </span>
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 mb-3">
                    <i class="fa-solid fa-file-invoice text-gray-300 text-lg"></i>
                </div>
                <p class="text-sm text-gray-400 font-medium">Belum ada bukti pembayaran</p>
                <p class="text-xs text-gray-300 mt-1">Upload melalui menu Bukti Pembayaran (kategori Rekap Proyek).</p>
            </div>
        @endif
    </div>

    {{-- Card F: File Design --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 mb-4 shadow-sm">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-3">File Design</h3>
        @if ($recap->hasDesignFile())
            <a href="{{ asset('storage/' . $recap->design_file) }}" target="_blank" rel="noopener noreferrer"
                class="inline-flex items-center gap-2 text-blue-600 hover:underline">
                <i class="fa-solid fa-image"></i>
                <span>{{ $recap->design_file_name }}</span>
            </a>
        @else
            <p class="text-sm text-gray-400 italic">Tidak ada file design.</p>
        @endif
    </div>

</x-modal>
