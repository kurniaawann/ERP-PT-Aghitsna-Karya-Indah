<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengeluaran Divisi Produksi</title>
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

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .header-subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .header-period {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
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
            width: 4%;
            text-align: center;
        }

        .col-faktur {
            width: 18%;
        }

        .col-tanggal {
            width: 10%;
            text-align: center;
        }

        .col-keterangan {
            width: 28%;
        }

        .col-pemasukan {
            width: 13%;
            text-align: right;
        }

        .col-pengeluaran {
            width: 13%;
            text-align: right;
        }

        .col-sumber {
            width: 14%;
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
    <div class="header-title">PT. AGHITSNA KARYA INDAH</div>
    <div class="header-subtitle">LAPORAN PENGELUARAN DIVISI PRODUKSI</div>
    <div class="header-period">PERIODE {{ strtoupper($periodTitle) }}</div>

    <table>
        <thead>
            <tr>
                <th class="col-no">NO</th>
                <th class="col-faktur">FAKTUR</th>
                <th class="col-tanggal">TANGGAL</th>
                <th class="col-keterangan">KETERANGAN</th>
                <th class="col-pemasukan">PEMASUKAN</th>
                <th class="col-pengeluaran">PENGELUARAN</th>
                <th class="col-sumber">SUMBER UANG</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $categoryGroups = $expenseRecaps->groupBy('transaction_category_id');
            @endphp

            @foreach ($categoryGroups as $categoryId => $expenses)
                @php
                    $category = $expenses->first()->category;
                    $categoryIncome = 0;
                    $categoryExpense = 0;
                @endphp

                {{-- Category Header Row --}}
                <tr class="category-row">
                    <td colspan="4" class="category-header">{{ strtoupper($category->name ?? 'LAIN-LAIN') }}</td>
                    <td class="empty-cell"></td>
                    <td class="empty-cell"></td>
                    <td class="empty-cell"></td>
                </tr>

                {{-- Items in Category --}}
                @php $itemNo = 1; @endphp
                @foreach ($expenses as $expense)
                    @php
                        $categoryIncome += $expense->income_amount ?? 0;
                        $categoryExpense += $expense->expense_amount ?? 0;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $itemNo++ }}</td>
                        <td>{{ $expense->invoice_number ?? '' }}</td>
                        <td class="text-center">
                            {{ $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('d/m/Y') : '' }}
                        </td>
                        <td>{{ $expense->description ?? '' }}</td>
                        <td class="text-right">
                            {{ $expense->income_amount ? 'Rp ' . number_format($expense->income_amount, 0, ',', '.') : '' }}
                        </td>
                        <td class="text-right">
                            {{ $expense->expense_amount ? 'Rp ' . number_format($expense->expense_amount, 0, ',', '.') : '' }}
                        </td>
                        <td>{{ $expense->money_source ?? '' }}</td>
                    </tr>
                @endforeach

                {{-- Category Subtotal --}}
                <tr class="subtotal-row">
                    <td colspan="4"></td>
                    <td class="text-right">
                        {{ $categoryIncome > 0 ? number_format($categoryIncome, 0, ',', '.') : '0' }}</td>
                    <td class="text-right">
                        {{ $categoryExpense > 0 ? number_format($categoryExpense, 0, ',', '.') : '0' }}</td>
                    <td></td>
                </tr>
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
        <div style="margin-bottom: 10px;">Rekapitulasi Pengeluaran Divisi Produksi {{ $periodTitle }}</div>

        <table style="border: none; width: 50%;">
            <tr style="border: none;">
                <td style="border: none; width: 30px;">1.</td>
                <td style="border: none;">Uang Masuk</td>
                <td style="border: none; text-align: right;">
                    <strong>Rp {{ number_format($totals->total_income ?? 0, 0, ',', '.') }}</strong>
                </td>
                <td style="border: none; padding-left: 20px;"><strong>UANG MASUK</strong></td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;">2.</td>
                <td style="border: none;">Uang Keluar</td>
                <td style="border: none; text-align: right;">
                    <strong>Rp {{ number_format($totals->total_expense ?? 0, 0, ',', '.') }}</strong>
                </td>
                <td style="border: none; padding-left: 20px;"><strong>UANG KELUAR</strong></td>
            </tr>
            <tr style="border: none;">
                <td style="border: none;"></td>
                <td style="border: none;"><strong>Saldo</strong></td>
                <td style="border: none; text-align: right;">
                    <strong>Rp {{ number_format($totals->balance ?? 0, 0, ',', '.') }}</strong>
                </td>
                <td style="border: none; padding-left: 20px;"><strong>SALDO</strong></td>
            </tr>
        </table>
    </div>

    {{-- Footer Signatures --}}
    <div class="footer-signatures">
        <table>
            <tr>
                <td>
                    <div>Dibuat / Diperiksa</div>
                    <div class="signature-line">( A.Khuluqi )</div>
                </td>
                <td>
                    <div>&nbsp;</div>
                    <div class="signature-line"></div>
                </td>
                <td>
                    <div>Direktur PT. Aghitsna</div>
                    <div class="signature-line">( Zulkarnaen, ST )</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
