@extends('layouts.app')

@section('title', 'Detail RAB - PT Aghitsna Karya Indah')

@section('content')
    <div class="bg-white p-6 rounded-xl shadow">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-text-primary mb-2">RANCANGAN ANGGARAN BIAYA</h1>
                <p class="text-gray-600">No: <strong>{{ $rab->rab_number }}</strong></p>
                <p class="text-gray-600">Tanggal: <strong>{{ $rab->date->format('d F Y') }}</strong></p>
            </div>
            <div class="text-right">
                <a href="{{ route('rab.index') }}" class="btn btn-secondary mb-2">Kembali</a>
            </div>
        </div>

        <!-- Penerima -->
        <div class="mb-6 p-4 border border-gray-300 rounded">
            <p class="mb-2"><strong>Kepada:</strong></p>
            <p class="text-gray-700">{{ $rab->recipient }}</p>
            <p class="text-gray-700">{{ $rab->recipient_address }}</p>
        </div>

        <!-- Intro Text -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
            <p class="text-gray-700 whitespace-pre-wrap">{{ $rab->intro_text }}</p>
        </div>

        <!-- RAB Details Table -->
        <div class="mb-6 overflow-x-auto">
            <table class="w-full border-collapse border border-gray-400 text-sm">
                <thead>
                    <tr class="bg-yellow-300">
                        <th class="border border-gray-400 px-3 py-2 text-left font-bold">NO</th>
                        <th class="border border-gray-400 px-3 py-2 text-left font-bold">JENIS PEKERJAAN</th>
                        <th class="border border-gray-400 px-3 py-2 text-center font-bold">VOL</th>
                        <th class="border border-gray-400 px-3 py-2 text-center font-bold">SATUAN</th>
                        <th class="border border-gray-400 px-3 py-2 text-right font-bold">HARGA/SATUAN</th>
                        <th class="border border-gray-400 px-3 py-2 text-right font-bold">JUMLAH</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotal = 0;
                    @endphp

                    @foreach ($rab->categories()->orderBy('roman_order')->get() as $category)
                        <tr class="bg-yellow-200">
                            <td colspan="6" class="border border-gray-400 px-3 py-2 font-bold">
                                {{ $category->getRomanNumeral() }} {{ $category->category_name }}
                            </td>
                        </tr>

                        @foreach ($category->subcategories()->orderBy('number_order')->get() as $subcategory)
                            <tr>
                                <td class="border border-gray-400 px-3 py-2">{{ $subcategory->number_order }}</td>
                                <td class="border border-gray-400 px-3 py-2">
                                    <strong>{{ $subcategory->subcategory_name }}</strong>

                                    <!-- Items (letters) -->
                                    @foreach ($subcategory->items()->orderBy('letter_order')->get() as $item)
                                        <div class="ml-4 text-gray-700">
                                            {{ $item->getLetter() }}. {{ $item->item_description }}
                                        </div>
                                    @endforeach
                                </td>
                                <td class="border border-gray-400 px-3 py-2 text-center">{{ $subcategory->volume }}</td>
                                <td class="border border-gray-400 px-3 py-2 text-center">{{ $subcategory->unit }}</td>
                                <td class="border border-gray-400 px-3 py-2 text-right">
                                    Rp. {{ number_format($subcategory->unit_price, 0, ',', '.') }}
                                </td>
                                <td class="border border-gray-400 px-3 py-2 text-right font-bold">
                                    @php
                                        $subTotal = $subcategory->sub_harga;
                                        $grandTotal += $subTotal;
                                    @endphp
                                    Rp. {{ number_format($subTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach

                        <!-- Category Subtotal -->
                        @php
                            $categorySubtotal = $category->subcategories()->pluck('sub_harga')->sum();
                        @endphp
                        <tr class="bg-yellow-100">
                            <td colspan="5" class="border border-gray-400 px-3 py-2 text-right font-bold">
                                Subtotal {{ $category->getRomanNumeral() }}
                            </td>
                            <td class="border border-gray-400 px-3 py-2 text-right font-bold">
                                Rp. {{ number_format($categorySubtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach

                    <!-- Grand Total -->
                    <tr class="bg-yellow-300 font-bold text-lg">
                        <td colspan="5" class="border border-gray-400 px-3 py-2 text-right">JUMLAH ANGGARAN BANGUNAN</td>
                        <td class="border border-gray-400 px-3 py-2 text-right">
                            Rp. {{ number_format($grandTotal, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column - Additional Info -->
            <div>
                <div class="mb-4 p-3 bg-gray-50 border border-gray-300 rounded">
                    <p class="text-gray-700">
                        <strong>Terbilang:</strong><br>
                        {{ $rab->amount_in_words }}
                    </p>
                </div>
            </div>

            <!-- Right Column - Payment Methods -->
            <div>
                @if ($rab->selected_payment_accounts && count($rab->selected_payment_accounts) > 0)
                    <div class="p-3 bg-gray-50 border border-gray-300 rounded">
                        <p class="font-bold mb-2">Rekening Pembayaran:</p>
                        @php
                            $accounts = is_array($rab->selected_payment_accounts)
                                ? $rab->selected_payment_accounts
                                : json_decode($rab->selected_payment_accounts, true);
                        @endphp
                        <ul class="list-disc list-inside text-sm text-gray-700">
                            @foreach ($accounts as $accountId)
                                @php
                                    $account = \App\Models\Finance\PaymentAccount::find($accountId);
                                @endphp
                                @if ($account)
                                    <li>{{ $account->bank_name }} - {{ $account->account_number }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <!-- Signature & Notes -->
        <div class="mt-8 pt-6 border-t border-gray-300">
            <div class="grid grid-cols-2 gap-8 text-center">
                <div>
                    <p class="text-sm text-gray-600 mb-12">Hormat kami,</p>
                    <p class="text-sm text-gray-600">PT. AGHITSNA KARYA INDAH</p>
                    @if ($rab->signed_by)
                        <p class="font-bold mt-2">
                            <u>{{ $rab->signed_by }}</u>
                        </p>
                        @if ($rab->division)
                            <p class="text-sm text-gray-600">{{ $rab->division }}</p>
                        @endif
                    @else
                        <div style="height: 40px;"></div>
                    @endif
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-12">Diketahui,</p>
                    <div style="height: 60px;"></div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex gap-2 justify-end">
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <i class="fas fa-print mr-2"></i>Cetak
            </button>
            <a href="{{ route('rab.index') }}" class="btn btn-primary">Kembali ke Daftar</a>
        </div>
    </div>
@endsection
