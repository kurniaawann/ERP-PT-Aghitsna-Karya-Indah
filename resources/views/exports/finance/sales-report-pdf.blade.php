<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Profit Penjualan Divisi Produksi</title>
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

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 2px;
        }

        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 10px;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 10px;
        }

        table.report th,
        table.report td {
            border: 1px solid #000;
            padding: 3px 4px;
            font-size: 10pt;
            text-align: left;
            vertical-align: middle;
        }

        table.report th {
            background-color: #FFFF00;
            font-weight: bold;
            text-align: center;
        }

        /* Header tabel diulang di setiap halaman */
        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        /* Satu baris tidak terpotong antar halaman */
        tr {
            page-break-inside: avoid;
        }

        /* Sel "gabungan" (NO/TANGGAL per penjualan, PROYEK/SUMBER UANG per proyek):
           isi hanya di baris pertama, garis horizontal di tengah kelompok disembunyikan */
        table.report td.merged {
            vertical-align: top;
        }

        table.report td.border-top-none {
            border-top: none;
        }

        table.report td.border-bottom-none {
            border-bottom: none;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .nowrap {
            white-space: nowrap;
        }

        .subtotal-row td {
            background-color: #FFC000;
            font-weight: bold;
        }

        .total-row td {
            background-color: #FFFF00;
            font-weight: bold;
        }

        /* Ringkasan modal & profit di bawah tabel */
        .summary {
            margin: 14px auto 0 auto;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .summary td {
            border: none;
            padding: 2px 8px;
            font-size: 12pt;
            font-weight: bold;
        }

        .col-no { width: 3%; }
        .col-date { width: 7.5%; }
        .col-project { width: 14%; }
        .col-item { width: 16.5%; }
        .col-qty { width: 4%; }
        .col-unit { width: 9%; }
        .col-amount { width: 10%; }
        .col-profit { width: 9.5%; }
        .col-status { width: 7.5%; }
    </style>
</head>

<body>
    @php
        // Label periode: "TAHUN 2026" tidak diberi prefix "BULAN"
        $periodLabel = strtoupper($monthYear);
        $periodLabel = str_starts_with($periodLabel, 'TAHUN') ? $periodLabel : 'BULAN ' . $periodLabel;
        $rp = fn ($value) => 'Rp ' . number_format($value, 0, ',', '.');
    @endphp

    <div class="title">LAPORAN PROFIT PENJUALAN DIVISI PRODUKSI</div>
    <div class="subtitle">{{ $periodLabel }}</div>

    <table class="report">
        <colgroup>
            <col class="col-no">
            <col class="col-date">
            <col class="col-project">
            <col class="col-item">
            <col class="col-qty">
            <col class="col-unit">
            <col class="col-amount">
            <col class="col-unit">
            <col class="col-amount">
            <col class="col-profit">
            <col class="col-status">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2" class="col-no">NO</th>
                <th rowspan="2" class="col-date">TANGGAL</th>
                <th rowspan="2" class="col-project">PROYEK</th>
                <th rowspan="2" class="col-item">NAMA BARANG</th>
                <th rowspan="2" class="col-qty">QTY</th>
                <th colspan="2">HPP (HARGA MODAL)</th>
                <th colspan="2">HARGA JUAL</th>
                <th rowspan="2" class="col-profit">PROFIT</th>
                <th rowspan="2" class="col-status">SUMBER UANG</th>
            </tr>
            <tr>
                <th class="col-unit">SATUAN</th>
                <th class="col-amount">JUMLAH</th>
                <th class="col-unit">SATUAN</th>
                <th class="col-amount">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                // Urutan data sudah tanggal menaik dari query export; groupBy mempertahankan
                // urutan kemunculan pertama sehingga proyek terurut dari transaksi paling awal.
                $projectGroups = $salesRecaps->groupBy('name_proyek');
                $totalCapitalAll = 0;
                $totalSellingAll = 0;
                $totalProfitAll = 0;
            @endphp

            @foreach ($projectGroups as $projectName => $projectSales)
                @php
                    $projectTotalCapital = 0;
                    $projectTotalSelling = 0;
                    $projectTotalProfit = 0;
                    $projectItemCounter = 0;

                    // Hitung total items dalam project ini
                    $totalItemsInProject = 0;
                    foreach ($projectSales as $saleTemp) {
                        $itemsTemp = is_string($saleTemp->items)
                            ? json_decode($saleTemp->items, true)
                            : $saleTemp->items;
                        $totalItemsInProject += count($itemsTemp);
                    }
                    $projectStatus = strtoupper($projectSales->first()->status);
                @endphp

                @foreach ($projectSales as $sale)
                    @php
                        $items = is_string($sale->items) ? json_decode($sale->items, true) : $sale->items;
                        $itemCount = count($items);
                    @endphp

                    @foreach ($items as $itemIndex => $item)
                        @php
                            $qty = $item['quantity'] ?? 0;
                            $capital = $item['capital_price'] ?? 0;
                            $selling = $item['selling_price'] ?? 0;
                            $totalCapital = $capital * $qty;
                            $totalSelling = $selling * $qty;
                            $profit = $totalSelling - $totalCapital;

                            $projectTotalCapital += $totalCapital;
                            $projectTotalSelling += $totalSelling;
                            $projectTotalProfit += $profit;

                            // Posisi baris dalam penjualan (untuk NO/TANGGAL)
                            $isFirstInSale = $itemIndex === 0;
                            $isLastInSale = $itemIndex === $itemCount - 1;
                            $saleMerge = ($isFirstInSale ? '' : ' border-top-none') . ($isLastInSale ? '' : ' border-bottom-none');

                            // Posisi baris dalam proyek (untuk PROYEK/SUMBER UANG)
                            $isFirstInProject = $projectItemCounter === 0;
                            $isLastInProject = $projectItemCounter === $totalItemsInProject - 1;
                            $projectMerge = ($isFirstInProject ? '' : ' border-top-none') . ($isLastInProject ? '' : ' border-bottom-none');

                            $projectItemCounter++;
                        @endphp

                        <tr>
                            <td class="merged text-center{{ $saleMerge }}">{{ $isFirstInSale ? $no : '' }}</td>
                            <td class="merged text-center{{ $saleMerge }}">
                                {{ $isFirstInSale ? \Carbon\Carbon::parse($sale->date)->format('d/m/Y') : '' }}
                            </td>
                            <td class="merged{{ $projectMerge }}">{{ $isFirstInProject ? strtoupper($projectName ?: '-') : '' }}</td>
                            <td>{{ $item['name_item'] ?? '-' }}</td>
                            <td class="text-center">{{ $qty }}</td>
                            <td class="text-right nowrap">{{ $rp($capital) }}</td>
                            <td class="text-right nowrap">{{ $rp($totalCapital) }}</td>
                            <td class="text-right nowrap">{{ $rp($selling) }}</td>
                            <td class="text-right nowrap">{{ $rp($totalSelling) }}</td>
                            <td class="text-right nowrap">{{ $rp($profit) }}</td>
                            <td class="merged text-center{{ $projectMerge }}">{{ $isFirstInProject ? $projectStatus : '' }}</td>
                        </tr>
                    @endforeach
                    @php $no++; @endphp
                @endforeach

                {{-- Subtotal per proyek --}}
                <tr class="subtotal-row">
                    <td colspan="5" class="text-right">SUB TOTAL</td>
                    <td colspan="2" class="text-right nowrap">{{ $rp($projectTotalCapital) }}</td>
                    <td colspan="2" class="text-right nowrap">{{ $rp($projectTotalSelling) }}</td>
                    <td class="text-right nowrap">{{ $rp($projectTotalProfit) }}</td>
                    <td></td>
                </tr>

                @php
                    $totalCapitalAll += $projectTotalCapital;
                    $totalSellingAll += $projectTotalSelling;
                    $totalProfitAll += $projectTotalProfit;
                @endphp
            @endforeach

            {{-- Grand Total --}}
            <tr class="total-row">
                <td colspan="5" class="text-center">TOTAL PENJUALAN PROFIT</td>
                <td colspan="2" class="text-right nowrap">{{ $rp($totalCapitalAll) }}</td>
                <td colspan="2" class="text-right nowrap">{{ $rp($totalSellingAll) }}</td>
                <td class="text-right nowrap">{{ $rp($totalProfitAll) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td>Modal Aghitsna</td>
            <td class="text-right">{{ $rp($totalCapitalAll) }}</td>
        </tr>
        <tr>
            <td>Modal Divisi Holo</td>
            <td class="text-right">{{ $rp($totalSellingAll) }}</td>
        </tr>
        <tr>
            <td>PROFIT</td>
            <td class="text-right">{{ $rp($totalProfitAll) }}</td>
        </tr>
    </table>
</body>

</html>
