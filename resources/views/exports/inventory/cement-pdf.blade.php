<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Data Semen</title>
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
    {{-- Header PDF --}}
    <div class="header">
        DATA SEMEN
    </div>

    {{-- Tabel Data Semen --}}
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Proyek</th>
                <th class="text-center">Jumlah</th>
                <th class="text-right">Harga</th>
                <th class="text-right">Total</th>
                <th>Tanggal Lunas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($cements as $cement)
                <tr>
                    <td>{{ $cement->no }}</td>
                    <td>{{ $cement->tanggal?->format('d M Y') ?: '-' }}</td>
                    <td>{{ $cement->nama_proyek }}</td>
                    <td class="text-center">{{ number_format($cement->jumlah, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($cement->harga, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($cement->total, 0, ',', '.') }}</td>
                    <td>{{ $cement->tanggal_lunas?->format('d M Y') ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
