<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengeluaran</title>
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
            text-transform: uppercase;
        }

        table {
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
        .col-faktur { width: 14%; }
        .col-tanggal { width: 10%; }
        .col-keterangan { width: 28%; }
        .col-pemasukan { width: 13%; }
        .col-pengeluaran { width: 13%; }
        .col-sumber { width: 18%; }

        .currency-cell {
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        /* Category header row */
        .category-row td {
            background-color: #A9D08E;
            font-weight: bold;
            text-align: center;
            font-size: 9.5px;
            padding: 5px;
        }

        .category-row td.empty-cell {
            background-color: #fff;
        }

        /* Category subtotal row */
        .subtotal-row td {
            background-color: #E2AD28;
            font-weight: bold;
            font-style: italic;
        }

        .subtotal-row td.subtotal-label {
            text-align: right;
            padding-right: 10px;
        }

        /* Grand total row */
        .grand-total-row td {
            background-color: #E5C327;
            font-weight: bold;
        }

        /* Rekapitulasi section */
        .rekapitulasi {
            margin-top: 15px;
            margin-bottom: 10px;
        }

        .rekapitulasi-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 5px;
        }

        .rekapitulasi table {
            width: 50%;
            border: none;
        }

        .rekapitulasi table td {
            border: none;
            padding: 2px 5px;
            font-size: 9.5px;
        }

        .rekapitulasi .rekap-bold {
            font-weight: bold;
        }

        /* Footer signatures */
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
        <div class="title">LAPORAN PENGELUARAN DIVISI PRODUKSI</div>
        <div class="subtitle">{{ $periodTitle }}</div>
    </div>

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

        <tfoot>
            <tr>
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
            @php
                $no = 1;
                $categoryGroups = $expenseRecaps->groupBy('transaction_category_id')
                    ->sortBy(function ($expenses) {
                        return $expenses->first()->category->sort_order ?? 999;
                    });
            @endphp

            @foreach ($categoryGroups as $categoryId => $expenses)
                @php
                    $category = $expenses->first()->category;
                    $categoryIncome = 0;
                    $categoryExpense = 0;
                @endphp

                {{-- Category Header Row --}}
                <tr class="category-row">
                    <td colspan="4">{{ strtoupper($category->name ?? 'LAIN-LAIN') }}</td>
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
                            @if ($expense->income_amount > 0)
                                <div class="currency-cell">
                                    <span>Rp</span>
                                    <span>{{ number_format($expense->income_amount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </td>
                        <td class="text-right">
                            @if ($expense->expense_amount > 0)
                                <div class="currency-cell">
                                    <span>Rp</span>
                                    <span>{{ number_format($expense->expense_amount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </td>
                        <td>{{ $expense->money_source ?? '' }}</td>
                    </tr>
                @endforeach

                {{-- Category Subtotal --}}
                <tr class="subtotal-row">
                    <td colspan="4" class="subtotal-label">SUB TOTAL</td>
                    <td class="text-right">
                        <div class="currency-cell">
                            <span>Rp</span>
                            <span>{{ number_format($categoryIncome, 0, ',', '.') }}</span>
                        </div>
                    </td>
                    <td class="text-right">
                        <div class="currency-cell">
                            <span>Rp</span>
                            <span>{{ number_format($categoryExpense, 0, ',', '.') }}</span>
                        </div>
                    </td>
                    <td></td>
                </tr>
            @endforeach

            {{-- Grand Total --}}
            <tr class="grand-total-row">
                <td colspan="4" class="text-center">JUMLAH</td>
                <td class="text-right">
                    <div class="currency-cell">
                        <span>Rp</span>
                        <span>{{ number_format($totals->total_income, 0, ',', '.') }}</span>
                    </div>
                </td>
                <td class="text-right">
                    <div class="currency-cell">
                        <span>Rp</span>
                        <span>{{ number_format($totals->total_expense, 0, ',', '.') }}</span>
                    </div>
                </td>
                <td class="text-right">
                    <div class="currency-cell">
                        <span>Rp</span>
                        <span>{{ number_format($totals->balance, 0, ',', '.') }}</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Rekapitulasi --}}
    <div class="rekapitulasi">
        <div class="rekapitulasi-title">Rekapitulasi Pengeluaran Divisi Produksi {{ $periodTitle }}</div>
        <table>
            <tr>
                <td style="width: 30px;">1.</td>
                <td>Uang Masuk</td>
                <td style="text-align: right;">
                    <strong>Rp {{ number_format($totals->total_income, 0, ',', '.') }}</strong>
                </td>
                <td style="padding-left: 20px;"><strong>UANG MASUK</strong></td>
            </tr>
            <tr>
                <td>2.</td>
                <td>Uang Keluar</td>
                <td style="text-align: right;">
                    <strong>Rp {{ number_format($totals->total_expense, 0, ',', '.') }}</strong>
                </td>
                <td style="padding-left: 20px;"><strong>UANG KELUAR</strong></td>
            </tr>
            <tr>
                <td></td>
                <td><strong>Saldo</strong></td>
                <td style="text-align: right;">
                    <strong>Rp {{ number_format($totals->balance, 0, ',', '.') }}</strong>
                </td>
                <td style="padding-left: 20px;"><strong>SALDO</strong></td>
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
