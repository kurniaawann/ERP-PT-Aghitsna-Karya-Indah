<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Profit Penjualan Divisi Produksi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9px;
            padding: 15px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 4px 6px;
            text-align: left;
        }

        th {
            background-color: #FFFF00;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            vertical-align: middle;
        }

        .vertical-center {
            vertical-align: middle;
        }

        /* Ensure table headers repeat on each page */
        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        /* Prevent page breaks inside table rows */
        tr {
            page-break-inside: avoid;
        }

        /* Style untuk merged cell - hide konten jika bukan first item */
        .hide-content {
            color: transparent;
            font-size: 0;
            line-height: 0;
        }

        /* Hide top border untuk merged cells (bukan first item) */
        .border-top-none {
            border-top: none;
        }

        /* Hide bottom border untuk merged cells (bukan last item) */
        .border-bottom-none {
            border-bottom: none;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .subtotal-row {
            background-color: #FFC000;
            font-weight: bold;
        }

        .total-row {
            background-color: #FFFF00;
            font-weight: bold;
        }

        .footer-info {
            margin-top: 15px;
            font-weight: bold;
            text-align: center;
        }

        .footer-info table {
            width: auto;
            border: none;
            margin: 0 auto;
        }

        .footer-info td {
            border: none;
            padding: 2px 10px 2px 0;
        }

        .col-no {
            width: 3%;
            text-align: center;
        }

        .col-date {
            width: 7%;
            text-align: center;
        }

        .col-project {
            width: 12%;
            text-align: center;
        }

        .col-item {
            width: 15%;
            text-align: center;
        }

        .col-qty {
            width: 4%;
            text-align: center;
        }

        .col-hpp {
            width: 13%;
            text-align: center;
        }

        .col-selling {
            width: 11%;
            text-align: center;
        }

        .col-profit {
            width: 11%;
            text-align: center;
        }

        .col-status {
            width: 13%;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="title">LAPORAN PROFIT PENJUALAN DIVISI PRODUKSI</div>
    <div class="subtitle">BULAN {{ strtoupper($monthYear) }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-no">NO</th>
                <th class="col-date">TANGGAL</th>
                <th class="col-project">PROYEK</th>
                <th class="col-item">NAMA BARANG</th>
                <th class="col-qty">QTY</th>
                <th class="col-hpp">HPP (HARGA MODAL )</th>
                <th class="col-selling">HARGA JUAL</th>
                <th class="col-profit">Jumlah & PROFIT</th>
                <th class="col-status">SUMBER UANG</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
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
                @endphp

                @foreach ($projectSales as $saleIndex => $sale)
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

                            // Tentukan apakah ini first/last item dalam sale
                            $isFirstInSale = $itemIndex === 0;
                            $isLastInSale = $itemIndex === $itemCount - 1;

                            // Tentukan apakah ini first/last item dalam project
                            $isFirstInProject = $projectItemCounter === 0;
                            $isLastInProject = $projectItemCounter === $totalItemsInProject - 1;

                            $projectItemCounter++;
                        @endphp

                        <tr>
                            <!-- NO Column: Tampilkan hanya di first item dalam sale, sisanya hide dengan border manipulation -->
                            <td
                                class="text-center vertical-center {{ !$isFirstInSale ? 'border-top-none' : '' }} {{ !$isLastInSale ? 'border-bottom-none' : '' }}">
                                <span class="{{ !$isFirstInSale ? 'hide-content' : '' }}">{{ $no }}</span>
                            </td>

                            <!-- TANGGAL Column: Tampilkan hanya di first item dalam sale -->
                            <td
                                class="text-center vertical-center {{ !$isFirstInSale ? 'border-top-none' : '' }} {{ !$isLastInSale ? 'border-bottom-none' : '' }}">
                                <span
                                    class="{{ !$isFirstInSale ? 'hide-content' : '' }}">{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</span>
                            </td>

                            <!-- PROYEK Column: Tampilkan hanya di first item dalam project -->
                            <td
                                class="vertical-center {{ !$isFirstInProject ? 'border-top-none' : '' }} {{ !$isLastInProject ? 'border-bottom-none' : '' }}">
                                <span
                                    class="{{ !$isFirstInProject ? 'hide-content' : '' }}">{{ strtoupper($projectName ?: '-') }}</span>
                            </td>

                            <td>{{ $item['name_item'] ?? '-' }}</td>
                            <td class="text-center">{{ $qty }}</td>
                            <td class="text-right">Rp {{ number_format($capital, 0, ',', '.') }} | Rp
                                {{ number_format($totalCapital, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($selling, 0, ',', '.') }} | Rp
                                {{ number_format($totalSelling, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($profit, 0, ',', '.') }}</td>

                            <!-- STATUS Column: Tampilkan hanya di first item dalam project -->
                            <td
                                class="text-center vertical-center {{ !$isFirstInProject ? 'border-top-none' : '' }} {{ !$isLastInProject ? 'border-bottom-none' : '' }}">
                                <span
                                    class="{{ !$isFirstInProject ? 'hide-content' : '' }}">{{ strtoupper($sale->status) }}</span>
                            </td>
                        </tr>
                    @endforeach
                    @php $no++; @endphp
                @endforeach

                <!-- Project Subtotal -->
                <tr class="subtotal-row">
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td></td>
                    <td></td>
                    <td class="text-center"></td>
                    <td class="text-right">Rp {{ number_format($projectTotalCapital, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($projectTotalSelling, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($projectTotalProfit, 0, ',', '.') }}</td>
                    <td></td>
                </tr>

                @php
                    $totalCapitalAll += $projectTotalCapital;
                    $totalSellingAll += $projectTotalSelling;
                    $totalProfitAll += $projectTotalProfit;
                @endphp
            @endforeach

            <!-- Grand Total -->
            <tr class="total-row">
                <td colspan="5" class="text-center" style="font-weight: bold;">TOTAL PENJUALAN PROFIT</td>
                <td class="text-right">Rp {{ number_format($totalCapitalAll, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalSellingAll, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalProfitAll, 0, ',', '.') }}</td>
                <td class="text-center" style="background-color: white; border: none;"></td>
            </tr>
        </tbody>
    </table>

    <div class="footer-info">
        <table>
            <tr>
                <td>Modal Aghitsna</td>
                <td>Rp {{ number_format($totalCapitalAll, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Modal Divisi Holo</td>
                <td>Rp {{ number_format($totalSellingAll, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>PROFIT</td>
                <td>Rp {{ number_format($totalProfitAll, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
</body>

</html>
