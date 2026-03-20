<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Stock Hollow DI GI</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
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
            padding: 8px;
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
    <div class="header">
        STOCK HOLLOW DI GI
    </div>

    <table>
        <thead>
            <tr>
                <th>Nama Barang</th>
                <th class="text-center">Quantity</th>
                <th class="text-right">Harga Modal</th>
                <th class="text-right">Total</th>
                <th class="text-right">Harga Jual</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->name_item }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rp{{ number_format($item->capital_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($item->quantity * $item->capital_price, 0, ',', '.') }}
                    </td>
                    <td class="text-right">Rp{{ number_format($item->selling_price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
