{{-- Section: Payment Proof Table --}}
<form id="deleteForm" method="POST" action="{{ route('payment-proofs.destroySelected') }}">
    @csrf
    @method('DELETE')
    <div class="overflow-x-auto -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
        <div class="inline-block min-w-full align-middle">
            <div class="border-2 border-border-strong rounded-xl overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-border-light">
                    {{-- Section: Table Header --}}
                    <thead class="bg-surface-secondary">
                        <tr>
                            <th class="p-3 text-center text-xs font-semibold uppercase tracking-wide text-text-label">
                                <input type="checkbox" id="selectAll" class="w-4 h-4 accent-primary cursor-pointer">
                            </th>
                            <th class="p-3 text-left text-xs font-semibold uppercase tracking-wide text-text-label">
                                Tanggal</th>
                            <th class="p-3 text-left text-xs font-semibold uppercase tracking-wide text-text-label">
                                Invoice</th>
                            <th class="p-3 text-left text-xs font-semibold uppercase tracking-wide text-text-label">
                                Kategori</th>
                            <th class="p-3 text-left text-xs font-semibold uppercase tracking-wide text-text-label">
                                Tahap</th>
                            <th class="p-3 text-right text-xs font-semibold uppercase tracking-wide text-text-label">
                                Nominal</th>
                            <th class="p-3 text-left text-xs font-semibold uppercase tracking-wide text-text-label">File
                            </th>
                            <th class="p-3 text-center text-xs font-semibold uppercase tracking-wide text-text-label">
                                Aksi</th>
                        </tr>
                    </thead>
                    {{-- Section: Table Body --}}
                    <tbody>
                        @forelse($paymentProofs as $paymentProof)
                            @php
                                $lookup = data_get(
                                    $invoiceLookup,
                                    $paymentProof->module_type .
                                        '.' .
                                        $paymentProof->invoice_type .
                                        '.' .
                                        $paymentProof->invoice_number,
                                    [],
                                );
                                $invoiceLabel = $lookup['label'] ?? $paymentProof->invoice_number;
                            @endphp
                            <tr class="border-t hover:bg-surface-secondary/70 transition-colors">
                                <td class="p-3 text-center">
                                    <input type="checkbox" name="selected_items[]" value="{{ $paymentProof->id }}"
                                        class="w-4 h-4 accent-primary cursor-pointer">
                                </td>
                                <td class="p-3 text-sm whitespace-nowrap">
                                    {{ $paymentProof->created_at?->format('d-m-Y H:i') }}</td>
                                <td class="p-3 text-sm font-medium text-primary">{{ $invoiceLabel }}</td>
                                <td class="p-3 text-sm">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="inline-flex w-fit items-center rounded-md px-2.5 py-0.5 text-xs font-semibold {{ $paymentProof->invoice_type === 'proyek' ? 'bg-primary-light text-primary' : ($paymentProof->invoice_type === 'alumunium' ? 'bg-warning-light text-warning' : 'bg-secondary-light text-secondary') }}">
                                            {{ $paymentProof->invoice_type === 'proyek' ? (auth()->user()->isAdmin() ? 'Invoice' : 'Invoice Proyek') : ($paymentProof->invoice_type === 'alumunium' ? 'Invoice Alumunium' : 'Rekap Penjualan') }}
                                        </span>
                                        <span class="text-xs text-text-label">
                                            {{ $paymentProof->module_type === 'finance' ? 'Keuangan' : ucfirst($paymentProof->module_type) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3 text-sm">
                                    @if ($paymentProof->invoice_type === 'proyek')
                                        <span
                                            class="inline-flex w-fit items-center rounded-md bg-success-light px-2.5 py-0.5 text-xs font-semibold text-success">
                                            Pembayaran ke {{ $paymentProof->payment_stage ?? '-' }}
                                        </span>
                                    @else
                                        <span class="text-text-label">Tidak ada tahap</span>
                                    @endif
                                </td>
                                <td class="p-3 text-sm text-right font-medium text-primary whitespace-nowrap">
                                    Rp {{ number_format($paymentProof->amount ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-sm">
                                    <a href="{{ asset($paymentProof->file_path) }}" target="_blank"
                                        class="inline-flex items-center gap-1 text-primary hover:underline">
                                        <i class="fa-solid fa-image text-xs"></i>
                                        {{ $paymentProof->file_name }}
                                    </a>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="flex justify-center gap-1 flex-wrap">
                                        <button type="button" onclick="openModal('editModal-{{ $paymentProof->id }}')"
                                            class="flex items-center gap-1 rounded-lg bg-btn-edit px-2.5 py-1.5 text-xs text-white transition hover:bg-btn-edit-hover">
                                            <i class="fa-solid fa-pen w-3 h-3"></i>
                                            Edit
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center p-4 text-text-secondary">Data bukti pembayaran
                                    tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>
