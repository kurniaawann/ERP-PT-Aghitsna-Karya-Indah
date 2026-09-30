<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengeluaran</title>
    <style>
        /* Margin halaman diatur di @page; !important wajib karena reset "* { margin: 0 }" ikut menimpa margin halaman di dompdf */
        @page {
            margin: 10mm 12mm !important;
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

        /* Setiap bagian bulan (dan halaman rekap per bulan) dimulai di halaman baru */
        .page-break {
            page-break-before: always;
        }

        .title-container {
            text-align: center;
            margin-bottom: 8px;
            font-weight: bold;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .title-container .company {
            font-size: 14pt;
        }

        .title-container .title {
            font-size: 13pt;
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

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.report th,
        table.report td {
            border: 1px solid #000;
            padding: 2px 5px;
            font-size: 11pt;
            vertical-align: middle;
        }

        table.report th {
            background-color: #9EA974;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .nowrap { white-space: nowrap; }

        .col-no { width: 4%; }
        .col-faktur { width: 17%; }
        .col-tanggal { width: 9%; }
        .col-keterangan { width: 28%; }
        .col-pemasukan { width: 13%; }
        .col-pengeluaran { width: 13%; }
        .col-sumber { width: 16%; }

        /* Tabel rekap per bulan (halaman terakhir export multi-bulan) */
        .col-bulan { width: 24%; }
        .col-nominal { width: 18%; }

        /* Category header row */
        .category-row td {
            background-color: #A9D08E;
            font-weight: bold;
            text-align: center;
            padding: 3px 5px;
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
            margin-top: 8px;
            margin-bottom: 4px;
            page-break-inside: avoid;
        }

        .rekapitulasi-title {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 5px;
        }

        .rekapitulasi table {
            width: 55%;
            border: none;
            border-collapse: collapse;
        }

        .rekapitulasi table td {
            border: none;
            padding: 1px 5px;
            font-size: 12pt;
        }

        /* Footer signatures */
        .footer-signatures {
            margin-top: 12px;
            width: 100%;
            page-break-inside: avoid;
        }

        .footer-signatures table {
            width: 100%;
            border: none;
            border-collapse: collapse;
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
            height: 40px;
        }
    </style>
</head>

<body>
    @php
        $rp = fn ($value) => 'Rp ' . number_format($value ?? 0, 0, ',', '.');

        $allCategories = \App\Models\Report\TransactionCategory::where('created_by', auth()->id())
            ->module(\App\Models\Report\TransactionCategory::MODULE_EXPENSE_RECAP)
            ->active()->orderBy('sort_order')->get();

        // Laporan bulanan: export lintas bulan dipecah per bulan + halaman rekap per bulan.
        // Export satu bulan (atau tanpa data) tetap satu bagian seperti sebelumnya.
        $monthlySections = $monthlySections ?? [];
        $isMultiMonth = count($monthlySections) > 1;

        $pages = [];
        if ($isMultiMonth) {
            foreach ($monthlySections as $index => $section) {
                $pages[] = [
                    'type' => 'month',
                    'periodTitle' => 'BULAN ' . strtoupper($section['label']),
                    'records' => $section['records'],
                    'totals' => $section['totals'],
                    // Saldo bulan sebelumnya dibawa ke bulan berikutnya (mulai bulan ke-2)
                    'carry' => $index > 0 ? $section : null,
                ];
            }
            $pages[] = ['type' => 'summary', 'periodTitle' => $periodTitle, 'totals' => $totals];
        } else {
            $pages[] = [
                'type' => 'month',
                'periodTitle' => $periodTitle,
                'records' => $expenseRecaps,
                'totals' => $totals,
                'carry' => null,
            ];
        }
    @endphp

    @foreach ($pages as $pageIndex => $page)
        <div class="{{ $pageIndex > 0 ? 'page-break' : '' }}">
            <div class="title-container">
                <div class="company">PT. AGHITSNA KARYA INDAH</div>
                <div class="title">LAPORAN PENGELUARAN DIVISI PRODUKSI</div>
                @if ($page['type'] === 'summary')
                    <div class="subtitle">REKAPITULASI PER BULAN &mdash; {{ $page['periodTitle'] }}</div>
                @else
                    <div class="subtitle">{{ $page['periodTitle'] }}</div>
                @endif
            </div>

            @if ($page['type'] === 'month')
                <table class="report">
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
                            $expenseRecapsById = $page['records']->groupBy('transaction_category_id');
                        @endphp

                        @foreach ($allCategories as $category)
                            @php
                                $expenses = $expenseRecapsById->get($category->id, collect());
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
                                    <td class="text-center nowrap">
                                        {{ $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('d/m/Y') : '' }}
                                    </td>
                                    <td>{{ $expense->description ?? '' }}</td>
                                    <td class="text-right nowrap">
                                        {{ $expense->income_amount > 0 ? $rp($expense->income_amount) : '' }}
                                    </td>
                                    <td class="text-right nowrap">
                                        {{ $expense->expense_amount > 0 ? $rp($expense->expense_amount) : '' }}
                                    </td>
                                    <td>{{ $expense->money_source ?? '' }}</td>
                                </tr>
                            @endforeach

                            {{-- Baris kosong putih jika tidak ada data --}}
                            @if ($expenses->isEmpty())
                                <tr>
                                    <td>&nbsp;</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endif

                            {{-- Category Subtotal --}}
                            <tr class="subtotal-row">
                                <td colspan="4" class="subtotal-label">SUB TOTAL</td>
                                <td class="text-right nowrap">{{ $rp($categoryIncome) }}</td>
                                <td class="text-right nowrap">{{ $rp($categoryExpense) }}</td>
                                <td></td>
                            </tr>
                        @endforeach

                        {{-- Grand Total (per bulan untuk export multi-bulan) --}}
                        <tr class="grand-total-row">
                            <td colspan="4" class="text-center">JUMLAH</td>
                            <td class="text-right nowrap">{{ $rp($page['totals']->total_income) }}</td>
                            <td class="text-right nowrap">{{ $rp($page['totals']->total_expense) }}</td>
                            <td class="text-right nowrap">{{ $rp($page['totals']->balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                {{-- Rekap per bulan: total tiap bulan + saldo kumulatif --}}
                <table class="report">
                    <thead>
                        <tr>
                            <th class="col-no">NO</th>
                            <th class="col-bulan">BULAN</th>
                            <th class="col-nominal">UANG MASUK</th>
                            <th class="col-nominal">UANG KELUAR</th>
                            <th class="col-nominal">SALDO BULAN INI</th>
                            <th class="col-nominal">SALDO KUMULATIF</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthlySections as $index => $section)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ strtoupper($section['label']) }}</td>
                                <td class="text-right nowrap">{{ $rp($section['totals']->total_income) }}</td>
                                <td class="text-right nowrap">{{ $rp($section['totals']->total_expense) }}</td>
                                <td class="text-right nowrap">{{ $rp($section['totals']->balance) }}</td>
                                <td class="text-right nowrap">{{ $rp($section['closing_balance']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="grand-total-row">
                            <td colspan="2" class="text-center">JUMLAH</td>
                            <td class="text-right nowrap">{{ $rp($page['totals']->total_income) }}</td>
                            <td class="text-right nowrap">{{ $rp($page['totals']->total_expense) }}</td>
                            <td class="text-right nowrap">{{ $rp($page['totals']->balance) }}</td>
                            <td class="text-right nowrap">{{ $rp($page['totals']->balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            @endif

            {{-- Rekapitulasi --}}
            <div class="rekapitulasi">
                <div class="rekapitulasi-title">Rekapitulasi Pengeluaran Divisi Produksi {{ $page['periodTitle'] }}</div>
                <table>
                    <tr>
                        <td style="width: 30px;">1.</td>
                        <td>UANG MASUK</td>
                        <td class="text-right font-bold nowrap">{{ $rp($page['totals']->total_income) }}</td>
                    </tr>
                    <tr>
                        <td>2.</td>
                        <td>UANG KELUAR</td>
                        <td class="text-right font-bold nowrap">{{ $rp($page['totals']->total_expense) }}</td>
                    </tr>
                    <tr>
                        <td></td>
                        <td class="font-bold">SALDO</td>
                        <td class="text-right font-bold nowrap">{{ $rp($page['totals']->balance) }}</td>
                    </tr>
                    @if (!empty($page['carry']))
                        {{-- Saldo dibawa dari bulan sebelumnya (dalam rentang export) --}}
                        <tr>
                            <td></td>
                            <td>SALDO BULAN SEBELUMNYA</td>
                            <td class="text-right font-bold nowrap">{{ $rp($page['carry']['opening_balance']) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="font-bold">SALDO AKHIR (KUMULATIF)</td>
                            <td class="text-right font-bold nowrap">{{ $rp($page['carry']['closing_balance']) }}</td>
                        </tr>
                    @endif
                </table>
            </div>

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
        </div>
    @endforeach
</body>

</html>
