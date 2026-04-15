<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Pembelian - {{ $invoice->id }}</title>
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

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 10px;
            margin-bottom: 3px;
        }

        /* Content Section */
        .content {
            margin-bottom: 20px;
        }

        .content h2 {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 15px;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .info-label {
            display: table-cell;
            width: 150px;
            font-weight: bold;
        }

        .info-value {
            display: table-cell;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        thead {
            background-color: #d3d3d3;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            font-weight: bold;
            text-align: center;
            background-color: #1F4E78;
            color: white;
        }

        td {
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .currency {
            text-align: right;
        }

        /* Total Section */
        .total-section {
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .total-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .total-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
        }

        .total-table {
            width: 100%;
        }

        .total-table td {
            border: none;
            border-bottom: 1px solid #000;
            padding: 5px 10px;
            text-align: right;
        }

        .total-table td:first-child {
            text-align: left;
            border-bottom: 1px solid #000;
        }

        .total-label {
            font-weight: bold;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>FAKTUR PEMBELIAN</h1>
            <p>Nomor: {{ $invoice->id }}</p>
            <p>Tanggal: {{ $invoice->date->format('d/m/Y') }}</p>
        </div>

        <!-- Detail Information -->
        <div class="content">
            <h2>DETAIL FAKTUR</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">TANGGAL</th>
                        <th style="width: 20%;">NAMA MATERIAL</th>
                        <th style="width: 15%;">NPWP</th>
                        <th style="width: 15%;">KODE NOMOR SERI PAJAK</th>
                        <th style="width: 15%;">NAMA BARANG</th>
                        <th style="width: 12%;">HARGA JUAL</th>
                        <th style="width: 12%;">PPN PAJAK</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">{{ $invoice->date->format('d/m/Y') }}</td>
                        <td>{{ $invoice->material_name }}</td>
                        <td class="text-center">{{ $invoice->npwp }}</td>
                        <td class="text-center">{{ $invoice->tax_number_code }}</td>
                        <td>{{ $invoice->item_name }}</td>
                        <td class="currency">Rp {{ number_format($invoice->selling_price, 0, ',', '.') }}</td>
                        <td class="currency">Rp {{ number_format($invoice->ppn_tax, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            @if ($invoice->notes)
                <h2>KETERANGAN</h2>
                <p>{{ $invoice->notes }}</p>
            @endif
        </div>

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-right">
                <table class="total-table">
                    <tr>
                        <td class="total-label">Harga Jual:</td>
                        <td>Rp {{ number_format($invoice->selling_price, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="total-label">PPN Pajak:</td>
                        <td>Rp {{ number_format($invoice->ppn_tax, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="total-label" style="font-size: 12px;">TOTAL:</td>
                        <td style="font-weight: bold; font-size: 12px;">Rp
                            {{ number_format($invoice->selling_price + $invoice->ppn_tax, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Dokumen ini dicetak dari sistem ERP PT Aghitsna Karya Indah</p>
            <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>

</html>
