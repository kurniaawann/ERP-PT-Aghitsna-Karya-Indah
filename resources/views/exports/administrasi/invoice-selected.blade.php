<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 10px;
            background: white;
        }

        .invoice-container {
            border: 2px solid #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #4a90a4;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }

        .info-section {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .label {
            font-weight: bold;
            width: 150px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table th,
        table td {
            border: 1px solid #333;
            padding: 8px;
            font-size: 11px;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-section {
            margin-top: 15px;
            font-size: 12px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .grand-total {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
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
                <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                <div class="title">INVOICE</div>
            </div>

            {{-- Invoice Info --}}
            <div class="info-section">
                <div class="info-row">
                    <div><span class="label">Lokasi:</span> {{ $invoice->location }}</div>
                    <div><span class="label">Tanggal:</span>
                        {{ \Carbon\Carbon::parse($invoice->invoice_date)->translatedFormat('d F Y') }}</div>
                </div>
                <div class="info-row">
                    <div><span class="label">Kepada Yth:</span> {{ $invoice->kepada }}</div>
                </div>
                <div class="info-row">
                    <div><span class="label">Faktur No:</span> {{ $invoice->faktur_no }}</div>
                    <div><span class="label">SJ.NO:</span> {{ $invoice->sj_no }}</div>
                </div>
            </div>

            {{-- Items Table --}}
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%">Banyaknya</th>
                        <th style="width: 50%">Nama Barang</th>
                        <th style="width: 20%">Harga Satuan</th>
                        <th style="width: 20%">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td class="text-center">{{ $item['banyaknya'] }}</td>
                            <td>{{ $item['nama_barang'] }}</td>
                            <td class="text-right">Rp {{ number_format($item['harga_satuan'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($item['jumlah'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Total Section --}}
            <div class="total-section">
                @if ($invoice->penerima)
                    <div class="total-row">
                        <span class="label">Penerima s/d:</span>
                        <span>{{ $invoice->penerima }}</span>
                    </div>
                @endif

                @if ($invoice->sewa_jual)
                    <div class="total-row">
                        <span class="label">Sewa / Jual:</span>
                        <span>Rp {{ number_format($invoice->sewa_jual, 0, ',', '.') }}</span>
                    </div>
                @endif

                @if ($invoice->ongkos_kirim)
                    <div class="total-row">
                        <span class="label">Ongkos Kirim PP / 1x:</span>
                        <span>Rp {{ number_format($invoice->ongkos_kirim, 0, ',', '.') }}</span>
                    </div>
                @endif

                @if ($invoice->bongkar_pasang)
                    <div class="total-row">
                        <span class="label">Bongkar / Pasang:</span>
                        <span>Rp {{ number_format($invoice->bongkar_pasang, 0, ',', '.') }}</span>
                    </div>
                @endif

                @if ($invoice->lembur)
                    <div class="total-row">
                        <span class="label">Lembur Antar / Ambil:</span>
                        <span>Rp {{ number_format($invoice->lembur, 0, ',', '.') }}</span>
                    </div>
                @endif

                @if ($invoice->uang_jaminan)
                    <div class="total-row">
                        <span class="label">Uang Jaminan:</span>
                        <span>Rp {{ number_format($invoice->uang_jaminan, 0, ',', '.') }}</span>
                    </div>
                @endif

                <div class="total-row grand-total">
                    <span class="label">JUMLAH TOTAL:</span>
                    <span>Rp {{ number_format($invoice->jumlah_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
