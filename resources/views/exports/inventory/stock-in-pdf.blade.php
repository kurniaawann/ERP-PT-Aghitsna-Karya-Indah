<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Barang Masuk</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            background-color: #C8E6C9;
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
            background-color: #E8F5E9;
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
        <p>Laporan Barang Masuk</p>
    </div>

    <div class="print-date">
        Tanggal Cetak: {{ date('d M Y H:i') }}
    </div>

    <div class="header">
        BARANG MASUK
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ID Masuk</th>
                <th style="width: 10%;">Barang</th>
                <th style="width: 20%;">Nama Barang</th>
                <th style="width: 8%;">Jumlah</th>
                <th style="width: 15%;">Harga Modal</th>
                <th style="width: 15%;">Total</th>
                <th style="width: 12%;">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQty = 0;
                $totalCapital = 0;
            @endphp
            @foreach ($stockIns as $record)
                @php
                    $total = $record->quantity * $record->capital_price;
                    $totalQty += $record->quantity;
                    $totalCapital += $total;
                @endphp
                <tr>
                    <td class="text-center">{{ $record->id_stock_in }}</td>
                    <td class="text-center">{{ $record->id_item }}</td>
                    <td>{{ $record->item->name_item ?? '-' }}</td>
                    <td class="text-center">{{ $record->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($record->capital_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($total, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $record->tanggal->format('d M Y') }}</td>
                </tr>
            @endforeach
            <tr style="background-color: #E8F5E9; font-weight: bold;">
                <td colspan="3" class="text-right">TOTAL:</td>
                <td class="text-center">{{ $totalQty }}</td>
                <td colspan="1"></td>
                <td class="text-right">Rp {{ number_format($totalCapital, 0, ',', '.') }}</td>
                <td></td>
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
