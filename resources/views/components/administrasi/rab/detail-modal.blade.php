<x-modal id="detailRABModal{{ $rab->rab_number }}" title="Detail RAB — {{ $rab->rab_number }}" readonly="true">

    {{-- Header Info --}}
    <div class="grid grid-cols-2 gap-2 mb-2 text-sm">
        <div>
            <p class="text-xs text-gray-500">Tanggal</p>
            <p class="font-semibold text-sm">{{ $rab->date->format('d F Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Penerima</p>
            <p class="font-semibold text-sm truncate">{{ $rab->recipient }}</p>
        </div>
    </div>

    @if ($rab->recipient_address)
        <div class="mb-2 p-2 bg-gray-50 rounded border border-gray-200 text-xs">
            <p class="text-gray-500 mb-1"><strong>Alamat:</strong></p>
            <p class="text-gray-700">{{ $rab->recipient_address }}</p>
        </div>
    @endif

    @if ($rab->intro_text)
        <div class="mb-2 p-2 bg-blue-50 rounded border border-blue-200 text-xs">
            <p class="text-blue-900 whitespace-pre-wrap">{{ $rab->intro_text }}</p>
        </div>
    @endif

    <hr class="my-2">

    {{-- Detail Pekerjaan --}}
    <h3 class="text-xs font-bold text-text-primary mb-2">Detail Pekerjaan</h3>

    <div class="space-y-2 mb-2 max-h-48 overflow-y-auto border rounded p-2 bg-gray-50">
        @forelse ($rab->categories()->orderBy('roman_order')->get() as $category)
            <div class="border rounded p-2 bg-white text-xs">
                {{-- Category Header --}}
                <h4 class="font-semibold text-text-primary mb-1 text-xs">
                    {{ $category->getRomanNumeral() }}. {{ $category->category_name }}
                </h4>

                {{-- Subcategories --}}
                <div class="space-y-1">
                    @foreach ($category->subcategories()->orderBy('number_order')->get() as $subcategory)
                        <div class="border-l-3 border-blue-300 pl-2 py-1 bg-blue-50 rounded text-xs">
                            {{-- Subcategory Name --}}
                            <p class="font-medium text-gray-800 text-xs">
                                {{ $subcategory->number_order }}. {{ $subcategory->subcategory_name }}
                            </p>

                            {{-- Volume, Unit, Price --}}
                            <div class="grid grid-cols-3 gap-1 text-xs mt-1 text-gray-600">
                                <div><span class="font-semibold">Vol:</span> {{ $subcategory->volume }}</div>
                                <div><span class="font-semibold">Satuan:</span> {{ $subcategory->unit }}</div>
                                <div><span class="font-semibold">Harga:</span> Rp
                                    {{ number_format($subcategory->unit_price, 0, ',', '.') }}</div>
                            </div>

                            {{-- Total Harga --}}
                            <div class="mt-1 p-1 bg-blue-100 rounded text-xs">
                                <p class="text-blue-900">
                                    <strong>Rp {{ number_format($subcategory->sub_harga, 0, ',', '.') }}</strong>
                                </p>
                            </div>

                            {{-- Items (Pekerjaan) --}}
                            @if ($subcategory->items->count() > 0)
                                <div class="mt-1 ml-1 space-y-0.5 text-xs">
                                    @foreach ($subcategory->items()->orderBy('letter_order')->get() as $item)
                                        <div class="text-gray-700 text-xs">
                                            <strong>{{ $item->getLetter() }}.</strong> {{ $item->item_description }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Category Subtotal --}}
                @php
                    $categoryTotal = $category->subcategories()->pluck('sub_harga')->sum();
                @endphp
                <div class="mt-1 p-1 bg-yellow-100 rounded border border-yellow-200 text-xs">
                    <p class="font-semibold text-gray-800">
                        Subtotal: <span class="text-yellow-700">Rp
                            {{ number_format($categoryTotal, 0, ',', '.') }}</span>
                    </p>
                </div>
            </div>
        @empty
            <div class="text-center p-2 text-gray-500 text-xs">
                <i class="fa-solid fa-inbox mb-1 block"></i> Tidak ada data
            </div>
        @endforelse
    </div>

    {{-- Grand Total --}}
    @php
        $grandTotal = $rab
            ->categories()
            ->with('subcategories')
            ->get()
            ->flatMap(fn($cat) => $cat->subcategories)
            ->sum('sub_harga');
    @endphp
    <div class="flex justify-end mb-2">
        <div class="bg-green-50 border-2 border-green-300 rounded p-2 w-full text-xs">
            <p class="text-green-900"><strong>Total Keseluruhan:</strong></p>
            <p class="font-bold text-lg text-green-600">Rp {{ number_format($grandTotal, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Payment Accounts --}}
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
                        <div class="p-1 bg-gray-50 rounded border border-gray-200 text-xs">
                            <p class="font-semibold">{{ $account->bank_name }} - {{ $account->account_number }}</p>
                            <p class="text-gray-600 text-xs">a/n {{ $account->account_holder }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

</x-modal>
