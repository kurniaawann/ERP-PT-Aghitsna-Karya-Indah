<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Item - {{ $invoice->invoice_number }}</title>
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

        /* Stempel LUNAS di Paling Depan & Transparan */
        .stamp-lunas-overlay {
            position: fixed;
            top: 28%;
            left: 10%;
            width: 80%;
            max-width: 550px;
            opacity: 0.35; /* Tingkat transparan (0.3 - 0.4 agar teks dibelakangnya tetap terbaca) */
            transform: rotate(-25deg);
            -webkit-transform: rotate(-25deg);
            z-index: 9999; /* Memastikan berada di PALING DEPAN */
            pointer-events: none;
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

        .recipient-table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }

        .recipient-table td {
            border: none;
            padding: 2px 0;
            vertical-align: top;
        }

        .recipient-table .recipient-label-cell {
            font-weight: bold;
            width: 80px;
            white-space: nowrap;
        }

        .recipient-table .recipient-name-cell {
            padding-left: 5px;
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

        .terbilang {
            font-style: italic;
            margin: 5px 0;
            font-size: 10px;
        }

        .closing {
            margin: 5px 0;
            text-align: justify;
        }
    </style>
</head>

<body>

    <!-- Gambar Stempel Lunas di Lapisan Paling Depan -->
    @if($invoice->salesRecap?->status === 'Lunas')
        <img src="{{ public_path('images/status_paid_proyek_and_item.jpeg') }}" class="stamp-lunas-overlay" alt="LUNAS">
    @endif

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
                                <td valign="top">{{ $invoice->regarding ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Recipient -->
        <div class="recipient">
            <table class="recipient-table">
                <tr>
                    <td class="recipient-label-cell">Kepada Yth :</td>
                    <td class="recipient-name-cell">
                        <div>{{ $invoice->recipient }}</div>
                        @if (!empty($invoice->proyek))
                            <div>{{ $invoice->proyek }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- Description -->
        <div class="description">
            Dengan ini kami sampaikan invoice untuk proyek {{ $invoice->project_description }} sebagai berikut :
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 45%">Nama Item</th>
                    <th style="width: 10%">Qty</th>
                    <th style="width: 20%">Harga</th>
                    <th style="width: 20%">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $items = is_string($invoice->items) ? json_decode($invoice->items, true) : $invoice->items;
                    $totalAmount = 0;
                @endphp

                @foreach ($items as $index => $item)
                    @php
                        $quantity = (int) ($item['quantity'] ?? 0);
                        $sellingPrice = (int) ($item['selling_price'] ?? 0);
                        $jumlah = $quantity * $sellingPrice;
                        $totalAmount += $jumlah;
                    @endphp
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $item['name_item'] ?? '-' }}</td>
                        <td class="center">{{ $quantity }}</td>
                        <td class="right">Rp {{ number_format($sellingPrice, 0, ',', '.') }}</td>
                        <td class="right">Rp {{ number_format($jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="3" style="border: none; background-color: #fff;"></td>
                    <td class="right" style="background-color: #FFFF00; border: 1px solid #000;"><strong>Jumlah</strong></td>
                    <td class="right" style="background-color: #FFFF00; border: 1px solid #000;"><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="terbilang">Terbilang : {{ ucwords(terbilang($totalAmount)) }} rupiah</div>

        <div class="closing">Demikian invoice ini kami sampaikan atas perhatian dan kerjasamanya kami ucapkan terima kasih.</div>

        <table style="width: 100%; border: none; margin-top: 5px;">
            <tr>
                <td style="width: 50%; border: none; vertical-align: top; text-align: left;">
                    <div>Hormat Kami,</div>
                    <div>PT. AGHITSNA KARYA INDAH</div>
                    <div style="margin-top: {{ $invoice->signedBy?->signature_image ? '5px' : '60px' }};">
                        @if ($invoice->signedBy?->signature_image)
                            <img src="{{ storage_path('app/public/' . $invoice->signedBy->signature_image) }}"
                                alt="Tanda Tangan" style="max-height: 55px; max-width: 160px;">
                        @endif
                        <div style="font-weight: bold;">{{ $invoice->signedBy?->name ?? 'Akhmad Khaidir' }}</div>
                    </div>
                    @if ($invoice->division)
                    <div style="margin-top: 5px;">{{ $invoice->division->name }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>

</html>