<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengembalian Barang</title>
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

        .summary {
            margin-top: 10px;
            padding: 5px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            width: 25%;
            float: right;
        }

        .summary table {
            width: 100%;
        }

        .summary td {
            border: none;
            padding: 4px 0;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .info {
            margin-bottom: 20px;
        }

        .info td {
            border: none;
            padding: 2px 0;
        }
    </style>
</head>

<body>
    {{-- Header Laporan --}}
    <div class="header">
        <h1>PT AGHITSNA KARYA INDAH</h1>
        <p>Laporan Pengembalian Barang</p>
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

    {{-- Tabel Data Pengembalian --}}
    <div class="content">
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 15%;">ID Return</th>
                    <th style="width: 25%;">Nama Barang</th>
                    <th style="width: 10%;">Jumlah</th>
                    <th style="width: 12%;">Tipe</th>
                    <th style="width: 18%;">Alasan</th>
                    <th style="width: 15%;">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $index => $record)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $record->id_return }}</td>
                        <td>{{ $record->item->name_item ?? '-' }}</td>
                        <td class="text-center">{{ $record->quantity }}</td>
                        <td class="text-center">{{ ucfirst($record->return_type) }}</td>
                        <td>{{ $record->reason ?? '-' }}</td>
                        <td class="text-center">{{ $record->date->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Tidak ada data untuk periode yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Ringkasan Total --}}
        <div class="clearfix">
            <div class="summary">
                <div class="summary-item">
                    <span>Total Kuantitas: </span>
                    <span class="text-right"><strong>{{ number_format($returns->sum('quantity'), 0, ',', '.') }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Laporan --}}
    <div class="footer">
        Laporan ini dibuat secara otomatis oleh sistem ERP PT Aghitsna Karya Indah.
    </div>
</body>

</html>
