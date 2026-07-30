{{--
    Component Modal Detail RAB
    Menampilkan informasi detail RAB dalam modal popup.
    Menggunakan relasi yang sudah di-eager load untuk menghindari N+1 query.

    Data:
    - $rab: Instance RAB
    - $paymentAccounts: Collection dari PaymentAccount aktif
--}}
<x-modal id="detailRABModal{{ $rab->rab_number }}" title="Detail RAB — {{ $rab->rab_number }}" :hideFooter="true">

    {{-- Info Header --}}
    <div class="grid grid-cols-2 gap-2 mb-2 text-sm">
        <div>
            <p class="text-xs text-text-secondary">Tanggal</p>
            <p class="font-semibold text-sm">{{ $rab->date->format('d F Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-text-secondary">Penerima</p>
            <p class="font-semibold text-sm truncate">{{ $rab->recipient }}</p>
        </div>
    </div>

    @if ($rab->incoming_payment > 0)
        <div class="mb-2 p-2 bg-primary-light rounded border border-primary text-xs">
            <p class="text-primary"><strong>Uang Masuk:</strong> Rp {{ number_format($rab->incoming_payment, 0, ',', '.') }}</p>
        </div>
    @endif

    @if ($rab->recipient_address)
        <div class="mb-2 p-2 bg-surface-secondary rounded border border-border-light text-xs">
            <p class="text-text-secondary mb-1"><strong>Alamat:</strong></p>
            <p class="text-text-primary">{{ $rab->recipient_address }}</p>
        </div>
    @endif

    @if ($rab->intro_text)
        <div class="mb-2 p-2 bg-primary-light rounded border border-primary text-xs">
            <p class="text-primary whitespace-pre-wrap">{{ $rab->intro_text }}</p>
        </div>
    @endif

    <hr class="my-2">

    {{-- Detail Pekerjaan (menggunakan relasi eager loaded) --}}
    <h3 class="text-xs font-bold text-text-primary mb-2">Detail Pekerjaan</h3>

    <div class="space-y-2 mb-2 max-h-48 overflow-y-auto border border-border-light rounded p-2 bg-surface-secondary">
        @forelse ($rab->categories as $category)
            <div class="border border-border-strong rounded p-2 bg-surface-base text-xs">
                <h4 class="font-semibold text-text-primary mb-1 text-xs">
                    {{ $category->getRomanNumeral() }}. {{ $category->category_name }}
                </h4>

                <div class="space-y-1">
                    @foreach ($category->subcategories as $subcategory)
                        <div class="border-l-3 border-primary pl-2 py-1 bg-primary-light rounded text-xs">
                            <p class="font-medium text-text-primary text-xs">
                                {{ $subcategory->number_order }}. {{ $subcategory->subcategory_name }}
                            </p>

                            @if ($subcategory->items->count() > 0)
                                <div class="mt-1 space-y-1 text-xs">
                                    @foreach ($subcategory->items as $item)
                                        @php
                                            $itemVolume = $item->volume ?? ($subcategory->volume ?? 0);
                                            $itemUnit = $item->unit ?? ($subcategory->unit ?? '-');
                                            $itemPrice = $item->unit_price ?? ($subcategory->unit_price ?? 0);
                                            $itemSubtotal = $item->sub_harga ?? ($subcategory->sub_harga ?? 0);
                                        @endphp
                                        <div class="rounded bg-surface-base border border-border-light p-2 text-xs">
                                            <div class="font-semibold text-text-primary">
                                                {{ $item->getLetter() }}. {{ $item->item_description }}
                                            </div>
                                            <div class="grid grid-cols-2 gap-1 mt-1 text-text-secondary">
                                                <div><span class="font-semibold">Vol:</span> {{ $itemVolume }}</div>
                                                <div><span class="font-semibold">Satuan:</span> {{ $itemUnit }}</div>
                                                <div><span class="font-semibold">Harga:</span> Rp
                                                    {{ number_format($itemPrice, 0, ',', '.') }}</div>
                                                <div><span class="font-semibold">Jumlah:</span> Rp
                                                    {{ number_format($itemSubtotal, 0, ',', '.') }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @php
                                $subcategoryTotal =
                                    $subcategory->items->sum(function ($item) {
                                        return (int) ($item->sub_harga ?? 0);
                                    }) ?:
                                    (int) ($subcategory->sub_harga ?? 0);
                            @endphp

                            <div class="mt-1 p-1 bg-primary-light rounded text-xs">
                                <p class="text-primary">
                                    <strong>Subtotal: Rp {{ number_format($subcategoryTotal, 0, ',', '.') }}</strong>
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Subtotal per Kategori --}}
                @php
                    $categoryTotal = $category->subcategories->sum(function ($subcategory) {
                        return $subcategory->items->sum(function ($item) {
                            return (int) ($item->sub_harga ?? 0);
                        }) ?:
                            (int) ($subcategory->sub_harga ?? 0);
                    });
                @endphp
                <div class="mt-1 p-1 bg-warning-light rounded border border-warning text-xs">
                    <p class="font-semibold text-text-primary">
                        Subtotal: <span class="text-warning">Rp
                            {{ number_format($categoryTotal, 0, ',', '.') }}</span>
                    </p>
                </div>
            </div>
        @empty
            <div class="text-center p-2 text-text-secondary text-xs">
                <i class="fa-solid fa-inbox mb-1 block"></i> Tidak ada data
            </div>
        @endforelse
    </div>

    {{-- Grand Total (menggunakan relasi eager loaded) --}}
    @php
        $grandTotal = $rab->categories->flatMap(fn($cat) => $cat->subcategories)
            ->sum(function ($subcategory) {
                return $subcategory->items->sum(function ($item) {
                    return (int) ($item->sub_harga ?? 0);
                }) ?:
                    (int) ($subcategory->sub_harga ?? 0);
            });
        $miscCostsTotal = $rab->miscellaneousCosts->sum('amount');
        $totalAnggaranBiaya = $grandTotal + $miscCostsTotal;
    @endphp
    <div class="flex justify-end mb-2">
        <div class="bg-success-light border-2 border-success rounded p-2 w-full text-xs">
            <div class="space-y-1">
                <div class="flex justify-between text-success border-b border-success pb-1">
                    <span><strong>Total Kategori:</strong></span>
                    <span class="font-semibold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-success border-b border-success pb-1">
                    <span><strong>Total Biaya Lain-Lain:</strong></span>
                    <span class="font-semibold">Rp {{ number_format($miscCostsTotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-lg text-success">
                    <span><strong>Total Keseluruhan:</strong></span>
                    <p class="font-bold text-xl text-success">Rp {{ number_format($totalAnggaranBiaya, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Rekening Pembayaran --}}
    @php
        $selectedAccounts = $rab->selected_payment_accounts ?? [];
    @endphp
    @if (!empty($selectedAccounts))
        <hr class="my-2">
        <div class="text-xs">
            <p class="font-semibold text-text-primary mb-1">Rekening Pembayaran</p>
            <div class="space-y-1">
                @foreach ($paymentAccounts as $account)
                    @if (in_array($account->id, $selectedAccounts))
                        <div class="p-1 bg-surface-secondary rounded border border-border-light text-xs">
                            <p class="font-semibold">{{ $account->bank_name }} - {{ $account->account_number }}</p>
                            <p class="text-text-secondary text-xs">a/n {{ $account->account_holder }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

</x-modal>
