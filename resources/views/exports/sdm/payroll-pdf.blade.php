{{--
    Payroll PDF Export Template

    Generates a landscape A4 PDF report for payroll data.

    Sections:
    1. Header - Company name, report title, project, period, print date
    2. Attendance Table - Daily attendance status per employee per day
    3. Salary Summary Table - Daily wage, present days, overtime, kasbon, net salary
    4. Additional Expenses Section - If any expenses are recorded
    5. Fund Recap - Total wages, expenses, kasbon, grand total
    6. Footer - Auto-generated timestamp

    Layout: A4 Landscape (DomPDF)
    Orientation: Landscape (many columns)

    Variables received from PayrollController@exportPdf:
    - $payrolls: Collection with employee and attendances loaded
    - $periodText: Formatted period string
    - $projectName: Project name (or null)
    - $dateRange: Date range string (e.g., "01 Feb 2026 - 07 Feb 2026")
    - $weekDays: Array of 7 date strings for column headers
    - $totalBaseSalary, $totalDeduction, $totalOvertime, $totalNetSalary: Summary totals
    - $operationalExpenses: Collection of project operational expenses (period-level)
--}}

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penggajian & Absensi</title>
    <style>
        @page {
            margin: 0.5cm 1cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif, 'DejaVu Sans';
            font-size: 10px;
            color: #333;
            line-height: 1.3;
        }

        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px double #444;
            text-align: center;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .doc-title {
            font-size: 14px;
            margin-bottom: 10px;
            color: #555;
        }

        .meta-info {
            width: 100%;
            margin-bottom: 20px;
            font-size: 10px;
        }

        .meta-info td {
            padding: 3px 0;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 100px;
            color: #555;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }

        .main-table th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: bold;
            padding: 8px 4px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
        }

        .main-table td {
            padding: 6px 4px;
            border: 1px solid #ccc;
        }

        .main-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .font-bold {
            font-weight: bold;
        }

        .summary-container {
            width: 100%;
            display: table;
            margin-top: 10px;
        }

        .summary-col {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding: 10px;
            border: 1px solid #eee;
            background: #fff;
        }

        .spacer-col {
            width: 4%;
        }

        .summary-header {
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 5px;
            font-size: 11px;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }

        .s-label {
            display: table-cell;
            text-align: left;
        }

        .s-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }

        .grand-total {
            margin-top: 20px;
            background-color: #f0f0f0;
            border: 2px solid #333;
            padding: 10px;
            text-align: right;
            font-size: 14px;
        }

        .sig-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: top;
        }

        .sig-line {
            margin-top: 60px;
            border-top: 1px solid #333;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .legend {
            font-size: 9px;
            color: #666;
            margin-top: -10px;
            margin-bottom: 20px;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="company-name">PT. AGHITSNA KARYA INDAH</div>
        <div class="doc-title">DAFTAR ABSENSI & PENGGAJIAN PEKERJA</div>
    </div>

    <table class="meta-info">
        @if ($projectName)
            <tr>
                <td class="label">PROYEK</td>
                <td>: {{ $projectName }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">PERIODE</td>
            <td>: {{ $periodText }}</td>
        </tr>
        @if ($dateRange)
            <tr>
                <td class="label">TANGGAL</td>
                <td>: {{ $dateRange }}</td>
            </tr>
        @endif
    </table>

    @if (count($weekDays) > 0)
        {{-- TABEL ABSENSI HARIAN --}}
        <table class="main-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px;">NO</th>
                    <th rowspan="2">NAMA PEKERJA</th>
                    <th colspan="7">KEHADIRAN (7 Hari)</th>
                    <th rowspan="2" style="width: 80px;">UPAH HARIAN</th>
                    <th rowspan="2" style="width: 90px;">TOTAL UPAH</th>
                </tr>
                <tr>
                    @foreach ($weekDays as $date)
                        <th style="font-size: 8px; width: 25px;">{{ ['MING', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'][\Carbon\Carbon::parse($date)->dayOfWeek] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($payrolls as $index => $payroll)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $payroll->employee->name ?? '-' }}</td>
                        @foreach ($weekDays as $date)
                            @php
                                $attendance = $payroll->attendances->first(
                                    fn($a) => $a->attendance_date?->format('Y-m-d') === $date
                                );
                                $status = $attendance ? $attendance->status : '';
                                $symbol = 'L';
                                $bg = '';

                                if ($status === 'hadir') {
                                    $symbol = '✓';
                                } elseif ($status === 'lembur') {
                                    $symbol = 'Lb';
                                    $bg = '#e3f2fd';
                                } elseif ($status === 'izin') {
                                    $symbol = 'I';
                                    $bg = '#fff3e0';
                                } elseif ($status === 'sakit') {
                                    $symbol = 'S';
                                    $bg = '#ffebee';
                                } elseif ($status === 'cuti') {
                                    $symbol = 'C';
                                    $bg = '#f3e5f5';
                                }
                            @endphp
                            <td class="text-center" style="background-color: {{ $bg }}">{{ $symbol }}
                            </td>
                        @endforeach
                        <td class="text-right">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="legend">
            Keterangan: ✓=Hadir, Lb=Lembur, L=Libur, I=Izin, S=Sakit, C=Cuti
        </div>
    @else
        {{-- TABEL RINGKASAN --}}
        <table class="main-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px;">NO</th>
                    <th rowspan="2">NAMA PEKERJA</th>
                    <th colspan="5">REKAP KEHADIRAN</th>
                    <th rowspan="2">UPAH HARIAN</th>
                    <th rowspan="2">BONUS LEMBUR</th>
                    <th rowspan="2">POT. KASBON</th>
                    <th rowspan="2">DITERIMA</th>
                </tr>
                <tr>
                    <th style="font-size: 8px;">Hdr</th>
                    <th style="font-size: 8px;">Lbr</th>
                    <th style="font-size: 8px;">Izn</th>
                    <th style="font-size: 8px;">Skt</th>
                    <th style="font-size: 8px;">Cut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payrolls as $index => $payroll)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $payroll->employee->name ?? '-' }}</td>
                        <td class="text-center">{{ $payroll->present_days }}</td>
                        <td class="text-center">{{ $payroll->overtime_days }}</td>
                        <td class="text-center">{{ $payroll->permission_days }}</td>
                        <td class="text-center">{{ $payroll->sick_days }}</td>
                        <td class="text-center">{{ $payroll->leave_days }}</td>
                        <td class="text-right">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($payroll->overtime_total, 0, ',', '.') }}</td>
                        <td class="text-right text-red" style="color: #c0392b;">
                            {{ number_format($payroll->kasbon_deduction, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- SUMMARY SECTION --}}
    @php
        $totalWages = $payrolls->sum('net_salary');
        $totalKasbon = $payrolls->sum('kasbon_deduction');

        // Biaya operasional proyek: satu record per periode + data legacy di payroll
        $allExpenses = [];
        foreach (($operationalExpenses ?? collect()) as $expense) {
            $items = is_array($expense->expense_items) ? $expense->expense_items : [];
            foreach ($items as $exp) {
                $name = $exp['name'] ?? 'Lain-lain';
                $amount = (int) ($exp['amount'] ?? 0);
                if (!isset($allExpenses[$name])) {
                    $allExpenses[$name] = 0;
                }
                $allExpenses[$name] += $amount;
            }
        }
        foreach ($payrolls as $payroll) {
            if ($payroll->additional_expenses_notes) {
                $expenses = json_decode($payroll->additional_expenses_notes, true);
                if ($expenses && is_array($expenses)) {
                    foreach ($expenses as $exp) {
                        $name = $exp['name'] ?? 'Lain-lain';
                        $amount = $exp['amount'] ?? 0;
                        if (!isset($allExpenses[$name])) {
                            $allExpenses[$name] = 0;
                        }
                        $allExpenses[$name] += $amount;
                    }
                }
            }
        }
        $totalExpenses = array_sum($allExpenses);
        $grandTotal = $totalWages + $totalExpenses - $totalKasbon;
    @endphp

    <div class="summary-container">
        <!-- Pengeluaran Tambahan -->
        <div class="summary-col">
            <div class="summary-header">PENGELUARAN TAMBAHAN (OPERASIONAL)</div>
            @if (count($allExpenses) > 0)
                @foreach ($allExpenses as $name => $amount)
                    <div class="summary-row">
                        <div class="s-label">{{ $name }}</div>
                        <div class="s-value">{{ number_format($amount, 0, ',', '.') }}</div>
                    </div>
                @endforeach
                <div class="summary-row" style="border-top: 1px dashed #ccc; margin-top: 5px; padding-top: 5px;">
                    <div class="s-label font-bold">Total Tambahan</div>
                    <div class="s-value">{{ number_format($totalExpenses, 0, ',', '.') }}</div>
                </div>
            @else
                <div class="text-center" style="padding: 10px; color: #999;">- Tidak ada pengeluaran tambahan -</div>
            @endif
        </div>

        <div class="spacer-col"></div>

        <!-- Potongan Kasbon -->
        <div class="summary-col">
            <div class="summary-header">REKAPITULASI DANA</div>
            <div class="summary-row">
                <div class="s-label">Total Upah Pekerja</div>
                <div class="s-value">{{ number_format($totalWages, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="s-label">Total Pengeluaran Tambahan</div>
                <div class="s-value">{{ number_format($totalExpenses, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row" style="color: #c0392b;">
                <div class="s-label">Total Potongan Kasbon</div>
                <div class="s-value">({{ number_format($totalKasbon, 0, ',', '.') }})</div>
            </div>
        </div>
    </div>

    <div class="grand-total">
        TOTAL DIBAYARKAN: <span style="font-size: 18px; font-weight: bold;">Rp
            {{ number_format($grandTotal, 0, ',', '.') }}</span>
    </div>

    <div style="text-align: center; margin-top: 30px; font-size: 8px; color: #aaa;">
        Dicetak otomatis oleh Sistem ERP PT. Aghitsna Karya Indah pada {{ date('d/m/Y H:i') }}
    </div>
</body>

</html>
