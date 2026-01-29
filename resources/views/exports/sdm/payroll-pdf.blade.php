<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Payroll</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header h2 {
            font-size: 13px;
            font-weight: normal;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #4472C4;
            color: white;
            font-weight: bold;
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #000;
            font-size: 8px;
        }

        td {
            padding: 6px 4px;
            border: 1px solid #000;
            font-size: 8px;
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

        tr.total-row {
            background-color: #FFD966;
            font-weight: bold;
        }

        .status-paid {
            background-color: #C6EFCE;
            color: #006100;
            font-weight: bold;
            text-align: center;
        }

        .status-draft {
            background-color: #FFC7CE;
            color: #9C0006;
            font-weight: bold;
            text-align: center;
        }

        .footer {
            margin-top: 15px;
            font-size: 8px;
            color: #666;
        }

        .summary-section {
            margin-top: 20px;
            padding: 10px;
            border-top: 2px solid #333;
        }

        .summary-table {
            width: 50%;
            margin-left: auto;
            border: none;
        }

        .summary-table td {
            border: none;
            padding: 5px;
            font-size: 9px;
        }

        .summary-table .label {
            font-weight: bold;
            width: 60%;
        }

        .summary-table .value {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN PAYROLL (PENGGAJIAN KARYAWAN)</h1>
        <h2>Periode: {{ $periodText }}</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3%;">NO</th>
                <th style="width: 8%;">KODE</th>
                <th style="width: 15%;">NAMA KARYAWAN</th>
                <th style="width: 12%;">JABATAN</th>
                <th style="width: 8%;">PERIODE</th>
                <th style="width: 10%;">GAJI POKOK</th>
                <th style="width: 4%;">HADIR</th>
                <th style="width: 4%;">IZIN</th>
                <th style="width: 4%;">SAKIT</th>
                <th style="width: 4%;">CUTI</th>
                <th style="width: 4%;">LEMBUR</th>
                <th style="width: 8%;">POTONGAN</th>
                <th style="width: 8%;">TOTAL LEMBUR</th>
                <th style="width: 10%;">GAJI BERSIH</th>
                <th style="width: 6%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payrolls as $index => $payroll)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $payroll->employee->employee_code ?? '-' }}</td>
                    <td class="text-left">{{ $payroll->employee->name ?? '-' }}</td>
                    <td class="text-left">{{ $payroll->employee->jabatan ?? '-' }}</td>
                    <td class="text-center">{{ $payroll->formatted_period }}</td>
                    <td class="text-right">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $payroll->present_days }}</td>
                    <td class="text-center">{{ $payroll->permission_days }}</td>
                    <td class="text-center">{{ $payroll->sick_days }}</td>
                    <td class="text-center">{{ $payroll->leave_days }}</td>
                    <td class="text-center">{{ $payroll->overtime_days }}</td>
                    <td class="text-right">Rp {{ number_format($payroll->deduction_amount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($payroll->overtime_total, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                    <td class="{{ $payroll->status === 'paid' ? 'status-paid' : 'status-draft' }}">
                        {{ strtoupper($payroll->status) }}
                    </td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="5" class="text-center">TOTAL</td>
                <td class="text-right">Rp {{ number_format($totalBaseSalary, 0, ',', '.') }}</td>
                <td colspan="5"></td>
                <td class="text-right">Rp {{ number_format($totalDeduction, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalOvertime, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalNetSalary, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="label">Total Gaji Pokok:</td>
                <td class="value">Rp {{ number_format($totalBaseSalary, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total Potongan:</td>
                <td class="value">Rp {{ number_format($totalDeduction, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Total Lembur:</td>
                <td class="value">Rp {{ number_format($totalOvertime, 0, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td class="label" style="font-size: 10px;">TOTAL GAJI BERSIH:</td>
                <td class="value" style="font-size: 10px;">Rp {{ number_format($totalNetSalary, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ date('d/m/Y H:i:s') }}</p>
        <p>PT. Aghitsna Karya Indah - Sistem ERP</p>
    </div>
</body>

</html>
