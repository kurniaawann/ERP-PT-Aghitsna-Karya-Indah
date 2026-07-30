<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok Barang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            padding: 20px;
            color: #000;
        }

        .title-container {
            text-align: center;
            margin-bottom: 12px;
            font-weight: bold;
            line-height: 1.3;
        }

        .title-container .company {
            font-size: 13px;
            text-transform: uppercase;
        }

        .title-container .title {
            font-size: 12px;
            text-transform: uppercase;
        }

        .title-container .subtitle {
            font-size: 11px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 12px;
        }

        .info-table td {
            border: none;
            padding: 2px 5px;
            font-size: 10px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tfoot td.tfoot-border {
            border: none;
            border-top: 1px solid #333;
            padding: 0;
            height: 0;
            line-height: 0;
            font-size: 0;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 4px 5px;
            vertical-align: middle;
        }

        th {
            background-color: #9EA974;
            font-weight: bold;
            text-align: center;
            font-size: 9.5px;
            text-transform: uppercase;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        .col-no { width: 4%; }
        .col-id { width: 12%; }
        .col-name { width: 20%; }
        .col-beginning { width: 10%; }
        .col-in { width: 10%; }
        .col-out { width: 10%; }
        .col-return { width: 10%; }
        .col-ending { width: 10%; }
        .col-price { width: 12%; }
        .col-value { width: 12%; }

        .grand-total-row td {
            background-color: #E5C327;
            font-weight: bold;
        }

        .summary-section {
            margin-top: 10px;
            margin-bottom: 15px;
        }

        .summary-section .summary-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 5px;
        }

        .summary-table {
            width: 50%;
            border: none;
        }

        .summary-table td {
            border: none;
            padding: 2px 5px;
            font-size: 9.5px;
        }

        .summary-table .summary-bold {
            font-weight: bold;
        }

        .footer-signatures {
            margin-top: 25px;
            width: 100%;
            page-break-inside: avoid;
        }

        .footer-signatures table {
            width: 100%;
            border: none;
            margin: 0;
        }

        .footer-signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
            width: 33.33%;
            font-weight: bold;
            font-size: 9.5px;
        }

        .signature-space {
            height: 55px;
        }
    </style>
</head>

<body>

    <div class="title-container">
        <div class="company">PT. AGHITSNA KARYA INDAH</div>
        <div class="title">LAPORAN STOK BARANG</div>
        <div class="subtitle">PERIODE {{ $periodTitle }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 120px;"><strong>Tanggal Cetak</strong></td>
            <td>: {{ date('d M Y H:i') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="col-no">NO</th>
                <th class="col-id">ID BARANG</th>
                <th class="col-name">NAMA BARANG</th>
                <th class="col-beginning">STOK AWAL</th>
                <th class="col-in">MASUK</th>
                <th class="col-out">KELUAR</th>
                <th class="col-return">RETUR</th>
                <th class="col-ending">STOK AKHIR</th>
                <th class="col-price">HARGA SATUAN</th>
                <th class="col-value">NILAI STOK</th>
            </tr>
        </thead>

        <tfoot>
            <tr>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
            </tr>
        </tfoot>

        <tbody>
            @forelse($reportData as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $item['id_item'] }}</td>
                    <td>{{ $item['name_item'] }}</td>
                    <td class="text-right">{{ number_format($item['beginning_stock']) }}</td>
                    <td class="text-right">{{ number_format($item['stock_in']) }}</td>
                    <td class="text-right">{{ number_format($item['stock_out']) }}</td>
                    <td class="text-right">{{ number_format($item['returns']) }}</td>
                    <td class="text-right font-bold">{{ number_format($item['ending_stock']) }}</td>
                    <td class="text-right">Rp {{ number_format($item['capital_price'], 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($item['stock_value'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">Tidak ada data stok untuk periode ini</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Summary Section --}}
    <div class="summary-section">
        <div class="summary-title">Ringkasan Pergerakan Stok Periode {{ $periodTitle }}</div>
        <table class="summary-table">
            <tr>
                <td style="width: 30px;">1.</td>
                <td>Stok Awal</td>
                <td style="text-align: right;"><strong>{{ number_format($summary['total_beginning_stock']) }} unit</strong></td>
            </tr>
            <tr>
                <td>2.</td>
                <td>Stok Masuk</td>
                <td style="text-align: right;"><strong>{{ number_format($summary['total_stock_in']) }} unit</strong></td>
            </tr>
            <tr>
                <td>3.</td>
                <td>Stok Keluar</td>
                <td style="text-align: right;"><strong>{{ number_format($summary['total_stock_out']) }} unit</strong></td>
            </tr>
            <tr>
                <td>4.</td>
                <td>Retur Barang</td>
                <td style="text-align: right;"><strong>{{ number_format($summary['total_returns']) }} unit</strong></td>
            </tr>
            <tr>
                <td></td>
                <td><strong>Stok Akhir</strong></td>
                <td style="text-align: right;"><strong>{{ number_format($summary['total_ending_stock']) }} unit</strong></td>
            </tr>
            <tr>
                <td></td>
                <td><strong>Nilai Stok Total</strong></td>
                <td style="text-align: right;"><strong>Rp {{ number_format($summary['total_stock_value'], 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer-signatures">
        <table>
            <tr>
                <td>
                    <div>DIBUAT/DIPERIKSA</div>
                    <div class="signature-space"></div>
                    <div>( A. KHAIDIR )</div>
                </td>
                <td>
                    <div>KAB. KEUANGAN</div>
                    <div class="signature-space"></div>
                    <div>( KAMILA )</div>
                </td>
                <td>
                    <div>MENGETAHUI,<br>DIREKTUR PT. AGHITSNA KARYA INDAH</div>
                    <div class="signature-space"></div>
                    <div>( ZULKARNAIN, ST )</div>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
