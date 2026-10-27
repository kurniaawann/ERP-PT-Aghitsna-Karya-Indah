{{-- =====================================================================
     Modal "Buat Invoice Semen" — satu per baris DO Semen (superadmin).
     Menampilkan daftar Data Semen milik DO dengan checkbox; data semen yang
     sudah pernah masuk invoice lain ditandai & dinonaktifkan.
     Submit → POST /do-semen/{no}/generate-invoice
     Hook JS (cement-do/index.js): .gen-inv-cement, .gen-select-all,
     .gen-inv-total, form & button[type=submit] di dalam modal.
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

<x-fi.modal id="generateInvoiceModal-{{ $no }}" heading="Buat Invoice Semen" :description="'No. DO: ' . $no"
    icon="document-plus" color="success" :action="route('cement-do.generateInvoice', $no)" method="POST"
    submitLabel="Buat Invoice" width="6xl">

    {{-- Info DO (read only) --}}
    <div class="grid grid-cols-2 divide-x divide-gray-200 rounded-xl bg-gray-50 ring-1 ring-gray-950/5">
        <div class="px-4 py-3">
            <div class="text-sm text-gray-500">Tanggal DO</div>
            <div class="mt-0.5 font-semibold text-gray-950">{{ $cementDeliveryOrder->tanggal?->format('d M Y') ?: '-' }}</div>
        </div>
        <div class="px-4 py-3 text-end">
            <div class="text-sm text-gray-500">Subtotal DO</div>
            <div class="mt-0.5 font-semibold tabular-nums text-gray-950">Rp {{ number_format($cementDeliveryOrder->subtotal, 0, ',', '.') }}</div>
        </div>
    </div>

    @if ($invoicedTotal > 0)
        <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-amber-600/20">
            <x-fi.icon name="exclamation-triangle" class="h-5 w-5 text-amber-500" />
            <p>
                Sebagian data semen ({{ number_format($invoicedTotal, 0, ',', '.') }}) sudah pernah dibuat invoice lain &
                otomatis dilewati. Centang data semen yang tersisa untuk diinvois.
            </p>
        </div>
    @endif

    {{-- Field Invoice --}}
    <div class="grid grid-cols-1 gap-x-4 gap-y-5 sm:grid-cols-3">
        <x-fi.field label="Tanggal Invoice" for="gen-invoice-date-{{ $no }}" required>
            <x-fi.input type="date" id="gen-invoice-date-{{ $no }}" name="invoice_date" :value="date('Y-m-d')" required
                oninvalid="this.setCustomValidity('Tanggal invoice tidak boleh kosong')"
                oninput="this.setCustomValidity('')" />
        </x-fi.field>

        {{-- Rekening wajib dipilih (default: rekening pertama) agar No. Rek selalu tercetak di invoice --}}
        <x-fi.field label="Rekening Pembayaran" for="gen-payment-account-{{ $no }}" required>
            <div class="flex items-center gap-2">
                <x-fi.select id="gen-payment-account-{{ $no }}" name="payment_account_id" prefix-icon="credit-card"
                    placeholder="-- Pilih Rekening --" required
                    oninvalid="this.setCustomValidity('Pilih rekening pembayaran (tambah lewat tombol Rekening Baru bila belum ada)')"
                    onchange="this.setCustomValidity('')">
                    @foreach ($paymentAccounts as $account)
                        <option value="{{ $account->id }}" @selected($loop->first)>{{ $account->bank_name }} - {{ $account->account_number }}</option>
                    @endforeach
                </x-fi.select>
                <x-finance.quick-add-payment-account mode="select"
                    target-selector="select[name='payment_account_id']" id-suffix="{{ $no }}" />
            </div>
        </x-fi.field>

        <x-fi.field label="Nama Tanda Tangan" for="gen-signed-by-{{ $no }}">
            <x-fi.select id="gen-signed-by-{{ $no }}" name="signed_by_id" prefix-icon="user"
                placeholder="-- Pilih Nama Tanda Tangan --">
                @foreach ($executives as $executive)
                    <option value="{{ $executive->id }}">{{ $executive->name }}
                        @if ($executive->position)
                            ({{ $executive->position }})
                        @endif
                    </option>
                @endforeach
            </x-fi.select>
        </x-fi.field>
    </div>

    <x-fi.field label="Note" for="gen-note-{{ $no }}">
        <x-fi.textarea id="gen-note-{{ $no }}" name="note" rows="2" placeholder="Catatan tambahan (opsional)" />
    </x-fi.field>

    {{-- Daftar Data Semen -> checkbox --}}
    <div class="grid gap-y-2">
        <div class="flex items-center justify-between gap-3">
            <span class="text-sm font-medium leading-6 text-gray-950">
                Pilih Data Semen untuk Diinvois<sup class="ms-0.5 font-medium text-red-600">*</sup>
            </span>
            <x-fi.button color="gray" size="sm" icon="check-circle" class="gen-select-all" data-no="{{ $no }}">
                Centang Semua
            </x-fi.button>
        </div>

        <div class="max-h-[320px] overflow-auto rounded-xl shadow-sm ring-1 ring-gray-950/5">
            <table class="w-full table-auto divide-y divide-gray-200">
                <thead class="sticky top-0 z-10 bg-gray-50">
                    <tr>
                        <th class="w-10 px-3 py-2"><span class="sr-only">Pilih</span></th>
                        <th class="px-3 py-2 text-start text-sm font-semibold text-gray-950">No</th>
                        <th class="px-3 py-2 text-start text-sm font-semibold text-gray-950">Tanggal</th>
                        <th class="px-3 py-2 text-start text-sm font-semibold text-gray-950">Proyek</th>
                        <th class="px-3 py-2 text-center text-sm font-semibold text-gray-950">Jumlah</th>
                        <th class="px-3 py-2 text-end text-sm font-semibold text-gray-950">Harga</th>
                        <th class="px-3 py-2 text-end text-sm font-semibold text-gray-950">Total</th>
                        <th class="px-3 py-2 text-center text-sm font-semibold text-gray-950">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap bg-white text-sm text-gray-950">
                    @forelse ($cementDeliveryOrder->cements as $cement)
                        @php
                            $alreadyInvoiced = !empty($cement->invoice_number);
                        @endphp
                        <tr class="{{ $alreadyInvoiced ? 'bg-gray-50 opacity-70' : 'has-[:checked]:bg-primary-light/30' }} gen-cement-row"
                            data-no="{{ $no }}">
                            <td class="px-3 py-2.5 text-center">
                                @if ($alreadyInvoiced)
                                    <input type="checkbox" disabled class="fi-checkbox cursor-not-allowed opacity-50">
                                @else
                                    <input type="checkbox" name="cement_nos[]" value="{{ $cement->no }}"
                                        data-total="{{ $cement->total }}" checked
                                        class="gen-inv-cement fi-checkbox">
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-gray-500">{{ $cement->no }}</td>
                            <td class="px-3 py-2.5">{{ $cement->tanggal?->format('d M Y') ?: '-' }}</td>
                            <td class="px-3 py-2.5">
                                <div>{{ $cement->nama_proyek }}</div>
                                @if ($cement->name)
                                    <div class="text-gray-500">{{ $cement->name }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center tabular-nums">{{ number_format($cement->jumlah, 0, ',', '.') }} {{ $cement->satuan ?: 'zak' }}</td>
                            <td class="px-3 py-2.5 text-end tabular-nums text-gray-700">{{ 'Rp ' . number_format($cement->harga, 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-end font-medium tabular-nums">{{ 'Rp ' . number_format($cement->total, 0, ',', '.') }}</td>
                            <td class="px-3 py-2.5 text-center">
                                @if ($alreadyInvoiced)
                                    <x-fi.badge color="danger" title="Masuk invoice {{ $cement->invoice_number }}">Diinvois</x-fi.badge>
                                @else
                                    <x-fi.badge color="success">Siap diinvois</x-fi.badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-sm text-gray-500">
                                Tidak ada data semen dalam DO ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Preview Total --}}
    <div class="flex items-center justify-between gap-3 rounded-xl bg-primary-light/40 px-4 py-3 ring-1 ring-primary/20">
        <span class="text-sm font-medium text-gray-700">Total Yang Akan Diinvois</span>
        <span class="gen-inv-total text-2xl font-bold tabular-nums text-primary">Rp 0</span>
    </div>
</x-fi.modal>
