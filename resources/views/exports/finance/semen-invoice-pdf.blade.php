<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Semen - {{ $invoice->invoice_number }}</title>
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
            position: relative;
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
        }

        .description {
            margin: 5px 0;
            text-align: justify;
        }

        .project-block {
            margin: 8px 0;
        }

        .project-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }

        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #000;
            padding: 6px 5px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 10px;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        .subtotal-row td {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .grand-total td {
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
                        PT. AGHITSNA KARYA INDAH
                        JL. TANAH BARU RAYA PERTIWI RT. 01/05 BEJI, DEPOK, JAWA BARAT
                        Telp. 021 - 29034923 - 0812 9596 552
                        Email : Design@aghitsna.id
                    </div>
                </td>

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
                                <td valign="top">Penagihan Semen</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Recipient -->
        <div class="recipient">
            <div class="recipient-label">Kepada Yth :</div>
            <div>
                <div>PT. AGHITSNA KARYA INDAH</div>
            </div>
        </div>

        <!-- Description -->
        <div class="description">
            Dengan ini kami sampaikan invoice semen sebagai berikut :
        </div>

        @php
            $projects = is_string($invoice->projects) ? json_decode($invoice->projects, true) : $invoice->projects;
            $grandTotal = 0;
        @endphp

        @foreach ($projects as $project)
            @php
                $items = $project['items'] ?? [];
                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += (int) ($item['jumlah'] ?? 0);
                }
                $grandTotal += $subtotal;
            @endphp
            <div class="project-block">
                <div class="project-title">Proyek: {{ $project['nama_proyek'] ?? '-' }}</div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%">No</th>
                            <th style="width: 15%">Tanggal</th>
                            <th style="width: 40%">Nama Barang</th>
                            <th style="width: 10%">Qty</th>
                            <th style="width: 15%">Harga</th>
                            <th style="width: 15%">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td class="center">{{ $item['no'] ?? $loop->iteration }}</td>
                                <td class="center">{{ \Carbon\Carbon::parse($item['tanggal'] ?? null)->format('d-m-Y') }}</td>
                                <td>{{ $item['nama_barang'] ?? 'SEMEN' }}</td>
                                <td class="center">{{ $item['qty'] ?? 0 }}</td>
                                <td class="right">Rp {{ number_format((int) ($item['harga'] ?? 0), 0, ',', '.') }}</td>
                                <td class="right">Rp {{ number_format((int) ($item['jumlah'] ?? 0), 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="subtotal-row">
                            <td colspan="5" class="right">Subtotal</td>
                            <td class="right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>

                @php
                    $account = \App\Models\Finance\PaymentAccount::find($project['payment_account_id'] ?? null);
                @endphp
                @if ($account)
                    <div style="font-size: 10px; margin-top: 3px;">
                        Pembayaran proyek ini melalui: <strong>{{ $account->bank_name }}</strong> / No :
                        <strong>{{ $account->account_number }}</strong> a/n <strong>{{ $account->account_holder }}</strong>
                    </div>
                @endif
            </div>
        @endforeach

        <table class="items-table">
            <tbody>
                <tr class="grand-total">
                    <td colspan="5" class="right">Jumlah</td>
                    <td class="right">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="terbilang">Terbilang : {{ ucwords(terbilang($grandTotal)) }} rupiah</div>

        <div class="closing">Demikian invoice ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terima kasih.</div>

        <table style="width: 100%; border: none; margin-top: 5px;">
            <tr>
                <td style="width: 50%; border: none; vertical-align: top; text-align: left;">
                    <div>Hormat Kami,</div>
                    <div>PT. AGHITSNA KARYA INDAH</div>
                    <div style="margin-top: 60px; font-weight: bold;">Direktur</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>