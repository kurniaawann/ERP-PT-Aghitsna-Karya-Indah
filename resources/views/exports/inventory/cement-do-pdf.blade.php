<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>DO Semen</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11px;
        }

        .header {
            background-color: #FFFF00;
            text-align: center;
            font-weight: bold;
            padding: 10px;
            font-size: 14px;
            margin-bottom: 20px;
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

        th,
        td {
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    {{-- Header PDF --}}
    <div class="header">
        DO SEMEN
    </div>

    {{-- Tabel DO Semen --}}
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Proyek</th>
                <th class="text-center">Volume</th>
                <th class="text-center">Satuan</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Jumlah</th>
                <th>Tanggal Lunas</th>
                <th class="text-right">Harga Modal</th>
                <th class="text-right">Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cementDeliveryOrders as $cementDeliveryOrder)
                <tr>
                    <td>{{ $cementDeliveryOrder->no }}</td>
                    <td>{{ $cementDeliveryOrder->tanggal?->format('d M Y') ?: '-' }}</td>
                    <td>{{ $cementDeliveryOrder->proyek }}</td>
                    <td class="text-center">{{ number_format($cementDeliveryOrder->volume, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $cementDeliveryOrder->satuan ?: '-' }}</td>
                    <td class="text-right">Rp{{ number_format($cementDeliveryOrder->harga, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($cementDeliveryOrder->jumlah, 0, ',', '.') }}</td>
                    <td>{{ $cementDeliveryOrder->tanggal_lunas?->format('d M Y') ?: '-' }}</td>
                    <td class="text-right">Rp{{ number_format($cementDeliveryOrder->harga_modal, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($cementDeliveryOrder->profit, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
