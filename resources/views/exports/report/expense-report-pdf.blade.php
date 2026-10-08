<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ auth()->user()->isAdmin() ? 'Kas Kantor' : 'Laporan Pengeluaran' }}</title>
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

        /* Tanpa kolom FAKTUR (role admin / Kas Kantor) */
        .no-faktur .col-no { width: 4%; }
        .no-faktur .col-tanggal { width: 10%; }
        .no-faktur .col-keterangan { width: 40%; }
        .no-faktur .col-pemasukan { width: 15%; }
        .no-faktur .col-pengeluaran { width: 15%; }
        .no-faktur .col-sumber { width: 16%; }

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

    </style>
</head>

@php
    // Aturan per role (revisi klien):
    // - Admin (Kas Kantor): tanpa kolom FAKTUR, kategori tanpa transaksi tidak
    //   ditampilkan, tanpa blok Rekapitulasi / halaman rekap per bulan.
    // - Role lain: tampilan lengkap seperti sebelumnya.
    $isAdmin = auth()->user()->isAdmin();
    $showFaktur = !$isAdmin;
    $showEmptyCategories = !$isAdmin;
    $showRekapitulasi = !$isAdmin;

    // Jumlah kolom label (NO s/d KETERANGAN) untuk colspan baris kategori / subtotal / total
    $labelColspan = $showFaktur ? 4 : 3;
    $columnCount = $labelColspan + 3;
@endphp

<body class="{{ $showFaktur ? '' : 'no-faktur' }}">
    @php
        $rp = fn ($value) => format_rupiah($value ?? 0);

        // Nominal subtotal: sisi yang nol dikosongkan bila sisi lainnya terisi
        $rpSubtotal = fn ($value, $other) => ((int) $value === 0 && (int) $other !== 0) ? '' : format_rupiah($value ?? 0);

        $allCategories = \App\Models\Report\TransactionCategory::where('created_by', auth()->id())
            ->module(\App\Models\Report\TransactionCategory::MODULE_EXPENSE_RECAP)
            ->active()->orderBy('sort_order')->get();

        // Laporan bulanan: export lintas bulan dipecah per bulan + halaman rekap per bulan
        // (kecuali admin). Export satu bulan (atau tanpa data) tetap satu bagian.
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
            <div class="title-container">
                <div class="company">PT. AGHITSNA KARYA INDAH</div>
                <div class="title">{{ $isAdmin ? 'KAS KANTOR' : 'LAPORAN PENGELUARAN DIVISI PRODUKSI' }}</div>
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
                            @if ($showFaktur)
                                <th class="col-faktur">FAKTUR</th>
                            @endif
                            <th class="col-tanggal">TANGGAL</th>
                            <th class="col-keterangan">KETERANGAN</th>
                            <th class="col-pemasukan">PEMASUKAN</th>
                            <th class="col-pengeluaran">PENGELUARAN</th>
                            {{-- Admin: "Sumber Uang" tampil sebagai "Keterangan" (hanya teks) --}}
                            <th class="col-sumber">{{ $isAdmin ? 'KETERANGAN' : 'SUMBER UANG' }}</th>
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
                                <td colspan="{{ $labelColspan }}">{{ strtoupper($category->name ?? 'LAIN-LAIN') }}</td>
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
                                    @if ($showFaktur)
                                        <td>{{ $expense->invoice_number ?? '' }}</td>
                                    @endif
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

                            {{-- Baris kosong putih jika tidak ada data (selain admin) --}}
                            @if ($expenses->isEmpty())
                                <tr>
                                    @for ($i = 0; $i < $columnCount; $i++)
                                        <td>{!! $i === 0 ? '&nbsp;' : '' !!}</td>
                                    @endfor
                                </tr>
                            @endif

                            {{-- Category Subtotal --}}
                            <tr class="subtotal-row">
                                <td colspan="{{ $labelColspan }}" class="subtotal-label">SUB TOTAL</td>
                                <td class="text-right nowrap">{{ $rpSubtotal($categoryIncome, $categoryExpense) }}</td>
                                <td class="text-right nowrap">{{ $rpSubtotal($categoryExpense, $categoryIncome) }}</td>
                                <td></td>
                            </tr>
                        @endforeach

                        {{-- Grand Total (per bulan untuk export multi-bulan) --}}
                        <tr class="grand-total-row">
                            <td colspan="{{ $labelColspan }}" class="text-center">JUMLAH</td>
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

            {{-- Rekapitulasi (tidak ditampilkan untuk admin) --}}
            @if ($showRekapitulasi)
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
            @endif

            {{-- Tanda tangan: satu penandatangan di kanan (dipilih saat cetak) --}}
            <div class="footer-signatures">
                @include('exports.partials.report-signer', ['signer' => $signer ?? null, 'uppercase' => true])
            </div>
        </div>
    @endforeach
</body>

</html>
