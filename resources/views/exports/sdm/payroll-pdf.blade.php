{{--
    Payroll PDF Export Template

    Generates a landscape A4 PDF report for payroll data.
    Layout adjusted to match official attendance sheet template.
--}}

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Absensi Pekerja</title>
    <style>
        @page {
            margin: 0.6cm 0.8cm;
        }

        body {
            /* DejaVu Sans digunakan agar DomPDF dapat merender simbol ceklis (✓) dengan benar */
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.2;
        }

        /* HEADER SECTION */
        .header-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .company-logo {
            height: 45px;
            width: auto;
        }

        .doc-title {
            font-size: 14px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            font-weight: bold;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: bottom;
        }

        .meta-border {
            border-bottom: 1px solid #000;
        }

        /* MAIN TABLE SECTION */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin-bottom: 8px;
        }

        .main-table th {
            background-color: #b0b0b0;
            color: #000;
            font-weight: bold;
            font-size: 8px;
            padding: 5px 2px;
            border: 1px solid #444;
            text-align: center;
            vertical-align: middle;
            text-transform: uppercase;
        }

        .main-table td {
            padding: 4px 3px;
            border: 1px solid #666;
            font-size: 8.5px;
        }

        .main-table tfoot td {
            background-color: #b0b0b0;
            font-weight: bold;
            border: 1px solid #444;
            font-size: 9px;
        }

        .symbol-cell {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        .legend {
            font-size: 8.5px;
            color: #333;
            margin-bottom: 15px;
            font-style: italic;
        }

        /* SIGNATURE SECTION */
        .sig-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        .sig-box {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
        }

        .sig-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .sig-space {
            min-height: 45px;
            height: 45px;
        }

        .sig-name {
            font-size: 9px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    {{-- HEADER KOP --}}
    <table class="header-table">
        <tr>
            <td style="width: 28%;">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="PT. AGHITSNA KARYA INDAH" class="company-logo">
            </td>
            <td style="width: 44%;">
                <div class="doc-title">DAFTAR ABSENSI PEKERJA</div>
            </td>
            <td style="width: 28%;">
                <table class="meta-table">
                    <tr>
                        <td style="width: 65px;">PROYEK</td>
                        <td>: <span class="meta-border">{{ $projectName ?? '.........................................' }}</span></td>
                    </tr>
                    <tr>
                        <td>TANGGAL</td>
                        <td>: <span class="meta-border">{{ $dateRange ?? $periodText }}</span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- TABEL ABSENSI UTAMA --}}
    @if (count($weekDays) > 0)
        <table class="main-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 25px;">NO</th>
                    <th rowspan="2" style="width: 120px;">NAMA PEKERJA</th>
                    <th rowspan="2" style="width: 80px;">JABATAN</th>
                    <th rowspan="2" style="width: 75px;">UPAH/HARI</th>
                    <th colspan="{{ count($weekDays) }}">HARI</th>
                    <th rowspan="2" style="width: 60px;">JUMLAH HARI KERJA</th>
                    <th rowspan="2" style="width: 65px;">LEMBUR</th>
                    <th rowspan="2" style="width: 65px;">KASBON</th>
                    <th rowspan="2" style="width: 85px;">JUMLAH UPAH DIBAYAR</th>
                </tr>
                <tr>
                    @foreach ($weekDays as $date)
                        <th style="width: 24px; font-size: 8px;">
                            {{ ['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MING'][\Carbon\Carbon::parse($date)->dayOfWeekIso - 1] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($payrolls as $index => $payroll)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $payroll->employee->name ?? '-' }}</td>
                        <td class="text-center">{{ $payroll->employee->position ?? '-' }}</td>
                        <td class="text-right">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                        @foreach ($weekDays as $date)
                            @php
                                $attendance = $payroll->attendances->first(
                                    fn($a) => $a->attendance_date?->format('Y-m-d') === $date
                                );
                                $status = $attendance ? $attendance->status : '';
                                $symbol = '';
                                $bg = '';

                                if ($status === 'hadir') {
                                    $symbol = '&#10003;'; // HTML entity untuk centang agar terbaca di DomPDF
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
                            <td class="text-center symbol-cell" style="background-color: {{ $bg }}">{!! $symbol !!}</td>
                        @endforeach
                        <td class="text-center font-bold">{{ $payroll->present_days }}</td>
                        <td class="text-right">{{ number_format($payroll->overtime_total, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($payroll->kasbon_deduction, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ 6 + count($weekDays) }}" class="text-center">TOTAL</td>
                    <td class="text-right">{{ number_format($payrolls->sum('kasbon_deduction'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($payrolls->sum('net_salary'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        {{-- REKAP ABSENSI BULANAN/PERIODE TANPA HARIAN --}}
        <table class="main-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 25px;">NO</th>
                    <th rowspan="2">NAMA PEKERJA</th>
                    <th rowspan="2" style="width: 90px;">JABATAN</th>
                    <th rowspan="2" style="width: 80px;">UPAH/HARI</th>
                    <th colspan="5">REKAP KEHADIRAN</th>
                    <th rowspan="2" style="width: 70px;">LEMBUR</th>
                    <th rowspan="2" style="width: 70px;">KASBON</th>
                    <th rowspan="2" style="width: 95px;">JUMLAH UPAH DIBAYAR</th>
                </tr>
                <tr>
                    <th style="font-size: 8px; width: 25px;">Hdr</th>
                    <th style="font-size: 8px; width: 25px;">Lbr</th>
                    <th style="font-size: 8px; width: 25px;">Izn</th>
                    <th style="font-size: 8px; width: 25px;">Skt</th>
                    <th style="font-size: 8px; width: 25px;">Cut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payrolls as $index => $payroll)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $payroll->employee->name ?? '-' }}</td>
                        <td class="text-center">{{ $payroll->employee->position ?? '-' }}</td>
                        <td class="text-right">{{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $payroll->present_days }}</td>
                        <td class="text-center">{{ $payroll->overtime_days }}</td>
                        <td class="text-center">{{ $payroll->permission_days }}</td>
                        <td class="text-center">{{ $payroll->sick_days }}</td>
                        <td class="text-center">{{ $payroll->leave_days }}</td>
                        <td class="text-right">{{ number_format($payroll->overtime_total, 0, ',', '.') }}</td>
                        <td class="text-right" style="color: #c0392b;">{{ number_format($payroll->kasbon_deduction, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="10" class="text-center">TOTAL</td>
                    <td class="text-right">{{ number_format($payrolls->sum('kasbon_deduction'), 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($payrolls->sum('net_salary'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- KETERANGAN LEGEND --}}
    <div class="legend">
        Keterangan: &#10003;=Hadir, Lb=Lembur, I=Izin, S=Sakit, C=Cuti — Minggu adalah hari libur
    </div>

    {{-- BLOK TANDA TANGAN --}}
    <table class="sig-table">
        <tr>
            @foreach (['disetujui' => 'DISETUJUI OLEH,', 'diperiksa' => 'DIPERIKSA OLEH,', 'dibuat' => 'DIBUAT OLEH,'] as $roleKey => $roleLabel)
                @php $signatory = $signatures[$roleKey] ?? null; @endphp
                <td class="sig-box">
                    <div class="sig-title">{{ $roleLabel }}</div>
                    <div class="sig-space">
                        @if ($signatory && !empty($signatory['signature_image']))
                            <img src="{{ storage_path('app/public/' . $signatory['signature_image']) }}"
                                alt="Tanda tangan {{ $signatory['name'] ?? '' }}"
                                style="max-height: 40px; max-width: 120px; object-fit: contain;">
                        @endif
                    </div>
                    <div class="sig-name">
                        ( {{ $signatory['name'] ?? '............................................' }} )
                    </div>
                </td>
            @endforeach
        </tr>
    </table>

    <div style="text-align: center; margin-top: 25px; font-size: 7.5px; color: #888;">
        Dicetak otomatis oleh Sistem ERP PT. Aghitsna Karya Indah pada {{ date('d/m/Y H:i') }}
    </div>
</body>

</html>