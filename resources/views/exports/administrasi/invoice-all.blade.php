<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        .invoice-container {
            width: 100%;
            border: 2px solid #000;
            padding: 10px;
            margin-bottom: 20px;
        }

        /* Header Section */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
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
            padding-right: 10px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2563a4;
            margin-bottom: 5px;
        }

        .company-tagline {
            font-size: 12px;
            color: #666;
            margin-bottom: 3px;
        }

        .company-detail {
            font-size: 16px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }

        .company-address {
            font-size: 9px;
            color: #333;
            line-height: 1.3;
        }

        .header-info {
            text-align: right;
        }

        .location-date {
            font-size: 11px;
            margin-bottom: 3px;
        }

        .kepada-label {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .kepada-name {
            font-size: 11px;
            font-weight: bold;
            text-decoration: underline;
        }

        /* Invoice Number Section */
        .invoice-numbers {
            margin-bottom: 10px;
        }

        .invoice-numbers table {
            width: 40%;
            border-collapse: collapse;
        }

        .invoice-numbers td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 10px;
        }

        .invoice-numbers td:first-child {
            font-weight: bold;
            width: 35%;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 10px;
        }

        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }

        .items-table td.center {
            text-align: center;
        }

        .items-table td.right {
            text-align: right;
        }

        /* Bank Information in Table */
        .bank-row td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: none;
            font-size: 10px;
            padding: 3px 6px;
        }

        /* Right Section with Totals */
        .bottom-section {
            display: table;
            width: 100%;
        }

        .bottom-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            font-size: 9px;
            padding-right: 10px;
        }

        .bottom-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            font-size: 10px;
        }

        .totals-table td:first-child {
            width: 50%;
        }

        .totals-table td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            font-size: 11px;
            font-weight: bold;
        }

        .footer-note {
            font-size: 9px;
            margin-top: 10px;
            font-style: italic;
        }

        .signature-section {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
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
                    <div class="company-name">Aghits</div>
                    <div class="company-tagline">DESIGN AND BUILT</div>
                    <div class="company-detail">PT. AGHITSNA KARYA INDAH</div>
                    <div class="company-address">
                        <strong>Scaffolding</strong><br>
                        <strong>Alat - Alat Kontruksi ,dll</strong><br>
                        JL. TANAH BARU RAYA PERTIWI RT 01/05, BEJI. DEPOK, JAWA BARAT<br>
                        Telp. 021-29034923 - 0812.9596.552 Email: zulkarnainmarzuki@yahoo.com
                    </div>
                </div>
                <div class="header-right">
                    <div class="header-info">
                        <div class="location-date">
                            <strong>{{ $invoice->location }}</strong>,
                            {{ \Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat('d F Y') }}
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
                        <th style="width: 12%">BANYAKNYA</th>
                        <th style="width: 48%">NAMA BARANG</th>
                        <th style="width: 20%">HARGA SATUAN</th>
                        <th style="width: 20%">JUMLAH</th>
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
                                <td colspan="4" style="border-bottom: {{ $loop->last ? '1px solid #000' : 'none' }};">
                                    <strong>{{ $bank->bank_name }}:</strong> {{ $bank->account_number }}
                                    a/n <strong>{{ $bank->account_holder }}</strong>
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    {{-- Receiver Info --}}
                    @if ($invoice->penerima)
                        <tr class="bank-row">
                            <td colspan="4" style="border-bottom: 1px solid #000; padding-top: 8px;">
                                <strong>Penerima:</strong> {{ $invoice->penerima }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            {{-- Bottom Section --}}
            <div class="bottom-section">
                <div class="bottom-left">
                    <div class="footer-note">
                        <strong>*) Faktur dianggap lunas setelah dana kami terima</strong><br>
                        <strong>tunai atau telah ditransfer ke rekening kami.</strong>
                    </div>
                </div>
                <div class="bottom-right">
                    <table class="totals-table">
                        @if ($invoice->sewa_jual)
                            <tr>
                                <td>Sewa / Jual</td>
                                <td>{{ number_format($invoice->sewa_jual, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        @if ($invoice->ongkos_kirim)
                            <tr>
                                <td>Ongkos Kirim PP / 1x</td>
                                <td>{{ number_format($invoice->ongkos_kirim, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        @if ($invoice->bongkar_pasang)
                            <tr>
                                <td>Bongkar / Pasang</td>
                                <td>{{ number_format($invoice->bongkar_pasang, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        @if ($invoice->lembur)
                            <tr>
                                <td>Lembur Antar / Ambil</td>
                                <td>{{ number_format($invoice->lembur, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        @if ($invoice->uang_jaminan)
                            <tr>
                                <td>Uang Jaminan</td>
                                <td>{{ number_format($invoice->uang_jaminan, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        {{-- Subtotal before PPN --}}
                        <tr>
                            <td style="border-bottom: 2px solid #000;"><strong>Sub Total</strong></td>
                            <td style="border-bottom: 2px solid #000;">
                                <strong>{{ number_format($invoice->jumlah_total, 0, ',', '.') }}</strong>
                            </td>
                        </tr>

                        {{-- PPN --}}
                        @if ($invoice->ppn_percentage > 0)
                            <tr>
                                <td>PPN {{ number_format($invoice->ppn_percentage, 0) }}%</td>
                                <td>{{ number_format($invoice->ppn_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endif

                        {{-- Grand Total --}}
                        <tr class="grand-total">
                            <td>Jumlah *)</td>
                            <td>{{ number_format($invoice->total_with_ppn, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Signature --}}
            <div class="signature-section">
                <strong>Penerima,</strong>
                <div style="margin-top: 50px;">
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
