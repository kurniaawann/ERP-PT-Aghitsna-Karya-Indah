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
            font-family: 'Times New Roman', Times, serif;
            font-size: 9px;
            padding: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .logo-cell {
            width: 90px;
            vertical-align: middle;
        }

        .logo-cell img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .header-title-cell {
            text-align: center;
            vertical-align: middle;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .report-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .project-info {
            font-size: 11px;
            font-weight: bold;
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

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .category-row {
            font-weight: bold;
            text-align: center;
        }

        .category-row td {
            padding: 6px;
        }

        .category-row .category-header {
            background-color: #A9D08E;
            text-align: center;
            vertical-align: middle;
        }

        .category-row .empty-cell {
            background-color: white;
        }

        .subtotal-row {
            background-color: #FFCC00;
            font-style: italic;
        }

        .total-row {
            background-color: #FFCC00;
            font-weight: bold;
        }

        .rekapitulasi {
            margin-top: 20px;
            font-weight: bold;
            font-size: 11px;
        }

        .rekap-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 10px;
        }

        .rekap-label {
            font-weight: bold;
        }

        .rekap-value {
            font-weight: bold;
        }

        .col-no {
            width: 8%;
            text-align: center;
        }

        .col-bon {
            width: 8%;
            text-align: center;
        }

        .col-tanggal {
            width: 11%;
            text-align: center;
        }

        .col-keterangan {
            width: 34%;
        }

        .col-pemasukan {
            width: 14%;
            text-align: center;
        }

        .col-pengeluaran {
            width: 14%;
            text-align: center;
        }

        .col-keterangan-bon {
            width: 19%;
        }

        .footer-signatures {
            margin-top: 30px;
            width: 100%;
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
            padding: 5px;
            width: 33.33%;
        }

        .signature-line {
            margin-top: 60px;
        }
    </style>
</head>

<body>
    {{-- Header: logo kiri + judul di tengah + info proyek --}}
    <table class="header-table" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="Logo">
            </td>
            <td class="header-title-cell">
                <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
                <div class="report-title">LAPORAN KEUANGAN</div>
                <div class="project-info">{{ strtoupper($recap->project_name ?? '') }}</div>
                @if (! empty($recap->location))
                    <div class="project-info">{{ strtoupper($recap->location) }}</div>
                @endif
            </td>
            <td class="logo-cell"></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th class="col-no">NO</th>
                <th class="col-bon">BON</th>
                <th class="col-tanggal">TANGGAL</th>
                <th class="col-keterangan">KETERANGAN</th>
                <th class="col-pemasukan">UANG MASUK</th>
                <th class="col-pengeluaran">UANG KELUAR</th>
                <th class="col-keterangan-bon">KETERANGAN BON</th>
            </tr>
        </thead>
        <tbody>
            @php
                $catNo = 1;
                $itemsByCategory = $items->groupBy('transaction_category_id');
                $categories = $items->pluck('category')->filter()->unique('id');
            @endphp

            @foreach ($categories as $category)
                @php
                    $categoryItems = $itemsByCategory->get($category->id, collect());
                    $categoryIncome = 0;
                    $categoryExpense = 0;
                    $bonNo = 1;
                @endphp

                {{-- Category Header Row (hijau hanya NO, BON, TANGGAL, KETERANGAN) --}}
                <tr class="category-row">
                    <td class="category-header" rowspan="{{ $categoryItems->count() + 1 }}">{{ $catNo }}</td>
                    <td colspan="3" class="category-header">{{ strtoupper($category->name ?? 'LAIN-LAIN') }}</td>
                    <td class="empty-cell"></td>
                    <td class="empty-cell"></td>
                    <td class="empty-cell"></td>
                </tr>

                {{-- Items in Category --}}
                @foreach ($categoryItems as $item)
                    @php
                        $categoryIncome += $item->income_amount ?? 0;
                        $categoryExpense += $item->expense_amount ?? 0;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $bonNo++ }}</td>
                        <td class="text-center">
                            {{ $item->transaction_date ? \Carbon\Carbon::parse($item->transaction_date)->format('d/m/Y') : '' }}
                        </td>
                        <td class="text-center">{{ $item->description ?? '' }}</td>
                        <td class="text-right">
                            {{ $item->income_amount ? 'Rp ' . number_format($item->income_amount, 0, ',', '.') : '' }}
                        </td>
                        <td class="text-right">
                            {{ $item->expense_amount ? 'Rp ' . number_format($item->expense_amount, 0, ',', '.') : '' }}
                        </td>
                        <td>{{ $item->keterangan_bon ?? '' }}</td>
                    </tr>
                @endforeach

                {{-- Category Subtotal --}}
                <tr class="subtotal-row">
                    <td colspan="4"></td>
                    <td class="text-right">
                        {{ 'Rp ' . number_format($categoryIncome, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        {{ 'Rp ' . number_format($categoryExpense, 0, ',', '.') }}
                    </td>
                    <td></td>
                </tr>

                @php $catNo++; @endphp
            @endforeach

            {{-- Grand Total --}}
            <tr class="total-row">
                <td colspan="4" class="text-center"><strong>Jumlah</strong></td>
                <td class="text-right">
                    <strong>Rp {{ number_format($totals->total_income ?? 0, 0, ',', '.') }}</strong>
                </td>
                <td class="text-right">
                    <strong>Rp {{ number_format($totals->total_expense ?? 0, 0, ',', '.') }}</strong>
                </td>
                <td class="text-right">
                    <strong>Rp {{ number_format($totals->balance ?? 0, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Rekapitulasi --}}
    <div class="rekapitulasi">
        <div style="margin-bottom: 10px;">Rekapitulasi Laporan Keuangan {{ $recap->project_name ?? '' }}</div>

        <table style="border: none; width: 50%;">
            <tr style="border: none;">
                <td style="border: none; width: 30px;">1.</td>
                <td style="border: none;">UANG MASUK</td>
                <td style="border: none; text-align: right;">
                    <strong>Rp {{ number_format($totals->total_income ?? 0, 0, ',', '.') }}</strong>
                </td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;">2.</td>
                <td style="border: none;">UANG KELUAR</td>
                <td style="border: none; text-align: right;">
                    <strong>Rp {{ number_format($totals->total_expense ?? 0, 0, ',', '.') }}</strong>
                </td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none;"><strong>SALDO</strong></td>
                <td style="border: none; text-align: right;">
                    <strong>Rp {{ number_format($totals->balance ?? 0, 0, ',', '.') }}</strong>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer Signatures --}}
    <div class="footer-signatures">
        <table>
            <tr>
                <td>
                    <div>Dibuat / Diperiksa</div>
                    <div class="signature-line">( AKHMAD KHAIDIR )</div>
                </td>
                <td>
                    <div>&nbsp;</div>
                    <div class="signature-line"></div>
                </td>
                <td>
                    <div>Direktur PT. Aghitsna</div>
                    <div class="signature-line">( Zulkarnain,ST.,MT )</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
