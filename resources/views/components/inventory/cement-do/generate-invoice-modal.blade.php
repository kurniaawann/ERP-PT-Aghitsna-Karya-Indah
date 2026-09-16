{{-- =====================================================================
     Modal "Buat Invoice Semen" — satu per baris DO Semen (superadmin).
     Menampilkan daftar Data Semen milik DO dengan checkbox; data semen yang
     sudah pernah masuk invoice lain ditandai & dinonaktifkan.
     Submit → POST /do-semen/{no}/generate-invoice
     ===================================================================== --}}
@php
    $no = $cementDeliveryOrder->no;
    $invoicedTotal = $cementDeliveryOrder->cements
        ->filter(fn ($c) => !empty($c->invoice_number))
        ->sum(fn ($c) => $c->total);
    $pendingCements = $cementDeliveryOrder->cements
        ->filter(fn ($c) => empty($c->invoice_number))
        ->values();
@endphp

<x-modal id="generateInvoiceModal-{{ $no }}"
    title="Buat Invoice Semen — {{ $no }}"
    action="{{ route('cement-do.generateInvoice', $no) }}"
    method="POST" buttonText="Buat Invoice" size="6xl">

    {{-- Info DO (read only) --}}
    <div class="flex items-center justify-between border-b pb-3 mb-3 bg-surface-secondary rounded-lg p-3">
        <div>
            <span class="text-xs text-text-secondary">Tanggal DO</span>
            <div class="font-semibold text-text-primary">{{ $cementDeliveryOrder->tanggal?->format('d M Y') ?: '-' }}</div>
        </div>
        <div class="text-right">
            <span class="text-xs text-text-secondary">Subtotal DO</span>
            <div class="font-semibold text-text-primary">Rp {{ number_format($cementDeliveryOrder->subtotal, 0, ',', '.') }}</div>
        </div>
    </div>

    @if ($invoicedTotal > 0)
        <div class="mb-3">
            <span class="text-xs text-error">Sebagian data semen ({{
                number_format($invoicedTotal, 0, ',', '.') }}) sudah pernah dibuat invoice lain &
                otomatis dilewati. Centang data semen yang tersisa untuk diinvois.</span>
        </div>
    @endif

    {{-- Field Invoice --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
        <div>
            <label class="block text-text-primary mb-1">Tanggal Invoice <span class="text-error">*</span></label>
            <input type="date" name="invoice_date" class="w-full border rounded p-2" required
                value="{{ date('Y-m-d') }}"
                oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
                oninput="this.setCustomValidity('')">
        </div>

        <div>
            <label class="block text-text-primary mb-1">Rekening Pembayaran</label>
            <div class="flex items-center gap-2">
                <select name="payment_account_id" class="flex-1 w-full border rounded p-2">
                    <option value="">-- Pilih Rekening --</option>
                    @foreach ($paymentAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->bank_name }} - {{ $account->account_number }}</option>
                    @endforeach
                </select>
                <x-finance.quick-add-payment-account mode="select"
                    target-selector="select[name='payment_account_id']" id-suffix="{{ $no }}" />
            </div>
        </div>

        <div>
            <label class="block text-text-primary mb-1">Nama Tanda Tangan</label>
            <select name="signed_by_id" class="w-full border rounded p-2">
                <option value="">-- Pilih Nama Tanda Tangan --</option>
                @foreach ($executives as $executive)
                    <option value="{{ $executive->id }}">{{ $executive->name }}
                        @if ($executive->position)
                            ({{ $executive->position }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="block text-text-primary mb-1">Note</label>
        <textarea name="note" rows="2" class="w-full border rounded p-2"
            placeholder="Catatan tambahan (opsional)"></textarea>
    </div>

    {{-- Daftar Data Semen -> checkbox --}}
    <div class="mb-3">
        <div class="flex items-center justify-between mb-2">
            <label class="block text-text-primary font-semibold">
                Pilih Data Semen untuk Diinvois <span class="text-error">*</span>
            </label>
            <button type="button"
                class="flex items-center gap-1 bg-btn-add hover:bg-btn-add-hover text-white px-3 py-1 rounded-lg transition-colors duration-200 text-xs gen-select-all"
                data-no="{{ $no }}">
                <i class="fa-solid fa-check-double w-3 h-3"></i> Centang Semua
            </button>
        </div>

        <div class="border rounded-lg overflow-auto max-h-[320px]">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr class="text-xs text-text-secondary">
                        <th class="p-2 text-center w-10"></th>
                        <th class="p-2 text-left">No</th>
                        <th class="p-2 text-left">Tanggal</th>
                        <th class="p-2 text-left">Proyek</th>
                        <th class="p-2 text-center">Jumlah</th>
                        <th class="p-2 text-right">Harga</th>
                        <th class="p-2 text-right">Total</th>
                        <th class="p-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($cementDeliveryOrder->cements as $cement)
                        @php
                            $alreadyInvoiced = !empty($cement->invoice_number);
                        @endphp
                        <tr class="{{ $alreadyInvoiced ? 'bg-surface-secondary opacity-70' : 'bg-white' }} gen-cement-row"
                            data-no="{{ $no }}">
                            <td class="p-2 text-center">
                                @if ($alreadyInvoiced)
                                    <input type="checkbox" disabled class="w-4 h-4 accent-primary">
                                @else
                                    <input type="checkbox" name="cement_nos[]" value="{{ $cement->no }}"
                                        data-total="{{ $cement->total }}" checked
                                        class="gen-inv-cement w-4 h-4 accent-primary cursor-pointer">
                                @endif
                            </td>
                            <td class="p-2 text-xs text-text-secondary">{{ $cement->no }}</td>
                            <td class="p-2">{{ $cement->tanggal?->format('d M Y') ?: '-' }}</td>
                            <td class="p-2">
                                {{ $cement->nama_proyek }}
                                @if ($cement->name)
                                    <div class="text-xs text-text-secondary">{{ $cement->name }}</div>
                                @endif
                            </td>
                            <td class="p-2 text-center">{{ number_format($cement->jumlah, 0, ',', '.') }} {{ $cement->satuan ?: 'zak' }}</td>
                            <td class="p-2 text-right">{{ 'Rp ' . number_format($cement->harga, 0, ',', '.') }}</td>
                            <td class="p-2 text-right font-medium">{{ 'Rp ' . number_format($cement->total, 0, ',', '.') }}</td>
                            <td class="p-2 text-center">
                                @if ($alreadyInvoiced)
                                    <span class="inline-block text-[10px] bg-error/10 text-error px-2 py-0.5 rounded-full"
                                        title="Masuk invoice {{ $cement->invoice_number }}">
                                        Diinvois
                                    </span>
                                @else
                                    <span class="inline-block text-[10px] bg-success/10 text-success px-2 py-0.5 rounded-full">
                                        Siap diinvois
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-4 text-center text-text-secondary text-sm">
                                Tidak ada data semen dalam DO ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Preview Total --}}
    <div class="p-4 bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border-2 border-primary/20">
        <div class="flex justify-between items-center">
            <span class="text-text-primary font-semibold">Total Yang Akan Diinvois:</span>
            <span class="gen-inv-total text-2xl font-bold text-primary">Rp 0</span>
        </div>
    </div>

</x-modal>