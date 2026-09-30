<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan</title>
    <style>
        /* Margin halaman diatur di @page; !important wajib karena reset "* { margin: 0 }" ikut menimpa margin halaman di dompdf */
        @page {
            margin: 12mm 10mm !important;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            color: #000;
        }

        .title-container {
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .title-container .title {
            font-size: 14pt;
        }

        .title-container .subtitle {
            font-size: 12pt;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 12px;
        }

        /* Mengulang Header di setiap halaman baru */
        thead {
            display: table-header-group;
        }

        /* Footer tabel selalu muncul di bagian paling bawah tiap halaman
           (garis penutup untuk kolom gabungan yang tidak punya garis bawah) */
        tfoot {
            display: table-footer-group;
        }

        table.report tfoot td.tfoot-border {
            border: none;
            border-top: 1px solid #000;
            padding: 0;
            height: 0;
            line-height: 0;
            font-size: 0;
        }

        /* Menjaga agar 1 kelompok proyek tidak terpisah antar halaman */
        tbody.project-group {
            page-break-inside: avoid;
        }

        tr {
            page-break-inside: avoid;
        }

        table.report th,
        table.report td {
            border: 1px solid #000;
            padding: 3px 5px;
            font-size: 11pt;
            vertical-align: middle;
        }

        table.report th {
            background-color: #9EA974;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        /* Kolom 1-3 (NO, TANGGAL, FAKTUR): hanya garis samping, garis atas di awal kelompok */
        table.report td.col-merged {
            border-top: none;
            border-bottom: none;
            vertical-align: top;
        }

        table.report td.col-merged.is-first {
            border-top: 1px solid #000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .nowrap { white-space: nowrap; }

        .col-no { width: 4%; }
        .col-date { width: 9%; }
        .col-faktur { width: 21%; }
        .col-item { width: 20%; }
        .col-qty { width: 5%; }
        .col-harga-modal { width: 10.5%; }
        .col-harga-jual { width: 10.5%; }
        .col-jumlah { width: 10%; }
        .col-total { width: 10%; }

        .faktur-no {
            font-size: 10pt;
        }

        .subtotal-row td.subtotal-label {
            background-color: #E2AD28;
            font-weight: bold;
            text-align: right;
            padding-right: 10px;
        }

        .subtotal-row td.subtotal-val {
            background-color: #E2AD28;
            font-weight: bold;
        }

        .grand-total-row td {
            background-color: #E5C327;
            font-weight: bold;
        }

        .footer-signatures {
            margin-top: 20px;
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
            padding: 0 6px;
            font-weight: bold;
            font-size: 12pt;
        }

        /* Tinggi judul tanda tangan disamakan (2 baris) agar nama sejajar */
        .signature-title {
            height: 29pt;
        }

        .signature-space {
            height: 45px;
        }
    </style>
</head>

<body>
    @php
        $rp = fn ($value) => 'Rp ' . number_format($value, 0, ',', '.');
    @endphp

    <div class="title-container">
        <div class="title">LAPORAN PENJUALAN LIST ORDER DIVISI PRODUKSI</div>
        <div class="subtitle">{{ $periodTitle }}</div>
    </div>

    <table class="report">
        <colgroup>
            <col class="col-no">
            <col class="col-date">
            <col class="col-faktur">
            <col class="col-item">
            <col class="col-qty">
            <col class="col-harga-modal">
            <col class="col-harga-jual">
            <col class="col-jumlah">
            <col class="col-total">
        </colgroup>
        <thead>
            <tr>
                <th class="col-no">NO</th>
                <th class="col-date">TANGGAL</th>
                <th class="col-faktur">NO FAKTUR & PROYEK</th>
                <th class="col-item">NAMA BARANG</th>
                <th class="col-qty">QTY</th>
                <th class="col-harga-modal">HARGA MODAL</th>
                <th class="col-harga-jual">HARGA JUAL</th>
                <th class="col-jumlah">JUMLAH</th>
                <th class="col-total">TOTAL</th>
            </tr>
        </thead>

        <tfoot>
            <tr>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td colspan="6" style="border: none; padding: 0; height: 0; line-height: 0; font-size: 0;"></td>
            </tr>
        </tfoot>

        @php $no = 1; @endphp

        {{-- Proyek & penjualan sudah terurut dari transaksi paling awal (tanggal menaik) --}}
        @foreach ($projects as $project)
            <tbody class="project-group">
                @foreach ($project['sales_recaps'] as $saleIndex => $sale)
                    @foreach ($sale['items'] as $itemIndex => $item)
                        @php
                            // NO digabung per proyek; TANGGAL & FAKTUR digabung per penjualan
                            $isFirstInProject = $saleIndex === 0 && $itemIndex === 0;
                            $isFirstInSale = $itemIndex === 0;
                        @endphp
                        <tr>
                            <td class="text-center col-merged {{ $isFirstInProject ? 'is-first' : '' }}">
                                {{ $isFirstInProject ? $no . '.' : '' }}
                            </td>

                            <td class="text-center col-merged {{ $isFirstInSale ? 'is-first' : '' }}">
                                {{ $isFirstInSale ? $sale['date'] : '' }}
                            </td>

                            <td class="text-center col-merged {{ $isFirstInSale ? 'is-first' : '' }}">
                                @if ($isFirstInSale)
                                    <div class="faktur-no">{{ $sale['no_faktur'] }}</div>
                                    @if ($saleIndex === 0)
                                        <div class="font-bold">{{ $project['project_name'] }}</div>
                                    @endif
                                @endif
                            </td>

                            <td class="text-left">{{ $item['name_item'] }}</td>
                            <td class="text-center">{{ $item['qty'] }}</td>
                            <td class="text-right nowrap">{{ $rp($item['capital_price']) }}</td>
                            <td class="text-right nowrap">{{ $rp($item['selling_price']) }}</td>
                            <td class="text-right nowrap">{{ $rp($item['jumlah']) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                @endforeach

                {{-- Subtotal per proyek --}}
                <tr class="subtotal-row">
                    <td class="col-merged"></td>
                    <td class="col-merged is-first"></td>
                    <td class="col-merged is-first"></td>
                    <td colspan="5" class="subtotal-label">
                        TOTAL
                        @if (($project['sales_recaps'][0]['status'] ?? '') === 'Lunas')
                            (Sudah Lunas {{ $project['lunas_date'] ?? '' }})
                        @endif
                    </td>
                    <td class="subtotal-val text-right nowrap">{{ $rp($project['subtotal']) }}</td>
                </tr>
            </tbody>

            @php $no++; @endphp
        @endforeach

        <tbody class="project-group">
            <tr class="grand-total-row">
                <td colspan="8" class="text-center">TOTAL PENJUALAN BELUM PROFIT</td>
                <td class="text-right nowrap">{{ $rp($grandTotal) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer-signatures">
        <table>
            <tr>
                <td style="width: 30%;">
                    <div class="signature-title">DIBUAT/DIPERIKSA</div>
                    <div class="signature-space"></div>
                    <div>( A. KHAIDIR )</div>
                </td>
                <td style="width: 30%;">
                    <div class="signature-title">KAB. KEUANGAN</div>
                    <div class="signature-space"></div>
                    <div>( Kamila,AMK )</div>
                </td>
                <td style="width: 40%;">
                    <div class="signature-title">MENGETAHUI,<br>DIREKTUR PT. AGHITSNA KARYA INDAH</div>
                    <div class="signature-space"></div>
                    <div>( Zulkarnain,ST.,MT )</div>
                </td>
            </tr>
        </table>
    </div>

</body>

</html>
