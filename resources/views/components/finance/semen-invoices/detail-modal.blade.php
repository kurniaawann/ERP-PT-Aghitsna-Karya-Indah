{{-- Modal Detail Invoice Semen --}}
<x-modal id="detailModal-{{ $invoice->invoice_number }}" title="Detail Invoice Semen" :hideFooter="true"
    size="6xl">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">No Invoice</label>
            <p class="text-gray-900 font-medium">{{ $invoice->invoice_number }}</p>
        </div>
        <div>
            <label class="block text-sm font-semibold text-text-primary mb-1">Tanggal Invoice</label>
            <p class="text-gray-900">{{ $invoice->invoice_date->format('d F Y') }}</p>
        </div>
    </div>

    @php
        $projects = is_string($invoice->projects) ? json_decode($invoice->projects, true) : $invoice->projects;
        $grandTotal = 0;
    @endphp

    @foreach ($projects as $project)
        <div class="mb-4 p-3 border rounded bg-surface-secondary">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                <div>
                    <label class="block text-xs text-text-label mb-0.5">Nama Proyek</label>
                    <p class="text-gray-900 font-semibold">
                        {{ $project['nama_proyek'] ?? '-' }}
                        @if (!empty($project['pengurus_proyek']))
                            ({{ $project['pengurus_proyek'] }})
                        @endif
                    </p>
                </div>
                <div>
                    <label class="block text-xs text-text-label mb-0.5">Rekening Pembayaran</label>
                    @php
                        $account = \App\Models\Finance\PaymentAccount::find($project['payment_account_id'] ?? null);
                    @endphp
                    <p class="text-gray-900">
                        {{ $account ? $account->bank_name . ' - ' . $account->account_number : '-' }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-border-strong">
                    <thead class="bg-surface-hover">
                        <tr>
                            <th class="border border-border-strong px-2 py-2 text-center text-sm w-10">No</th>
                            <th class="border border-border-strong px-2 py-2 text-left text-sm">No Data</th>
                            <th class="border border-border-strong px-2 py-2 text-left text-sm">Tanggal</th>
                            <th class="border border-border-strong px-2 py-2 text-left text-sm">Nama Barang</th>
                            <th class="border border-border-strong px-2 py-2 text-center text-sm">QTY</th>
                            <th class="border border-border-strong px-2 py-2 text-right text-sm">Harga</th>
                            <th class="border border-border-strong px-2 py-2 text-right text-sm">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $subtotal = 0;
                            $items = $project['items'] ?? [];
                        @endphp
                        @foreach ($items as $item)
                            @php
                                $jumlah = (int) ($item['jumlah'] ?? 0);
                                $harga = (int) ($item['harga'] ?? 0);
                                $subtotal += $jumlah;
                                $grandTotal += $jumlah;
                            @endphp
                            <tr>
                                <td class="border border-border-strong px-2 py-2 text-center text-sm">
                                    {{ $item['no'] ?? $loop->iteration }}</td>
                                <td class="border border-border-strong px-2 py-2 text-sm">
                                    {{ $item['data_no'] ?: '-' }}</td>
                                <td class="border border-border-strong px-2 py-2 text-sm">
                                    {{ $item['tanggal'] ? \Carbon\Carbon::parse($item['tanggal'])->format('d-m-Y') : '-' }}</td>
                                <td class="border border-border-strong px-2 py-2 text-sm">
                                    {{ $item['nama_barang'] ?? 'SEMEN' }}</td>
                                <td class="border border-border-strong px-2 py-2 text-center text-sm">
                                    {{ $item['qty'] ?? 0 }}</td>
                                <td class="border border-border-strong px-2 py-2 text-right text-sm">Rp
                                    {{ number_format($harga, 0, ',', '.') }}</td>
                                <td class="border border-border-strong px-2 py-2 text-right text-sm font-semibold">Rp
                                    {{ number_format($jumlah, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-primary/5 font-bold">
                            <td colspan="6" class="border border-border-strong px-2 py-2 text-right text-sm">
                                Subtotal {{ $project['nama_proyek'] ?? '' }}</td>
                            <td class="border border-border-strong px-2 py-2 text-right text-sm text-primary">Rp
                                {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    <div class="mb-4 p-4 bg-gradient-to-r from-primary/10 to-primary/5 rounded-lg border-2 border-primary/20">
        <div class="flex justify-between items-center">
            <span class="text-text-primary font-semibold">Total Invoice:</span>
            <span class="text-2xl font-bold text-primary">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
        </div>
    </div>
</x-modal>