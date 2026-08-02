<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Barang Masuk</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: bold;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
        }

        .content {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #777;
            border-top: 1px solid #ccc;
            padding-top: 5px;
        }

        .info {
            margin-bottom: 20px;
        }

        .info td {
            border: none;
            padding: 2px 0;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    {{-- Header Laporan --}}
    <div class="header">
        <h1>PT AGHITSNA KARYA INDAH</h1>
        <p>Laporan Barang Masuk</p>
    </div>

    {{-- Info Cetak & Filter --}}
    <table class="info">
        <tr>
            <td style="width: 120px;"><strong>Tanggal Cetak</strong></td>
            <td>: {{ date('d M Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Filter Data</strong></td>
            <td>:
                @if (request('month') && request('year'))
                    Bulan {{ DateTime::createFromFormat('!m', request('month'))->format('F') }} {{ request('year') }}
                @elseif (request('year'))
                    Tahun {{ request('year') }}
                @else
                    Semua Data
                @endif
            </td>
        </tr>
    </table>

    {{-- Tabel Data Barang Masuk --}}
    <div class="content">
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 15%;">ID Masuk</th>
                    <th style="width: 25%;">Nama Barang</th>
                    <th style="width: 10%;">Jumlah</th>
                    <th style="width: 15%;">Harga Modal</th>
                    <th style="width: 15%;">Total</th>
                    <th style="width: 15%;">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalOverall = 0;
                    $totalQuantity = 0;
                @endphp
                @forelse($stockIns as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record->id_stock_in }}</td>
                        <td>{{ $record->item->name_item ?? '-' }}</td>
                        <td class="text-center">{{ $record->quantity }}</td>
                        <td class="text-right">Rp {{ number_format($record->capital_price, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($record->total_capital, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $record->date->format('d M Y') }}</td>
                    </tr>
                    @php
                        $totalOverall += $record->total_capital;
                        $totalQuantity += $record->quantity;
                    @endphp
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data untuk periode yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-right"><strong>Total Barang Masuk:</strong></td>
                    <td class="text-center"><strong>{{ number_format($totalQuantity, 0, ',', '.') }}</strong></td>
                </tr>
                <tr>
                    <td colspan="6" class="text-right"><strong>Total Keseluruhan:</strong></td>
                    <td class="text-center"><strong>Rp {{ number_format($totalOverall, 0, ',', '.') }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Footer Laporan --}}
    <div class="footer">
        Laporan ini dibuat secara otomatis oleh sistem ERP PT Aghitsna Karya Indah.
    </div>
</body>

</html>
