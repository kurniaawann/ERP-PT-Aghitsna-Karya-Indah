<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Semen</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .header-period {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid #000000;
        }

        th {
            background-color: #8ea9db;
            color: #000000;
            font-weight: bold;
            text-align: center;
            padding: 5px;
            font-size: 9pt;
        }

        td {
            padding: 4px 5px;
            font-size: 9pt;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .do-header {
            background-color: #d9e2f3;
            font-weight: bold;
        }

        .subtotal {
            background-color: #ffff99;
            font-weight: bold;
        }

        .grand-total {
            background-color: #e5c327;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="header-title">LAPORAN SEMEN</div>
    <div class="header-period">{{ $periodTitle }}</div>

    <table>
        <thead>
            <tr>
                <th rowspan="1">No DO / Baris</th>
                <th>Tanggal</th>
                <th>Nama Proyek</th>
                <th>Volume</th>
                <th>Satuan</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Total</th>
                <th>Tgl Lunas</th>
                <th>Harga Modal</th>
                <th>Profit</th>
            </tr>
        </thead>
        <tbody>
            @php
                $grandVolume = 0;
                $grandSubtotal = 0;
                $grandModal = 0;
                $grandProfit = 0;
            @endphp

            @foreach ($deliveryOrders as $do)
                @php
                    $grandVolume += $do->total_volume;
                    $grandSubtotal += $do->subtotal;
                    $grandModal += $do->harga_modal;
                    $grandProfit += $do->profit;
                @endphp

                {{-- Baris Header DO --}}
                <tr class="do-header">
                    <td>{{ $do->no }}</td>
                    <td>{{ $do->tanggal?->format('d M Y') ?: '-' }}
                        <div style="font-size:8pt;font-weight:normal;">
                            Datang: {{ $do->tanggal_datang?->format('d M Y') ?: '-' }}
                            · Bayar: {{ $do->tanggal_bayar?->format('d M Y') ?: '-' }}
                        </div>
                    </td>
                    <td>{{ $do->jumlah_baris }} baris · {{ number_format($do->total_volume, 0, ',', '.') }} zak</td>
                    <td class="text-center">-</td>
                    <td class="text-center">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right">Rp{{ number_format($do->subtotal, 0, ',', '.') }}</td>
                    <td class="text-right">-</td>
                    <td class="text-center">-</td>
                    <td class="text-right">Rp{{ number_format($do->harga_modal, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($do->profit, 0, ',', '.') }}</td>
                </tr>

                {{-- Baris Detail Data Semen --}}
                @forelse ($do->cements as $cement)
                    <tr>
                        <td>{{ $cement->no }}</td>
                        <td>{{ $cement->tanggal?->format('d M Y') ?: '-' }}</td>
                        <td>{{ $cement->nama_proyek }}</td>
                        <td class="text-center">{{ number_format($cement->jumlah, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $cement->satuan ?: 'zak' }}</td>
                        <td class="text-right">Rp{{ number_format($cement->harga, 0, ',', '.') }}</td>
                        <td class="text-right">Rp{{ number_format($cement->total, 0, ',', '.') }}</td>
                        <td class="text-center">-</td>
                        <td>{{ $cement->tanggal_lunas?->format('d M Y') ?: '-' }}</td>
                        <td class="text-center">-</td>
                        <td class="text-right">Rp{{ number_format($cement->profit, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">Tidak ada data semen dalam DO ini.</td>
                    </tr>
                @endforelse

                {{-- Subtotal DO --}}
                <tr class="subtotal">
                    <td>SUBTOTAL</td>
                    <td></td>
                    <td></td>
                    <td class="text-center">{{ number_format($do->total_volume, 0, ',', '.') }}</td>
                    <td class="text-center">zak</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">Rp{{ number_format($do->subtotal, 0, ',', '.') }}</td>
                    <td></td>
                    <td class="text-right">Rp{{ number_format($do->harga_modal, 0, ',', '.') }}</td>
                    <td class="text-right">Rp{{ number_format($do->profit, 0, ',', '.') }}</td>
                </tr>
            @endforeach

            {{-- Grand Total --}}
            <tr class="grand-total">
                <td>TOTAL</td>
                <td></td>
                <td></td>
                <td class="text-center">{{ number_format($grandVolume, 0, ',', '.') }}</td>
                <td class="text-center">zak</td>
                <td></td>
                <td></td>
                <td class="text-right">Rp{{ number_format($grandSubtotal, 0, ',', '.') }}</td>
                <td></td>
                <td class="text-right">Rp{{ number_format($grandModal, 0, ',', '.') }}</td>
                <td class="text-right">Rp{{ number_format($grandProfit, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

</body>

</html>
