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
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
            line-height: 1.4;
            padding: 15mm 15mm 15mm 15mm;
        }
       

        .container {
            max-width: 210mm;
            margin: 0 auto;
        }

        .header {
            width: 100%;
            margin-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 0;
        }

        .logo-cell {
            width: 100px;
            vertical-align: top;
            padding-right: 10px;
        }

        .logo-cell img {
            display: block;
            width: 80px;
            height: 60px;
            object-fit: contain;
        }

        .title-cell {
            text-align: center;
            vertical-align: middle;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
        }

        .company-address {
            font-size: 10px;
            line-height: 1.8;
        }

        .invoice-info {
            font-size: 11px;
            line-height: 1.8;
        }

        .invoice-info table {
            margin-left: 0;
            /* margin-right: 15px; */
        }

        .invoice-info td {
            padding: 2px 0;
        }

        .invoice-info td:first-child {
            width: 65px;
        }

        .invoice-info td:nth-child(2) {
            width: 10px;
        }

        .recipient {
            margin: 5px 0;
        }

        .recipient-label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .recipient-name {
            margin-left: 80px;
            margin-bottom: 10px;
        }

        .description {
            margin: 5px 0;
            text-align: justify;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
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

        .terbilang {
            font-style: italic;
            margin: 5px 0;
            font-size: 10px;
        }

        .payment-info {
            margin: 5px 0;
            line-height: 1.8;
        }

        .closing {
            margin: 5px 0;
            text-align: justify;
        }

        .signature {
            margin-top: 5px;
            text-align: left;
        }

        .signature-line {
            margin-top: 60px;
            font-weight: bold;
        }

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
    <table class="header-table" cellpadding="0" cellspacing="0" border="0" width="100%">
        
        <tr>
            <td width="45%" valign="top" style="padding-bottom: 15px;">
                <div class="logo-cell">
                    <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo" width="80" height="80">
                </div>
            </td>
            
            <td width="20%" valign="middle" style="text-align: center; padding-bottom: 15px;">
                <div class="invoice-title" style="font-weight: bold; font-size: 16px; letter-spacing: 1px;">
                    INVOICE
                </div>
            </td>
            
            <td width="35%" valign="top" style="padding-bottom: 15px;"></td>
        </tr>

        <tr>
            <td valign="top">
                <div class="company-info" style="white-space: pre-line">
                    AGHITSNA ALUMUNIUM DAN BAJA RINGAN
                    JL. TANAH BARU RAYA PERTIWI RT. 01/05 BEJI, DEPOK, JAWA BARAT
                    Telp. 021 - 29034923 - 0812 9596 552
                    Email : Design@aghitsna.id
                </div>
            </td>

            <!-- Tengah: Dikosongkan -->
            <td valign="top"></td>

            <td valign="top">
                <div class="invoice-info" style="font-size: 12px;">
                    <table cellpadding="0" cellspacing="0" border="0" align="right">
                        <tr>
                            <td style="padding-right: 5px;" valign="top">No</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding-right: 5px;" valign="top">Tanggal</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ \Carbon\Carbon::parse($invoice->invoice_date)->isoFormat('DD MMMM YYYY') }}</td>
                        </tr>
                        <tr>
                            <td style="padding-right: 5px;" valign="top">Hal</td>
                            <td style="padding-right: 5px;" valign="top">:</td>
                            <td valign="top">{{ $invoice->regarding ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>

    </table>
</div>
        <!-- Recipient -->
        <div class="recipient">
            <div class="recipient-label">Kepada Yth :</div>
            <div class="recipient-name">{{ $invoice->recipient }}</div>
        </div>

        <!-- Description -->
        <div class="description">
            Dengan ini kami sampaikan {{ $invoice->project_description }}
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

                @php
                    $discountAmount = 0;
                    $dpAmount = 0;
                    if ($invoice->discount_value && $invoice->discount_value > 0) {
                        $discountAmount = $invoice->getDiscountAmount($totalAmount);
                    }
                    if ($invoice->dp_value && $invoice->dp_value > 0) {
                        $dpAmount = $invoice->getDpAmount($totalAmount);
                    }
                    $hasDiscountOrDp = $discountAmount > 0 || $dpAmount > 0;
                    $remainingAmount = $totalAmount - $discountAmount - $dpAmount;
                @endphp

                <tr>
                    <!-- Kolom 1-4 dikosongkan tanpa warna latar belakang -->
                    <td colspan="4" style="border: none; background-color: #fff;"></td>
                    <!-- Teks masuk ke kolom Harga (Kolom 5) -->
                    <td class="right" style="background-color: #FFFF00; border: 1px solid #000;"><strong>Jumlah</strong></td>
                    <!-- Nominal di kolom Jumlah (Kolom 6) -->
                    <td class="right" style="background-color: #FFFF00; border: 1px solid #000;"><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></td>
                </tr>

                @if ($invoice->discount_value && $invoice->discount_value > 0)
                    @php
                        $discountAmount = $invoice->getDiscountAmount($totalAmount);
                    @endphp

                    <!-- Discount Row -->
                    <tr>
                        <!-- Kolom 1-4 dikosongkan -->
                        <td colspan="4" style="border: none; background-color: #fff;"></td>
                        <!-- Teks masuk ke kolom Harga (Kolom 5) -->
                        <td class="right" style="background-color: #FFE6E6; border: 1px solid #000;"><strong>Discount
                                @if ($invoice->discount_type === 'percentage')
                                    ({{ number_format($invoice->discount_value, 0) }}%)
                                @endif
                            </strong></td>
                        <td class="right" style="background-color: #FFE6E6; border: 1px solid #000;"><strong>Rp {{ number_format($discountAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif

               @if ($invoice->dp_value && $invoice->dp_value > 0)
                    @php
                        $dpAmount = $invoice->getDpAmount($totalAmount);
                    @endphp

                    <!-- DP Row -->
                    <tr>
                        <!-- Kolom 1-4 dikosongkan -->
                        <td colspan="4" style="border: none; background-color: #fff;"></td>
                        <!-- Teks masuk ke kolom Harga (Kolom 5) -->
                        <td class="right" style="background-color: #ADD8E6; border: 1px solid #000;"><strong>DP
                                @if ($invoice->dp_type === 'percentage')
                                    ({{ number_format($invoice->dp_value, 0) }}%)
                                @endif
                            </strong></td>
                        <td class="right" style="background-color: #ADD8E6; border: 1px solid #000;"><strong>Rp {{ number_format($dpAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif

                @if ($hasDiscountOrDp)
                    <tr>
                        <td colspan="4" style="border: none; background-color: #fff;"></td>
                        <td class="right" style="background-color: #E6FFE6; border: 1px solid #000;"><strong>Tersisa</strong></td>
                        <td class="right" style="background-color: #E6FFE6; border: 1px solid #000;"><strong>Rp {{ number_format($remainingAmount, 0, ',', '.') }}</strong></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Terbilang -->
        <div class="terbilang">
            Terbilang : {{ ucwords(terbilang($totalAmount)) }} rupiah
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
            Demikian Invoice ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terima kasih.
        </div>

        <!-- Signature -->
        <table style="width: 100%; border: none; margin-top: 5px;">
            <tr>
                <td style="width: 50%; border: none; vertical-align: top; text-align: left;">
                    <div>Hormat Kami,</div>
                    <div style="font-weight: bold;">PT. AGHITSNA KARYA INDAH</div>
                    <div style="margin-top: 60px;">{{ $invoice->signed_by ?? 'Akhmad Khaidir' }}</div>
                    @if ($invoice->division)
                    <div style="margin-top: 5px;">{{ $invoice->division }}</div>
                    @endif
                </td>
                @if($invoice->isFullyPaid())
                <td style="width: 50%; border: none; vertical-align: top; text-align: center;">
                    <img src="{{ public_path('images/status_paid_alumunium.jpeg') }}" style="height: 100px;">
                </td>
                @endif
            </tr>
        </table>
    </div>
</body>

</html>
