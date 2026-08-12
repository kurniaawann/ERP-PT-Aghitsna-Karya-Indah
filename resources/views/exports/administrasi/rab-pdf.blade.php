<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAB - {{ $rab->rab_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.4;
            color: #000;
            background: white;
            font-size: 10px;
        }

        .page {
            padding: 1cm;
            margin: 0 auto;
        }

        /* ──── Header ──── */
        .header {
            width: 100%;
            margin-bottom: 15px;
            position: relative;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
        }

        .header-left {
            display: inline-block;
            vertical-align: middle;
        }

        .header-left img {
            width: 80px;
            height: auto;
        }

        .header-center {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            width: calc(100% - 100px);
        }

        .header-center h1 {
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            margin: 0;
        }

        .info-section {
            width: 100%;
            margin-bottom: 15px;
        }

        .company-info {
            width: 45%;
            display: inline-block;
            vertical-align: top;
            font-size: 10px;
            line-height: 1.5;
        }

        .recipient-info {
            width: 50%;
            display: inline-block;
            vertical-align: top;
            font-size: 10px;
        }

        .recipient-info table {
            width: auto;
            float: right;
        }

        .recipient-info td {
            padding: 1px 2px;
        }

        .recipient-info .label {
            width: 60px;
        }


        /* ──── Opening ──── */
        .opening {
            font-size: 10px;
            margin-bottom: 0;
            padding-bottom: 8px;
            line-height: 1.5;
            border-bottom: 0.5px solid #000;
        }

        /* ──── Table ──── */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            font-size: 9px;
        }

        table.main-table thead {
            background-color: #ffcc00;
        }

        table.main-table th,
        table.main-table td {
            border: 0.75px solid #000;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
        }

        table.main-table th {
            font-weight: bold;
        }

        table.main-table .text-left {
            text-align: left;
        }

        table.main-table .text-right {
            text-align: right;
        }

        table.main-table {
            margin-top: 0;
        }

        .category-row td {
            background-color: #d9d9d9;
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 0.75px solid #666;
        }

        .subcategory-row td {
            background-color: #f0f0f0;
            font-weight: 600;
            font-size: 10px;
        }

        .item-row td {
            text-align: left;
            padding-left: 40px;
            background-color: #fff;
            font-size: 9px;
            font-weight: normal;
        }

        .subtotal-row td {
            background-color: #e8e8e8;
            font-weight: bold;
            text-align: right;
            border-top: 0.75px solid #333;
            border-bottom: 1px solid #000;
        }

        /* ──── Summary Section ──── */
        .summary-section {
            width: 100%;
            margin-top: 0;
            font-size: 9px;
            border-collapse: collapse;
        }

        .summary-row td {
            border: 0.75px solid #000;
            padding: 2px 3px;
            font-size: 9px;
        }

        .summary-row .label {
            font-weight: bold;
            padding-left: 25px;
            text-align: left;
            width: 70%;
        }

        .summary-row .value {
            text-align: right;
            width: 30%;
        }

        .summary-row.total-row td {
            background-color: #fff;
            font-weight: bold;
        }

        .summary-row.total-amount-row td {
            background-color: #ffff00;
            font-weight: bold;
        }

        .summary-row.terbilang-row td {
            /* background-color: #fff; */
            background-color:  #ffff00;
            font-weight: bold;
            font-style: italic;
        }

        /* ──── Bank Info & Footer ──── */
        .footer-section {
            margin-top: 20px;
            font-size: 10px;
            line-height: 1.6;
        }

        .footer-section p {
            margin: 8px 0;
        }

        .footer-section strong {
            display: block;
            margin-bottom: 2px;
        }

        .payment-accounts-list {
            margin-top: 8px;
            margin-bottom: 15px;
        }

        .payment-account-item {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .payment-account-item .bank-info {
            font-weight: bold;
            font-size: 10px;
        }

        .payment-account-item .account-holder {
            font-size: 9px;
            color: #333;
        }

        .signature-section {
            margin-top: 20px;
            width: 100%;
            text-align: right;
        }

        .signature-box {
            display: inline-block;
            text-align: center;
            width: 250px;
        }

        .signature-box .name {
            margin-top: 60px;
            font-weight: bold;
            text-decoration: underline;
        }

        .no-border {
            border: none !important;
        }

        .no-border td {
            border: none !important;
        }
    </style>
</head>

<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
            </div>
            <div class="header-center">
                <h1>RENCANA ANGGARAN BIAYA</h1>
            </div>
        </div>

        <!-- Company & Recipient Info -->
        <div class="info-section">
            <div class="company-info">
                <p><strong>PT. AGHITSNA KARYA INDAH</strong></p>
                <p>Jl. Pertiwi No.36 Rt.01/Rw.05, Tanah Baru</p>
                <p>Depok - Jawa Barat</p>
            </div>
            <div class="recipient-info">
                <table>
                    <tr>
                        <td class="label">To</td>
                        <td>: {{ $rab->recipient }}</td>
                    </tr>
                    <tr>
                        <td class="label">No.</td>
                        <td>: {{ $rab->rab_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tanggal</td>
                        <td>: {{ $rab->date->format('d F Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>


        <!-- Opening -->
        <div class="opening">
            <p><strong>Dengan Hormat,</strong></p>
            <p>{{ $rab->intro_text_or_default }}</p>
        </div>

        <!-- Main Table -->
        <table class="main-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">NO</th>
                    <th rowspan="2" style="width: 35%;">JENIS PEKERJAAN</th>
                    <th rowspan="2" style="width: 8%;">VOL</th>
                    <th rowspan="2" style="width: 12%;">SATUAN</th>
                    <th colspan="2">HARGA</th>
                    <th rowspan="2" style="width: 15%;">JUMLAH</th>
                </tr>
                <tr>
                    <th style="width: 15%;">SATUAN</th>
                    <th style="width: 15%;">SUB HARGA</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandTotal = 0;
                    $arabicToRoman = function ($num) {
                        $map = [
                            'M' => 1000,
                            'CM' => 900,
                            'D' => 500,
                            'CD' => 400,
                            'C' => 100,
                            'XC' => 90,
                            'L' => 50,
                            'XL' => 40,
                            'X' => 10,
                            'IX' => 9,
                            'V' => 5,
                            'IV' => 4,
                            'I' => 1,
                        ];
                        $returnValue = '';
                        while ($num > 0) {
                            foreach ($map as $roman => $int) {
                                if ($num >= $int) {
                                    $num -= $int;
                                    $returnValue .= $roman;
                                    break;
                                }
                            }
                        }
                        return $returnValue;
                    };
                @endphp

                @foreach ($rab->categories as $category)
                    @php
                        $categoryRoman = $arabicToRoman($category->roman_order);
                        $categoryTotal = 0;
                        $subcategoryTotal = 0;
                    @endphp
                    <tr class="category-row">
                        <td class="text-left"><strong style="font-size: 12px;">{{ $categoryRoman }}</strong></td>
                        <td class="text-left"><strong>{{ $category->category_name }}</strong></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>

                    @foreach ($category->subcategories as $subcategory)
                        @php
                            $subcategoryTotal = 0;
                        @endphp

                        <tr class="subcategory-row">
                            <td>{{ $subcategory->number_order }}</td>
                            <td class="text-left" style="padding-left: 20px;">
                                <strong style="color: #333;">{{ $subcategory->subcategory_name }}</strong>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        @foreach ($subcategory->items as $item)
                            @php
                                $itemVolume = $item->volume ?? null;
                                $itemUnit = $item->unit ?? null;
                                $itemPrice = (int) ($item->unit_price ?? 0);
                                $itemSubtotal =
                                    (int) ($item->sub_harga ??
                                        ($itemVolume && $itemPrice ? $itemVolume * $itemPrice : 0));
                                $subcategoryTotal += $itemSubtotal;
                            @endphp
                            <tr class="item-row">
                                <td></td>
                                <td class="text-left" style="padding-left: 50px;">
                                    <em style="color: #555;">{{ chr(96 + $item->letter_order) }}.
                                        {{ $item->item_description }}</em>
                                </td>
                                <td class="text-center">{{ $itemVolume !== null ? $itemVolume : '' }}</td>
                                <td class="text-center">{{ $itemUnit ?: '' }}</td>
                                <td class="text-right">
                                    {{ $itemPrice > 0 ? number_format($itemPrice, 0, ',', '.') : '' }}
                                </td>
                                <td class="text-right">
                                    {{ $itemSubtotal > 0 ? number_format($itemSubtotal, 0, ',', '.') : '' }}
                                </td>
                                <td></td>
                            </tr>
                        @endforeach

                        @if ($subcategoryTotal === 0)
                            @php
                                $subcategoryTotal = (int) ($subcategory->sub_harga ?? 0);
                            @endphp
                        @endif

                        @php
                            $categoryTotal += $subcategoryTotal;
                        @endphp
                    @endforeach

                    @if ($category->subcategories->count() > 0)
                        <tr class="subtotal-row">
                            <td colspan="6" class="text-right"></td>
                            <td class="text-right">{{ number_format($categoryTotal, 0, ',', '.') }}</td>
                        </tr>
                        @php
                            $grandTotal += $categoryTotal;
                        @endphp
                    @endif
                @endforeach

                {{-- Summary Section Rows --}}
                @php
                    $miscCostsTotal = $rab->miscellaneousCosts->sum('amount');
                    $totalAnggaranBiaya = $grandTotal + $miscCostsTotal;
                @endphp
                <tr class="summary-row total-row">
                    <td colspan="6" class="label" style="width: 70%;">I. Jumlah Anggaran Bangunan</td>
                    <td class="value" style="width: 30%;">Rp. {{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
                <tr class="summary-row total-row">
                    <td colspan="6" class="label" style="width: 70%;">II. Biaya Lain-Lain</td>
                    <td class="value" style="width: 30%;">Rp.
                        {{ number_format($rab->miscellaneousCosts->sum('amount'), 0, ',', '.') }}</td>
                </tr>
                @foreach ($rab->miscellaneousCosts as $miscCost)
                    <tr class="summary-row total-row">
                        <td colspan="6" class="label" style="padding-left: 40px;">{{ $miscCost->item_order }}.
                            {{ $miscCost->item_name }}</td>
                        <td class="value">Rp. {{ number_format($miscCost->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="summary-row total-amount-row">
                    <td colspan="6" class="label">TOTAL ANGGARAN BIAYA</td>
                    <td class="value">Rp. {{ number_format($totalAnggaranBiaya, 0, ',', '.') }}</td>
                </tr>
                @if (!auth()->user()->isAdmin())
                    <tr class="summary-row terbilang-row">
                        <td colspan="6" class="label">TERBILANG</td>
                        <td class="value">{{ ucwords($rab->amount_in_words) }}</td>
                    </tr>
                @endif
                @if (auth()->user()->role === 'superadmin')
                    @php
                        $incomingPayment = $rab->incoming_payment ?? 0;
                        $sisaPembayaran = $totalAnggaranBiaya - $incomingPayment;
                    @endphp
                    @if ($incomingPayment > 0)
                        <tr class="summary-row" style="background-color: #edbcbc;">
                            <td colspan="6" class="label">UANG MASUK (DP)</td>
                            <td class="value">Rp. {{ number_format($incomingPayment, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="summary-row total-amount-row" style="background-color: #FFE0B2;">
                            <td colspan="6" class="label">SISA PEMBAYARAN</td>
                            <td class="value">Rp. {{ number_format($sisaPembayaran, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="summary-row terbilang-row">
                            <td colspan="6" class="label">TERBILANG</td>
                            <td class="value">{{ ucwords(terbilang($sisaPembayaran)) }} rupiah</td>
                        </tr>
                    @endif
                @endif
            </tbody>
        </table>

        @if (!auth()->user()->isAdmin())
            <div class="footer-section">
                <p><strong>Pembayaran dapat ditransfer melalui nomor rekening :</strong></p>
                @php
                    $accountIds = is_string($rab->selected_payment_accounts)
                        ? json_decode($rab->selected_payment_accounts, true)
                        : $rab->selected_payment_accounts;

                    // Fetch full account data from database using the IDs
                    $accounts = [];
                    if (is_array($accountIds) && count($accountIds) > 0) {
                        $accounts = \App\Models\Finance\PaymentAccount::whereIn('id', $accountIds)->get();
                    }
                @endphp
                @if (count($accounts) > 0)
                    <div class="payment-accounts-list">
                        @foreach ($accounts as $account)
                            <div class="payment-account-item">
                                <div class="bank-info">{{ $account->bank_name }} - {{ $account->account_number }} <span
                                        class="account-holder">a/n {{ $account->account_holder }}</span></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p><em>Tidak ada rekening pembayaran yang dipilih</em></p>
                @endif
            </div>
        @endif

        <div class="footer-section">
            <p>Demikian rencana anggaran biaya ini kami sampaikan, atas perhatian dan kerjasamanya kami ucapkan terima
                kasih.</p>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <p>Hormat Kami,</p>
                <p><strong>PT. AGHITSNA KARYA INDAH</strong></p>
                <div class="name">{{ $rab->signed_by }}</div>
                <div>{{ $rab->division }}</div>
            </div>
        </div>
    </div>
</body>

</html>
