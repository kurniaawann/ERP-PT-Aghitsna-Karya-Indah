<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Delivery Order</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 15pt;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table,
        th,
        td {
            border: 1px solid #000000;
        }

        th {
            background-color: #8ea9db; /* Warna biru muda/periwinkle sesuai gambar */
            color: #000000;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            font-size: 11pt;
        }

        td {
            padding: 5px 6px;
            text-align: center;
            font-size: 11pt;
        }

        .month-header {
            color: #ff0000; /* Teks warna merah tebal sesuai gambar */
            font-weight: bold;
            text-align: center;
            font-size: 12pt;
            padding: 4px;
            background-color: #ffffff;
        }

        .col-no { width: 10%; }
        .col-tgl-do { width: 30%; }
        .col-tgl-datang { width: 30%; }
        .col-bayar { width: 30%; }
    </style>
</head>

<body>

    <div class="header-title">DELIVERY ORDER</div>

    <table>
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-tgl-do">Tanggal DO</th>
                <th class="col-tgl-datang">Tanggal Datang</th>
                <th class="col-bayar">Tanggal Bayar</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groupedDeliveryOrders as $month => $orders)
                <!-- Header Bulan Warna Merah -->
                <tr>
                    <td colspan="4" class="month-header">{{ strtoupper($month) }}</td>
                </tr>

                @forelse ($orders as $do)
                    <tr>
                        <td>{{ $do->no_urutan }}</td>
                        <td>{{ $do->tanggal?->format('d.m.Y') ?: '-' }}</td>
                        <td>{{ $do->tanggal_datang?->format('d.m.Y') ?: '-' }}</td>
                        <td>{{ $do->tanggal_bayar?->format('d.m.Y') ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Tidak ada data DO.</td>
                    </tr>
                @endforelse
            @endforeach
        </tbody>
    </table>

</body>

</html>