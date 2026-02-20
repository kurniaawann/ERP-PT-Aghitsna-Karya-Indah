<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Absensi Pekerja</title>
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
        }

        .header {
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        .header-info {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .header-info .row {
            display: table-row;
        }

        .header-info .label {
            display: table-cell;
            width: 100px;
            font-weight: bold;
            padding: 3px 0;
        }

        .header-info .value {
            display: table-cell;
            padding: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th {
            background-color: #e0e0e0;
            font-weight: bold;
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #000;
            font-size: 9px;
        }

        td {
            padding: 6px 4px;
            border: 1px solid #000;
            font-size: 9px;
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .day-col {
            width: 8%;
        }

        .name-col {
            width: 20%;
        }

        .footer-section {
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .footer-section h3 {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .expense-table,
        .kasbon-table {
            width: 60%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .expense-table td,
        .kasbon-table td {
            padding: 5px;
            border: 1px solid #000;
            font-size: 9px;
        }

        .total-section {
            margin-top: 15px;
            padding: 10px;
            border: 2px solid #000;
            background-color: #f5f5f5;
        }

        .total-section h2 {
            font-size: 14px;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            font-size: 8px;
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>DAFTAR ABSENSI PEKERJA</h1>
        <div class="header-info">
            @if ($projectName)
                <div class="row">
                    <div class="label">PROYEK:</div>
                    <div class="value">{{ $projectName }}</div>
                </div>
            @endif
            @if ($dateRange)
                <div class="row">
                    <div class="label">TANGGAL:</div>
                    <div class="value">{{ $dateRange }}</div>
                </div>
            @endif
            <div class="row">
                <div class="label">PERIODE:</div>
                <div class="value">{{ $periodText }}</div>
            </div>
        </div>
    </div>

    @if (count($weekDays) > 0)
        {{-- Format dengan absensi harian (jika minggu dipilih) --}}
        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 3%;">NO</th>
                    <th rowspan="2" class="name-col">NAMA PEKERJA</th>
                    <th colspan="7">HARI KERJA</th>
                    <th rowspan="2" style="width: 10%;">UPAH/HARI</th>
                    <th rowspan="2" style="width: 12%;">TOTAL UPAH DIBAYAR</th>
                </tr>
                <tr>
                    <th class="day-col">MING</th>
                    <th class="day-col">SEN</th>
                    <th class="day-col">SEL</th>
                    <th class="day-col">RAB</th>
                    <th class="day-col">KAM</th>
                    <th class="day-col">JUM</th>
                    <th class="day-col">SAB</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payrolls as $index => $payroll)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $payroll->employee->name ?? '-' }}</td>
                        @foreach ($weekDays as $date)
                            @php
                                $attendance = $payroll->attendances->firstWhere('attendance_date', $date);
                                $mark = '';
                                if ($attendance) {
                                    if ($attendance->status === 'hadir') {
                                        $mark = '✓';
                                    } elseif ($attendance->status === 'lembur') {
                                        $mark = 'L';
                                    } elseif ($attendance->status === 'izin') {
                                        $mark = 'I';
                                    } elseif ($attendance->status === 'sakit') {
                                        $mark = 'S';
                                    } elseif ($attendance->status === 'cuti') {
                                        $mark = 'C';
                                    }
                                }
                            @endphp
                            <td>{{ $mark }}</td>
                        @endforeach
                        <td class="text-right">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-bottom: 15px; font-size: 8px; color: #666;">
            <strong>Keterangan:</strong> ✓ = Hadir | L = Lembur | I = Izin | S = Sakit | C = Cuti
        </div>
    @else
        {{-- Format ringkasan (tanpa absensi harian) --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 3%;">NO</th>
                    <th style="width: 15%;">NAMA PEKERJA</th>
                    <th style="width: 10%;">PERIODE</th>
                    <th style="width: 8%;">HADIR</th>
                    <th style="width: 6%;">IZIN</th>
                    <th style="width: 6%;">SAKIT</th>
                    <th style="width: 6%;">CUTI</th>
                    <th style="width: 6%;">LEMBUR</th>
                    <th style="width: 10%;">UPAH/HARI</th>
                    <th style="width: 10%;">BONUS LEMBUR</th>
                    <th style="width: 10%;">POTONGAN KASBON</th>
                    <th style="width: 10%;">TOTAL DIBAYAR</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payrolls as $index => $payroll)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-left">{{ $payroll->employee->name ?? '-' }}</td>
                        <td class="text-center">{{ $payroll->formatted_period }}</td>
                        <td class="text-center">{{ $payroll->present_days }}</td>
                        <td class="text-center">{{ $payroll->permission_days }}</td>
                        <td class="text-center">{{ $payroll->sick_days }}</td>
                        <td class="text-center">{{ $payroll->leave_days }}</td>
                        <td class="text-center">{{ $payroll->overtime_days }}</td>
                        <td class="text-right">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($payroll->overtime_total, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($payroll->kasbon_deduction, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer-section">
        <h3>PENGELUARAN TAMBAHAN:</h3>
        <table class="expense-table">
            @php
                $allExpenses = [];
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
            @endphp
            @if (count($allExpenses) > 0)
                @foreach ($allExpenses as $name => $amount)
                    <tr>
                        <td class="text-left" style="width: 60%;">{{ $name }}</td>
                        <td class="text-right">Rp {{ number_format($amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="2" class="text-center">Tidak ada pengeluaran tambahan</td>
                </tr>
            @endif
        </table>

        <h3>POTONGAN KASBON:</h3>
        <table class="kasbon-table">
            @php
                $totalKasbon = $payrolls->sum('kasbon_deduction');
            @endphp
            @if ($totalKasbon > 0)
                <tr>
                    <td class="text-left" style="width: 60%;">Total Potongan Kasbon</td>
                    <td class="text-right">Rp {{ number_format($totalKasbon, 0, ',', '.') }}</td>
                </tr>
            @else
                <tr>
                    <td colspan="2" class="text-center">Tidak ada potongan kasbon</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="total-section">
        @php
            $totalWages = $payrolls->sum('net_salary');
            $totalExpenses = array_sum($allExpenses);
            $grandTotal = $totalWages + $totalExpenses - $totalKasbon;
        @endphp
        <h2>TOTAL: Rp {{ number_format($grandTotal, 0, ',', '.') }}</h2>
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ date('d/m/Y H:i:s') }} | PT. Aghitsna Karya Indah - Sistem ERP</p>
    </div>
</body>

</html>
