<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengeluaran{{ auth()->user()->isAdmin() ? '' : ' Divisi Produksi' }}</title>
    <style>
        /* Kertas A4 PORTRAIT (diatur di controller). Margin halaman diatur di @page;
           !important wajib karena reset "* { margin: 0 }" ikut menimpa margin halaman di dompdf */
        @page {
            margin: 10mm 10mm 12mm 10mm !important;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
        }

        /* Setiap bagian bulan (dan halaman rekap per bulan) dimulai di halaman baru */
        .page-break {
            page-break-before: always;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 15pt;
            margin-bottom: 1px;
        }

        .header-subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 1px;
        }

        .header-period {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 8px;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 10px;
        }

        /* Isi tabel 10pt untuk 7 kolom (dengan FAKTUR) di kertas portrait;
           tanpa FAKTUR (admin) kolom lebih lega sehingga tetap 11pt */
        table.report th,
        table.report td {
            border: 1px solid black;
            padding: 2px 4px;
            font-size: 10pt;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .no-faktur table.report td {
            font-size: 11pt;
        }

        /* Judul kolom diperkecil agar kata seperti "PENGELUARAN" tidak terpotong */
        table.report th {
            background-color: #FFFF00;
            font-weight: bold;
            font-size: 9.5pt;
            padding: 3px 2px;
        }

        .no-faktur table.report th {
            font-size: 10pt;
        }

        /* Header tabel diulang di setiap halaman lanjutan (halaman 2 dst.) */
        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        /* Satu baris tidak pernah terbelah di dua halaman */
        tr {
            page-break-inside: avoid;
        }

        /* Judul kategori tidak tertinggal sendirian di dasar halaman */
        tr.category-row {
            page-break-after: avoid;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .nowrap {
            white-space: nowrap;
        }

        /* Nomor faktur panjang (mis. 333/590/div.produksi/146) memakai huruf sedikit lebih kecil */
        table.report td.faktur {
            font-size: 9pt;
        }

        .category-row td {
            font-weight: bold;
            padding: 3px 4px;
        }

        .category-row td.category-header {
            background-color: #A9D08E;
        }

        .category-row td.empty-cell {
            background-color: white;
        }

        .subtotal-row td {
            background-color: #FFCC00;
            font-style: italic;
        }

        /* Baris "Jumlah" (total per bulan) berlatar KUNING */
        .total-row td {
            background-color: #FFFF00;
            font-weight: bold;
        }

        /* Rekapitulasi (uang masuk, uang keluar, saldo) — tidak dipakai role admin */
        .rekapitulasi {
            margin-top: 10px;
            font-weight: bold;
            font-size: 12pt;
            page-break-inside: avoid;
        }

        .rekapitulasi-title {
            margin-bottom: 4px;
        }

        .rekapitulasi table {
            border: none;
            border-collapse: collapse;
            width: 75%;
        }

        .rekapitulasi td {
            border: none;
            padding: 1px 4px;
            font-size: 12pt;
        }

        .rekapitulasi td.rekap-value {
            text-align: right;
            white-space: nowrap;
        }

        /* Lebar kolom A4 portrait — dengan kolom FAKTUR (super admin) */
        .col-no { width: 4.5%; }
        .col-faktur { width: 19%; }
        .col-tanggal { width: 10%; }
        .col-keterangan { width: 23%; }
        .col-pemasukan { width: 14.5%; }
        .col-pengeluaran { width: 14.5%; }
        .col-sumber { width: 14.5%; }

        /* Lebar kolom A4 portrait — tanpa kolom FAKTUR (admin / Kas Kantor) */
        .no-faktur .col-no { width: 5%; }
        .no-faktur .col-tanggal { width: 11%; }
        .no-faktur .col-keterangan { width: 36%; }
        .no-faktur .col-pemasukan { width: 16%; }
        .no-faktur .col-pengeluaran { width: 16%; }
        .no-faktur .col-sumber { width: 16%; }

        /* Tabel rekap per bulan (halaman terakhir export multi-bulan) */
        .col-bulan { width: 20%; }
        .col-nominal { width: 18.75%; }

        .footer-signatures {
            margin-top: 12px;
            width: 100%;
            page-break-inside: avoid;
        }

        /* Rekapitulasi + tanda tangan selalu satu halaman (tidak terpisah) */
        .closing {
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
            padding: 4px;
            width: 33.33%;
            font-size: 12pt;
        }

        .signature-line {
            margin-top: 50px;
        }
    </style>
</head>

@php
    // Aturan per role (revisi klien):
    // - Admin (menu "Kas Kantor"): tanpa kolom FAKTUR, kategori tanpa transaksi
    //   tidak ditampilkan, dan tanpa blok Rekapitulasi / halaman rekap per bulan.
    // - Super admin: kolom FAKTUR, semua kategori aktif, dan Rekapitulasi tetap ada.
    $isAdmin = auth()->user()->isAdmin();
    $showFaktur = !$isAdmin;
    $showEmptyCategories = !$isAdmin;
    $showRekapitulasi = !$isAdmin;

    // Jumlah kolom label (NO s/d KETERANGAN) untuk colspan baris kategori / subtotal / total
    $labelColspan = $showFaktur ? 4 : 3;
    $columnCount = $labelColspan + 3;

    $rp = fn ($value) => format_rupiah($value ?? 0);

    // Nominal subtotal: sisi yang nol dikosongkan bila sisi lainnya terisi
    // (mis. kategori pemasukan tidak menampilkan "Rp. 0" di kolom pengeluaran).
    $rpSubtotal = fn ($value, $other) => ((int) $value === 0 && (int) $other !== 0) ? '' : format_rupiah($value ?? 0);
@endphp

<body class="{{ $showFaktur ? '' : 'no-faktur' }}">
    @php
        $allCategories = \App\Models\Report\TransactionCategory::where('created_by', auth()->id())
            ->module(\App\Models\Report\TransactionCategory::MODULE_EXPENSE_RECAP)
            ->active()->orderBy('sort_order')->get();

        // Laporan bulanan: export lintas bulan dipecah per bulan (+ halaman rekap per bulan
        // khusus super admin). Export satu bulan (atau tanpa data) tetap satu bagian.
        $monthlySections = $monthlySections ?? [];
        $isMultiMonth = count($monthlySections) > 1;

        $pages = [];
        if ($isMultiMonth) {
            foreach ($monthlySections as $index => $section) {
                $pages[] = [
                    'type' => 'month',
                    'periodTitle' => $section['label'],
                    'records' => $section['records'],
                    'totals' => $section['totals'],
                    // Saldo bulan sebelumnya dibawa ke bulan berikutnya (mulai bulan ke-2)
                    'carry' => $index > 0 ? $section : null,
                ];
            }
            if ($showRekapitulasi) {
                $pages[] = ['type' => 'summary', 'periodTitle' => $periodTitle, 'totals' => $totals];
            }
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
            <div class="header-title">PT. AGHITSNA KARYA INDAH</div>
            <div class="header-subtitle">
                @if ($isAdmin)
                    LAPORAN PENGELUARAN
                @else
                    LAPORAN PENGELUARAN DIVISI PRODUKSI
                @endif
            </div>
            @if ($page['type'] === 'summary')
                <div class="header-period">REKAPITULASI PER BULAN &mdash; PERIODE {{ strtoupper($page['periodTitle']) }}</div>
            @else
                <div class="header-period">PERIODE {{ strtoupper($page['periodTitle']) }}</div>
            @endif

            @if ($page['type'] === 'month')
                <table class="report">
                    <thead>
                        <tr>
                            <th class="col-no">NO</th>
                            @if ($showFaktur)
                                <th class="col-faktur">FAKTUR</th>
                            @endif
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

                            {{-- Admin: kategori tanpa transaksi tidak ditampilkan sama sekali --}}
                            @continue($expenses->isEmpty() && !$showEmptyCategories)

                            {{-- Category Header Row --}}
                            <tr class="category-row">
                                <td colspan="{{ $labelColspan }}" class="category-header">{{ strtoupper($category->name ?? 'LAIN-LAIN') }}</td>
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
                                    <td>{{ $itemNo++ }}</td>
                                    @if ($showFaktur)
                                        <td class="faktur">{{ $expense->invoice_number ?? '' }}</td>
                                    @endif
                                    <td class="nowrap">
                                        {{ $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('d/m/Y') : '' }}
                                    </td>
                                    <td>{{ $expense->description ?? '' }}</td>
                                    {{-- Baris pemasukan: kolom pengeluaran kosong, dan sebaliknya (bukan "Rp. 0") --}}
                                    <td class="nowrap">{{ (int) $expense->income_amount > 0 ? $rp($expense->income_amount) : '' }}</td>
                                    <td class="nowrap">{{ (int) $expense->expense_amount > 0 ? $rp($expense->expense_amount) : '' }}</td>
                                    <td>{{ $expense->money_source ?? '' }}</td>
                                </tr>
                            @endforeach

                            {{-- Baris kosong putih jika tidak ada data (hanya super admin) --}}
                            @if ($expenses->isEmpty())
                                <tr>
                                    @for ($i = 0; $i < $columnCount; $i++)
                                        <td>{!! $i === 0 ? '&nbsp;' : '' !!}</td>
                                    @endfor
                                </tr>
                            @endif

                            {{-- Category Subtotal --}}
                            <tr class="subtotal-row">
                                <td colspan="{{ $labelColspan }}"></td>
                                <td class="nowrap">{{ $rpSubtotal($categoryIncome, $categoryExpense) }}</td>
                                <td class="nowrap">{{ $rpSubtotal($categoryExpense, $categoryIncome) }}</td>
                                <td></td>
                            </tr>
                        @endforeach

                        {{-- Grand Total (per bulan untuk export multi-bulan) --}}
                        <tr class="total-row">
                            <td colspan="{{ $labelColspan }}">Jumlah</td>
                            <td class="nowrap">{{ $rp($page['totals']->total_income) }}</td>
                            <td class="nowrap">{{ $rp($page['totals']->total_expense) }}</td>
                            <td class="nowrap">{{ $rp($page['totals']->balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                {{-- Rekap per bulan: total tiap bulan + saldo kumulatif (khusus super admin) --}}
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
                                <td>{{ $index + 1 }}</td>
                                <td>{{ strtoupper($section['label']) }}</td>
                                <td class="nowrap">{{ $rp($section['totals']->total_income) }}</td>
                                <td class="nowrap">{{ $rp($section['totals']->total_expense) }}</td>
                                <td class="nowrap">{{ $rp($section['totals']->balance) }}</td>
                                <td class="nowrap">{{ $rp($section['closing_balance']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="2">Jumlah</td>
                            <td class="nowrap">{{ $rp($page['totals']->total_income) }}</td>
                            <td class="nowrap">{{ $rp($page['totals']->total_expense) }}</td>
                            <td class="nowrap">{{ $rp($page['totals']->balance) }}</td>
                            <td class="nowrap">{{ $rp($page['totals']->balance) }}</td>
                        </tr>
                    </tbody>
                </table>
            @endif

            <div class="closing">
            {{-- Rekapitulasi (super admin saja; admin tidak menampilkan blok ini) --}}
            @if ($showRekapitulasi)
                <div class="rekapitulasi">
                    <div class="rekapitulasi-title">
                        Rekapitulasi Pengeluaran Divisi Produksi {{ $page['periodTitle'] }}
                    </div>

                    <table>
                        <tr>
                            <td style="width: 30px;">1.</td>
                            <td>UANG MASUK</td>
                            <td class="rekap-value">{{ $rp($page['totals']->total_income) }}</td>
                        </tr>
                        <tr>
                            <td>2.</td>
                            <td>UANG KELUAR</td>
                            <td class="rekap-value">{{ $rp($page['totals']->total_expense) }}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>SALDO</td>
                            <td class="rekap-value">{{ $rp($page['totals']->balance) }}</td>
                        </tr>
                        @if (!empty($page['carry']))
                            {{-- Saldo dibawa dari bulan sebelumnya (dalam rentang export) --}}
                            <tr>
                                <td></td>
                                <td>SALDO BULAN SEBELUMNYA</td>
                                <td class="rekap-value">{{ $rp($page['carry']['opening_balance']) }}</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>SALDO AKHIR (KUMULATIF)</td>
                                <td class="rekap-value">{{ $rp($page['carry']['closing_balance']) }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            @endif

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
            </div>
        </div>
    @endforeach
</body>

</html>
