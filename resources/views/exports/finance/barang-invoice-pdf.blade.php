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
            padding: 20px;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
        }

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
            width: 70px;
        }

        .invoice-info td:nth-child(2) {
            width: 10px;
        }

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

        .description {
            margin: 15px 0;
            text-align: justify;
        }

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

        .total-row {
            background-color: #FFFF00;
            font-weight: bold;
        }

        .terbilang {
            font-style: italic;
            margin: 10px 0;
            font-size: 10px;
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
    </style>
</head>

<body>
    <div class="container">
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
                        Email: Design@aghitsna.id
                    </div>
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE ITEM</div>
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
                            <td>Kepada</td>
                            <td>:</td>
                            <td>{{ $invoice->recipient }}</td>
                        </tr>
                        <tr>
                            <td>Hal</td>
                            <td>:</td>
                            <td>{{ $invoice->regarding ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="recipient">
            <div class="recipient-label">Kepada Yth :</div>
            <div class="recipient-name">{{ $invoice->recipient }}</div>
        </div>

        <div class="description">
            Dengan ini kami sampaikan {{ $invoice->project_description }}
        </div>

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

                <tr class="total-row">
                    <td colspan="4" class="right">Jumlah</td>
                    <td class="right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="terbilang">Terbilang : {{ ucwords(terbilang($totalAmount)) }} rupiah</div>

        <div class="closing">
            Demikian invoice item ini kami buat atas perhatian dan kerjasamanya kami ucapkan terima kasih.
        </div>

        <table style="width: 100%; border: none; margin-top: 30px;">
            <tr>
                <td style="width: 50%; border: none; vertical-align: top; text-align: left;">
                    <p>Hormat Kami,</p>
                    <div class="signature-line">PT AGHITSNA KARYA INDAH</div>
                </td>
                <td style="width: 50%; border: none; vertical-align: top; text-align: center;">
                    <img src="{{ public_path('images/status_paid_proyek_and_item.jpeg') }}" style="height: 100px;">
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
