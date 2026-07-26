<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Faktur Pembelian</title>
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

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        thead {
            background-color: #1F4E78;
            color: white;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            font-weight: bold;
            text-align: center;
        }

        td {
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .currency {
            text-align: right;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    {{-- ==================== Header ==================== --}}
    <div class="header">
        <h1>DAFTAR FAKTUR PEMBELIAN</h1>
        <p>PT Aghitsna Karya Indah</p>
        <p>Tanggal Cetak: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    {{-- ==================== Tabel Data ==================== --}}
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">NO</th>
                <th style="width: 9%;">TANGGAL</th>
                <th style="width: 15%;">NAMA MATERIAL</th>
                <th style="width: 13%;">NPWP</th>
                <th style="width: 13%;">KODE NOMOR SERI PAJAK</th>
                <th style="width: 13%;">NAMA BARANG</th>
                <th style="width: 10%;">HARGA JUAL</th>
                <th style="width: 10%;">PPN PAJAK</th>
                <th style="width: 13%;">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $index => $invoice)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $invoice->date->format('d/m/Y') }}</td>
                    <td>{{ $invoice->material_name }}</td>
                    <td class="text-center">{{ $invoice->npwp }}</td>
                    <td class="text-center">{{ $invoice->tax_number_code }}</td>
                    <td>{{ $invoice->item_name }}</td>
                    <td class="currency">Rp {{ number_format($invoice->selling_price, 0, ',', '.') }}</td>
                    <td class="currency">Rp {{ number_format($invoice->ppn_tax, 0, ',', '.') }}</td>
                    <td>{{ $invoice->notes ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data faktur pembelian</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ==================== Footer ==================== --}}
    <div class="footer">
        <p>Dokumen ini dicetak dari sistem ERP PT Aghitsna Karya Indah</p>
    </div>
</body>

</html>
