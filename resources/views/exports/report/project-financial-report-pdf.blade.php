<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Proyek</title>
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

        /* Header Layout */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .logo-cell {
            width: 25%;
        }

        .logo-cell img {
            max-width: 150px;
            height: auto;
        }

        .header-title-cell {
            width: 50%;
            text-align: center;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .project-info {
            font-size: 11px;
            font-style: italic;
            color: #333;
            margin-top: 2px;
        }

        .header-date-cell {
            width: 25%;
            text-align: right;
            font-size: 9px;
            vertical-align: bottom;
            padding-bottom: 5px;
        }

        /* Main Data Table */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #7f7f7f;
            padding: 4px 5px;
            font-size: 9.5px;
        }

        table.data-table th {
            background-color: #FFC000; /* Kuning Amber Header */
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            height: 24px;
        }

        /* Category Header Row */
        .category-header-row td {
            border-top: 1px solid #7f7f7f;
            border-bottom: 1px solid #7f7f7f;
        }

        .category-title {
            background-color: #92D050; /* Hijau Kategori */
            font-weight: bold;
            font-style: italic;
            text-align: center;
        }

        /* Colors for Numbers */
        .text-blue {
            color: #002060;
            font-weight: bold;
        }

        .text-green {
            color: #548235;
        }

        .text-brown {
            color: #C65911;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* Subtotal Row */
        .subtotal-row {
            background-color: #FFC000; /* Kuning Subtotal */
            font-weight: bold;
            font-style: italic;
        }

        .subtotal-row td {
            text-decoration: underline;
        }

        /* Total Row */
        .total-row {
            background-color: #BFBFBF; /* Abu-abu Total */
            font-weight: bold;
            font-style: italic;
        }

        .total-row td {
            text-decoration: underline;
            padding: 6px 5px;
        }

        /* Summary Labels below table */
        .summary-labels-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 40px;
        }

        .summary-labels-table td {
            border: none;
            padding: 2px 5px;
            font-weight: bold;
            font-style: italic;
            font-size: 9.5px;
        }

        /* Column Widths */
        .col-no { width: 3%; }
        .col-bon { width: 4%; }
        .col-tanggal { width: 10%; }
        .col-keterangan { width: 35%; }
        .col-masuk { width: 12%; }
        .col-keluar { width: 11%; }
        .col-saldo { width: 11%; }
        .col-bon-ket { width: 14%; }

        /* Footer Signatures */
        .footer-signatures {
            width: 100%;
            margin-top: 30px;
        }

        .footer-signatures table {
            width: 100%;
            border: none;
        }

        .footer-signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            width: 33.33%;
            font-size: 9.5px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 60px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
            </td>
            <td class="header-title-cell">
                <div class="report-title">LAPORAN KEUANGAN</div>
                <div class="project-info">{{ $recap->project_name ?? 'Proyek Rumah Kos 4 Lantai' }}</div>
                @if (!empty($recap->location))
                    <div class="project-info">{{ $recap->location }}</div>
                @else
                    <div class="project-info">Jl. XYZ - Jakarta Selatan</div>
                @endif
            </td>
            <td class="header-date-cell">
                Tgl Edit Terakhir : {{ \Carbon\Carbon::now()->format('d F Y') }}
            </td>
        </tr>
    </table>

    {{-- Main Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-bon">Bon</th>
                <th class="col-tanggal">Tanggal</th>
                <th class="col-keterangan">Keterangan</th>
                <th class="col-masuk">Uang Masuk</th>
                <th class="col-keluar">Uang Keluar</th>
                <th class="col-saldo">Saldo</th>
                <th class="col-bon-ket">Keterangan Bon</th>
            </tr>
        </thead>
        <tbody>
            @php
                $catNo = 1;
                $runningBalance = 0;
                $itemsByCategory = $items->groupBy('transaction_category_id');
                $categories = $items->pluck('category')->filter()->unique('id');
            @endphp

            @foreach ($categories as $category)
                @php
                    $categoryItems = $itemsByCategory->get($category->id, collect());
                    $categoryIncome = 0;
                    $categoryExpense = 0;
                    $bonNo = 1;
                    $isFirstItem = true;
                @endphp

                {{-- Baris Judul Kategori (Warna Hijau) --}}
                <tr class="category-header-row">
                    <td colspan="4" class="category-title">{{ $category->name ?? 'Lain - lain' }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>

                {{-- Item Transaksi --}}
                @foreach ($categoryItems as $item)
                    @php
                        // Baris informasi (kasbon personal) hanya keterangan,
                        // tidak memengaruhi subtotal kategori maupun saldo berjalan.
                        $inc = $item->is_informational ? 0 : ($item->income_amount ?? 0);
                        $exp = $item->is_informational ? 0 : ($item->expense_amount ?? 0);
                        $categoryIncome += $inc;
                        $categoryExpense += $exp;
                        $runningBalance += ($inc - $exp);
                    @endphp
                    <tr>
                        <td class="text-center">{{ $isFirstItem ? $catNo : '' }}</td>
                        <td class="text-center text-blue">{{ $bonNo++ }}</td>
                        <td class="text-center">
                            {{ $item->transaction_date ? \Carbon\Carbon::parse($item->transaction_date)->format('d/m/Y') : '' }}
                        </td>
                        <td>
                            {{ $item->description ?? '' }}
                            @if ($item->is_informational)
                                <span style="font-style: italic; color: #555;">(informasi)</span>
                            @endif
                        </td>
                        <td class="text-right text-green">
                            {{ $inc ? number_format($inc, 0, ',', '.') : '' }}
                        </td>
                        <td class="text-right text-brown">
                            {{ $exp ? number_format($exp, 0, ',', '.') : '' }}
                        </td>
                        <td class="text-right">
                            {{ $item->is_informational ? '' : number_format($runningBalance, 0, ',', '.') }}
                        </td>
                        <td class="text-center">{{ $item->keterangan_bon ?? '' }}</td>
                    </tr>
                    @php $isFirstItem = false; @endphp
                @endforeach

                {{-- Subtotal Kategori (Warna Kuning) --}}
                <tr class="subtotal-row">
                    <td colspan="4"></td>
                    <td class="text-right text-green">
                        {{ $categoryIncome ? number_format($categoryIncome, 0, ',', '.') : '' }}
                    </td>
                    <td class="text-right text-brown">
                        {{ $categoryExpense ? number_format($categoryExpense, 0, ',', '.') : '' }}
                    </td>
                    <td class="text-right">
                        {{ number_format($runningBalance, 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>

                @php $catNo++; @endphp
            @endforeach

            {{-- Grand Total (Warna Abu-abu) --}}
            <tr class="total-row">
                <td colspan="4" class="text-center">Jumlah</td>
                <td class="text-right">
                    Rp. {{ number_format($totals->total_income ?? 0, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    Rp. {{ number_format($totals->total_expense ?? 0, 0, ',', '.') }}
                </td>
                <td class="text-right">
                    Rp. {{ number_format($totals->balance ?? $runningBalance, 0, ',', '.') }}
                </td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- Sub-label Keterangan Total di bawah kolom --}}
    <table class="summary-labels-table">
        <tr>
            <td class="col-no"></td>
            <td class="col-bon"></td>
            <td class="col-tanggal"></td>
            <td class="col-keterangan"></td>
            <td class="col-masuk text-center">Uang Masuk</td>
            <td class="col-keluar text-center">Uang Keluar</td>
            <td class="col-saldo text-center">Sisa Saldo</td>
            <td class="col-bon-ket"></td>
        </tr>
    </table>

    {{-- Footer Signatures --}}
    <div class="footer-signatures">
        <table>
            <tr>
                <td>
                    <div class="signature-title">MANDOR</div>
                    <div class="signature-name">Siswoyo</div>
                </td>
                <td>
                    <div class="signature-title">KABAG KEUANGAN</div>
                    <div class="signature-name">Kamila</div>
                </td>
                <td>
                    <div class="signature-title">DIREKTUR PT. AGHITSNA KARYA INDAH</div>
                    <div class="signature-name">Zulkarnain, S.T., M.T.</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>