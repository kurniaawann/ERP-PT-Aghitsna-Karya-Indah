<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 1cm 1cm 1cm 1cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
        }

        .invoice-container {
            width: 100%;
            max-width: 19cm;
            /* border: 2px solid #000; */
            padding: 8px;
            margin: 0 auto;
        }

        /* Header Section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }

        .header-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .header-left img {
            max-width: 330px;
            height: auto;
            display: block;
        }

        .header-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            text-align: right;
            padding-right: 5px;
        }

        .header-info {
            text-align: right;
            line-height: 1.4;
        }

        .location-date {
            font-size: 10px;
            margin-bottom: 8px;
        }

        .kepada-label {
            font-size: 10px;
            font-weight: normal;
            margin-bottom: 2px;
        }

        .kepada-name {
            font-size: 10px;
            font-weight: normal;
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 150px;
            padding-bottom: 1px;
        }

        /* Invoice Number Section */
        .invoice-numbers {
            margin-bottom: 8px;
        }

        .invoice-numbers table {
            width: 300px;
            border-collapse: collapse;
        }

        .invoice-numbers td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-size: 10px;
            font-weight: bold;
        }

        .invoice-numbers td:first-child {
            width: 90px;
            background-color: #f5f5f5;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 10px;
        }

        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            padding: 5px;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        /* Bank Information in Table */
        .bank-row td {
            border-left: none;
            border-right: 1px solid #000;
            border-bottom: none;
            border-top: none;
            font-size: 9px;
            padding: 2px 6px;
            font-weight: normal;
        }

        /* Bottom Section */
        .bottom-section {
            display: table;
            width: 100%;
            margin-top: 0;
        }

        .bottom-left {
            display: table-cell;
            width: 45%;
            vertical-align: top;
            padding-right: 10px;
            padding-top: 8px;
        }

        .period-section {
            font-size: 10px;
            margin-bottom: 15px;
        }

        .footer-note {
            font-size: 9px;
            line-height: 1.3;
        }

        .bottom-right {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            margin-left: auto;
        }

        .totals-table td {
            border: 1px solid #000;
            padding: 3px 8px;
            font-size: 10px;
        }

        .totals-table td:first-child {
            width: 55%;
            font-weight: normal;
        }

        .totals-table td:last-child {
            text-align: right;
            width: 45%;
        }

        .totals-table .total-row td {
            border-bottom: 2px solid #000;
            font-weight: bold;
        }

        .signature-section {
            text-align: left;
            margin-top: 20px;
            margin-left: 20px;
            font-size: 10px;
        }

        .signature-section strong {
            display: block;
            margin-bottom: 50px;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @foreach ($invoices as $index => $invoice)
        <div class="invoice-container">
            {{-- Header --}}
            <div class="header">
                <div class="header-left">
                    <img src="{{ public_path('images/invoice_administrasi.jpeg') }}" alt="PT. Aghitsna Karya Indah">
                </div>
                <div class="header-right">
                    <div class="header-info">
                        <div class="location-date">
                            <strong>{{ $invoice->location }}</strong>,
                            <em>{{ \Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat('d F Y') }}</em>
                        </div>
                        <div class="kepada-label">Kepada Yth,</div>
                        <div class="kepada-name">{{ $invoice->kepada }}</div>
                    </div>
                </div>
            </div>

            {{-- Invoice Numbers --}}
            <div class="invoice-numbers">
                <table>
                    <tr>
                        <td>FAKTUR No.</td>
                        <td>{{ $invoice->faktur_no }}</td>
                    </tr>
                    <tr>
                        <td>SJ No.</td>
                        <td>{{ $invoice->sj_no }}</td>
                    </tr>
                </table>
            </div>

            {{-- Items Table --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 15%">BANYAKNYA</th>
                        <th style="width: 40%">NAMA BARANG</th>
                        <th style="width: 22.5%">HARGA SATUAN</th>
                        <th style="width: 22.5%">JUMLAH</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td class="center">{{ $item['banyaknya'] }}</td>
                            <td>{{ $item['nama_barang'] }}</td>
                            <td class="right">{{ number_format($item['harga_satuan'], 0, ',', '.') }}</td>
                            <td class="right">{{ number_format($item['jumlah'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                    {{-- Bank Information Rows --}}
                    @php
                        $banks = $invoice->paymentAccounts();
                    @endphp

                    @if ($banks->count() > 0)
                        @foreach ($banks as $bank)
                            <tr class="bank-row">
                                <td colspan="4">
                                    <strong>{{ $bank->bank_name }}:</strong> {{ $bank->account_number }} a/n
                                    <strong>{{ $bank->account_holder }}</strong>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>

            {{-- Bottom Section --}}
            <div class="bottom-section">
                <div class="bottom-left">
                    @if ($invoice->period)
                        <div class="period-section">
                            <strong>Periode :</strong> {{ $invoice->period }}
                        </div>
                    @endif
                </div>
                <div class="bottom-right">
                    <table class="totals-table">
                        @php
                            $subtotal = 0;
                            foreach ($invoice->items as $item) {
                                $subtotal += $item['jumlah'];
                            }
                        @endphp

                        {{-- PPN Row --}}
                        @if ($invoice->ppn_percentage > 0)
                            <tr>
                                <td>PPN {{ number_format($invoice->ppn_percentage, 0) }}%</td>
                                <td>{{ number_format($invoice->ppn_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        {{-- Sewa/Jual --}}
                        @if ($invoice->sewa_jual)
                            <tr>
                                <td>Sewa / Jual</td>
                                <td>{{ number_format($invoice->sewa_jual, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        {{-- Ongkos Kirim --}}
                        @if ($invoice->ongkos_kirim)
                            <tr>
                                <td>Ongkos Kirim PP / 1x</td>
                                <td>{{ number_format($invoice->ongkos_kirim, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        {{-- Bongkar Pasang --}}
                        @if ($invoice->bongkar_pasang)
                            <tr>
                                <td>Bongkar / Pasang</td>
                                <td>{{ number_format($invoice->bongkar_pasang, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        {{-- Lembur --}}
                        @if ($invoice->lembur)
                            <tr>
                                <td>Lembur Antar / Ambil</td>
                                <td>{{ number_format($invoice->lembur, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        {{-- Uang Jaminan --}}
                        @if ($invoice->uang_jaminan)
                            <tr>
                                <td>Uang Jaminan</td>
                                <td>{{ number_format($invoice->uang_jaminan, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        {{-- Grand Total --}}
                        <tr class="total-row">
                            <td>Jumlah *)</td>
                            <td>{{ number_format($invoice->total_with_ppn, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Footer Note --}}
            <div class="footer-note" style="margin-top: 10px; margin-left: 20px;">
                <strong>*) Faktur dianggap lunas setelah dana kami terima</strong><br>
                <strong style="padding-left: 10px;">tunai atau telah ditransfer ke rekening kami.</strong>
            </div>

            {{-- Signature --}}
            <div class="signature-section">
                <strong style="margin-left: 60px;">Penerima,</strong>
                <div style="margin-top: 50px; margin-left: 30px;">
                    <strong>(__________________)</strong>
                </div>
            </div>
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
