<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Barang Keluar</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            background-color: #FFCDD2;
            text-align: center;
            font-weight: bold;
            padding: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            border: 2px solid #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th {
            background-color: #FFEBEE;
            font-weight: bold;
            padding: 10px;
            text-align: center;
        }

        td {
            padding: 8px;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .company-info {
            margin-bottom: 20px;
            text-align: center;
        }

        .company-info h2 {
            margin: 0;
            font-size: 16px;
        }

        .company-info p {
            margin: 5px 0;
            font-size: 11px;
        }

        .print-date {
            text-align: right;
            margin-bottom: 10px;
            font-size: 11px;
        }
    </style>
</head>

<body>
    <div class="company-info">
        <h2>PT AGHITSNA KARYA INDAH</h2>
        <p>Laporan Barang Keluar</p>
    </div>

    <div class="print-date">
        Tanggal Cetak: {{ date('d M Y H:i') }}
    </div>

    <div class="header">
        BARANG KELUAR
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ID Keluar</th>
                <th style="width: 10%;">Barang</th>
                <th style="width: 20%;">Nama Barang</th>
                <th style="width: 8%;">Jumlah</th>
                <th style="width: 12%;">Sisa Barang</th>
                <th style="width: 12%;">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @php $totalQty = 0; @endphp
            @foreach ($stockOuts as $record)
                @php $totalQty += $record->quantity; @endphp
                <tr>
                    <td class="text-center">{{ $record->id_stock_out }}</td>
                    <td class="text-center">{{ $record->id_item }}</td>
                    <td>{{ $record->item->name_item ?? '-' }}</td>
                    <td class="text-center">{{ $record->quantity }}</td>
                    <td class="text-center">{{ $record->remaining_quantity ?? '-' }}</td>
                    <td class="text-center">{{ $record->tanggal->format('d M Y') }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #FFEBEE; font-weight: bold;">
                <td colspan="3" class="text-right">TOTAL:</td>
                <td class="text-center">{{ $totalQty }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right;">
        <p>Diketahui oleh,</p>
        <br>
        <br>
        <p>________________________</p>
        <p style="font-size: 11px; margin-top: -10px;">(Kepala Gudang)</p>
    </div>
</body>

</html>
