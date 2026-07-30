<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan</title>
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

        .title-container .title {
            font-size: 12px;
            text-transform: uppercase;
        }

        .title-container .subtitle {
            font-size: 11px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        /* Mengulang Header di setiap halaman baru */
        thead {
            display: table-header-group;
        }

        /* SOLUSI A: Memaksa Footer selalu muncul di BAGIAN PALING BAWAH KERTAS tiap halaman */
        tfoot {
            display: table-footer-group;
        }

        tfoot td.tfoot-border {
            border: none   ;
            border-top: 1px solid #333   ; /* Garis penutup paling bawah kertas */
            padding: 0   ;
            height: 0   ;
            line-height: 0   ;
            font-size: 0   ;
        }

        /* Menjaga agar 1 kelompok proyek tidak terpisah antar halaman */
        tbody.project-group {
            page-break-inside: avoid   ;
            break-inside: avoid   ;
        }

        /* Border standar untuk Kolom 4-9 (Nama Barang s/d Total) */
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

        /* STYLING KHUSUS KOLOM 1-3 (NO, TANGGAL, FAKTUR) */
        /* Hanya garis samping kiri & kanan, tanpa garis horizontal di tengah */
        td.col-merged {
            border-top: none   ;
            border-bottom: none   ;
            border-left: 1px solid #333   ;
            border-right: 1px solid #333   ;
        }

        /* Garis ATAS hanya untuk baris pertama kelompok */
        td.col-merged.is-first {
            border-top: 1px solid #333   ;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        .col-no { width: 3.5%; }
        .col-date { width: 9%; }
        .col-faktur { width: 22%; }
        .col-item { width: 20%; }
        .col-qty { width: 4.5%; }
        .col-harga-modal { width: 11%; }
        .col-harga-jual { width: 11%; }
        .col-jumlah { width: 9%; }
        .col-total { width: 10%; }

        .currency-cell {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            margin-top: 25px;
            width: 100%;
            page-break-inside: avoid   ;
        }

        .footer-signatures table {
            width: 100%;
            border: none;
            margin: 0;
        }

        .footer-signatures td {
            border: none   ;
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
        <div class="title">LAPORAN PENJUALAN LIST ORDER DIVISI PRODUKSI</div>
        <div class="subtitle">{{ $periodTitle }}</div>
    </div>

    <table>
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

        <!-- TFOOTER SOLUSI A: Membuat garis horizontal otomatis di paling bawah setiap halaman -->
        <tfoot>
            <tr>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td class="tfoot-border"></td>
                <td colspan="6" style="border: none; padding: 0; height: 0;"></td>
            </tr>
        </tfoot>

        @php $no = 1; @endphp

        @foreach ($projects as $project)
            @php
                $itemCounter = 0;
            @endphp

            <tbody class="project-group">
                @foreach ($project['sales_recaps'] as $sale)
                    @foreach ($sale['items'] as $itemIndex => $item)
                        @php
                            $isFirstRow = ($itemCounter === 0);
                            $itemCounter++;

                            $mergedClass = 'col-merged';
                            if ($isFirstRow) $mergedClass .= ' is-first';
                        @endphp
                        <tr>
                            <!-- Kolom 1: NO -->
                            <td class="text-center {{ $mergedClass }}">
                                {{ $isFirstRow ? $no . '.' : '' }}
                            </td>

                            <!-- Kolom 2: TANGGAL -->
                            <td class="text-center {{ $mergedClass }}">
                                {{ $isFirstRow ? $sale['date'] : '' }}
                            </td>

                            <!-- Kolom 3: NO FAKTUR & PROYEK -->
                            <td class="text-center {{ $mergedClass }}">
                                @if ($isFirstRow)
                                    <div>{{ $sale['no_faktur'] }}</div>
                                    <div class="font-bold">{{ $project['project_name'] }}</div>
                                @endif
                            </td>

                            <!-- Kolom 4: NAMA BARANG -->
                            <td class="text-left">{{ $item['name_item'] }}</td>

                            <!-- Kolom 5: QTY -->
                            <td class="text-center">{{ $item['qty'] }}</td>

                            <!-- Kolom 6: HARGA MODAL -->
                            <td>
                                <div class="currency-cell">
                                    <span>Rp</span>
                                    <span>{{ number_format($item['capital_price'], 0, ',', '.') }}</span>
                                </div>
                            </td>

                            <!-- Kolom 7: HARGA JUAL -->
                            <td>
                                <div class="currency-cell">
                                    <span>Rp</span>
                                    <span>{{ number_format($item['selling_price'], 0, ',', '.') }}</span>
                                </div>
                            </td>

                            <!-- Kolom 8: JUMLAH -->
                            <td>
                                <div class="currency-cell">
                                    <span>Rp</span>
                                    <span>{{ number_format($item['jumlah'], 0, ',', '.') }}</span>
                                </div>
                            </td>

                            <!-- Kolom 9: TOTAL -->
                            <td></td>
                        </tr>
                    @endforeach
                @endforeach

                <!-- Subtotal Row -->
                <tr class="subtotal-row">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td colspan="5" class="subtotal-label">
                        TOTAL
                        @if (($project['sales_recaps'][0]['status'] ?? '') === 'Lunas')
                            (Sudah Lunas {{ $project['lunas_date'] ?? '' }})
                        @endif
                    </td>
                    <td class="subtotal-val">
                        <div class="currency-cell">
                            <span>Rp</span>
                            <span>{{ number_format($project['subtotal'], 0, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>
            </tbody>

            @php $no++; @endphp
        @endforeach

        <tbody class="project-group">
            <tr class="grand-total-row">
                <td colspan="8" class="text-center">TOTAL PENJUALAN BELUM PROFIT</td>
                <td>
                    <div class="currency-cell">
                        <span>Rp</span>
                        <span>{{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

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