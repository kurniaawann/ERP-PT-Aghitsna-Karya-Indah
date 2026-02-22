<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            padding: 20px;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
        }

        /* Header Section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            width: 80px;
            height: 80px;
            float: left;
            margin-right: 10px;
            object-fit: contain;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .company-info {
            margin-left: 90px;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #FF6600;
            margin-bottom: 5px;
        }

        .company-address {
            font-size: 10px;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .invoice-info {
            font-size: 11px;
            line-height: 1.8;
        }

        .invoice-info table {
            width: 100%;
        }

        .invoice-info td {
            padding: 2px 0;
        }

        .invoice-info td:first-child {
            width: 60px;
        }

        .invoice-info td:nth-child(2) {
            width: 10px;
        }

        /* Recipient Section */
        .recipient {
            margin: 20px 0;
        }

        .recipient-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .recipient-name {
            margin-left: 80px;
            margin-bottom: 10px;
        }

        /* Description Section */
        .description {
            margin: 15px 0;
            text-align: justify;
        }

        /* Table Section */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 6px 5px;
            font-size: 10px;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .items-table td.left {
            text-align: left;
        }

        .total-row {
            background-color: #FFFF00;
            font-weight: bold;
        }

        /* Footer Section */
        .terbilang {
            font-style: italic;
            margin: 10px 0;
            font-size: 10px;
        }

        .payment-info {
            margin: 20px 0;
            line-height: 1.8;
        }

        .closing {
            margin: 20px 0;
            text-align: justify;
        }

        .signature {
            margin-top: 30px;
            text-align: left;
        }

        .signature-line {
            margin-top: 60px;
            font-weight: bold;
        }

        /* Utilities */
        .bold {
            font-weight: bold;
        }

        .italic {
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="logo">
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
                </div>
                <div class="company-info">
                    <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                    <div class="company-address">
                        AGHITSNA ALUMUNIUM DAN BAJA RINGAN<br>
                        JL. CEMARA RT 02 RW 07, KEL, GROGOL<br>
                        KEC. LIMO KOTA DEPOK<br>
                        Telp. 0882 1303 1263 / 0882 1303 1264<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email: Design@aghitsna.id
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-info">
                    <table>
                        <tr>
                            <td>No</td>
                            <td>:</td>
                            <td>{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td>Tanggal</td>
                            <td>:</td>
                            <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY') }}</td>
                        </tr>
                        <tr>
                            <td>Hal</td>
                            <td>:</td>
                            <td>{{ $invoice->regarding }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recipient -->
        <div class="recipient">
            <div class="recipient-label">Kepada Yth :</div>
            <div class="recipient-name">{{ $invoice->recipient }}</div>
        </div>

        <!-- Description -->
        <div class="description">
            <strong>Ditempat</strong><br>
            {{ $invoice->project_description }}
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 35%;">Keterangan</th>
                    <th style="width: 12%;">Volume</th>
                    <th style="width: 10%;">Satuan</th>
                    <th style="width: 18%;">Harga</th>
                    <th style="width: 20%;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                    $totalAmount = 0;
                @endphp

                @foreach ($items as $index => $item)
                    @php
                        $jumlah = floatval($item['volume']) * floatval($item['harga']);
                        $totalAmount += $jumlah;
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="left">{{ $item['keterangan'] }}</td>
                        <td class="right">{{ number_format($item['volume'], 2, ',', '.') }}</td>
                        <td class="center">{{ $item['satuan'] }}</td>
                        <td class="right">Rp {{ number_format($item['harga'], 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                <!-- Total Row -->
                <tr class="total-row">
                    <td colspan="5" class="center"><strong>Jumlah</strong></td>
                    <td class="right"><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></td>
                </tr>

                @if ($invoice->discount_value && $invoice->discount_value > 0)
                    @php
                        $discountAmount = $invoice->getDiscountAmount($totalAmount);
                        $finalTotal = $totalAmount - $discountAmount;
                    @endphp

                    <!-- Discount Row -->
                    <tr style="background-color: #FFE6E6;">
                        <td colspan="5" class="center"><strong>Discount
                                @if ($invoice->discount_type === 'percentage')
                                    ({{ number_format($invoice->discount_value, 0) }}%)
                                @endif
                            </strong></td>
                        <td class="right"><strong>Rp {{ number_format($discountAmount, 0, ',', '.') }}</strong></td>
                    </tr>

                    <!-- Total After Discount Row -->
                    <tr style="background-color: #90EE90;">
                        <td colspan="5" class="center"><strong>Total Setelah Discount</strong></td>
                        <td class="right"><strong>Rp {{ number_format($finalTotal, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif

                @if ($invoice->dp_value && $invoice->dp_value > 0)
                    @php
                        $baseForDP = isset($finalTotal) ? $finalTotal : $totalAmount;
                        $dpAmount = $invoice->getDpAmount($baseForDP);
                    @endphp

                    <!-- DP Row -->
                    <tr style="background-color: #ADD8E6;">
                        <td colspan="5" class="center"><strong>DP
                                @if ($invoice->dp_type === 'percentage')
                                    ({{ number_format($invoice->dp_value, 0) }}%)
                                @endif
                            </strong></td>
                        <td class="right"><strong>Rp {{ number_format($dpAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif

                @if ($invoice->payment_installments)
                    @php
                        $paymentInstallments = is_string($invoice->payment_installments)
                            ? json_decode($invoice->payment_installments, true)
                            : $invoice->payment_installments;
                    @endphp

                    @if (is_array($paymentInstallments) && count($paymentInstallments) > 0)
                        @foreach ($paymentInstallments as $index => $payment)
                            <!-- Payment Installment Row -->
                            <tr style="background-color: #E9D5FF;">
                                <td colspan="5" class="center">
                                    <strong>{{ $payment['label'] ?? 'Pembayaran ' . ($index + 1) }}</strong>
                                </td>
                                <td class="right"><strong>Rp
                                        {{ number_format($payment['amount'] ?? 0, 0, ',', '.') }}</strong></td>
                            </tr>
                        @endforeach
                    @endif
                @endif
            </tbody>
        </table>

        <!-- Terbilang -->
        @php
            $terbilangAmount = isset($finalTotal) ? $finalTotal : $totalAmount;
        @endphp
        <div class="terbilang">
            <em>Terbilang : {{ ucwords(terbilang($terbilangAmount)) }} rupiah</em>
        </div>

        <!-- Payment Information -->
        <div class="payment-info">
            Pembayaran dapat ditransfer melalui nomor rekening<br>
            @php
                $selectedAccountIds = is_string($invoice->selected_payment_accounts)
                    ? json_decode($invoice->selected_payment_accounts, true)
                    : $invoice->selected_payment_accounts ?? [];

                if (!empty($selectedAccountIds)) {
                    $paymentAccounts = \App\Models\Finance\PaymentAccount::whereIn('id', $selectedAccountIds)
                        ->orderBy('id')
                        ->get();
                } else {
                    // Fallback ke semua rekening aktif jika tidak ada yang dipilih
                    $paymentAccounts = \App\Models\Finance\PaymentAccount::active()->get();
                }
            @endphp
            @foreach ($paymentAccounts as $account)
                <strong>{{ $account->bank_name }}</strong> / No : <strong>{{ $account->account_number }}</strong> a/n
                <strong>{{ $account->account_holder }}</strong><br>
            @endforeach
            @if ($paymentAccounts->isEmpty())
                <em>Tidak ada rekening pembayaran yang tersedia</em>
            @endif
        </div>

        <!-- Closing -->
        <div class="closing">
            Demikian Invoice ini kami buat atas perhatian dan kerjasamanya kami ucapkan terima kasih.
        </div>

        <!-- Signature -->
        <div class="signature">
            <div>Hormat Kami,</div>
            <div class="signature-line">PT AGHITSNA KARYA INDAH</div>
        </div>
    </div>
</body>

</html>
