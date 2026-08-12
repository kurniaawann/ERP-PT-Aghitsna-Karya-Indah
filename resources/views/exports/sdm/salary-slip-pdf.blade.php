{{--
    Slip Gaji PDF Export Template (per slip, portrait A4)

    Mencetak satu slip gaji karyawan bulanan: kop, identitas karyawan,
    rekap absensi 30 hari, perhitungan gaji, dan blok tanda tangan.
--}}

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Slip Gaji - {{ $slip->employee->name ?? $slip->employee_code }}</title>
    <style>
        @page {
            margin: 0.7cm 0.8cm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
            line-height: 1.3;
        }

        .header-table {
            width: 100%;
            margin-bottom: 12px;
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
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .doc-sub {
            font-size: 9px;
            text-align: center;
            margin-top: 2px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 3px 4px;
            vertical-align: top;
        }

        .info-label {
            width: 110px;
            font-weight: bold;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin-bottom: 8px;
        }

        .main-table th {
            background-color: #b0b0b0;
            font-weight: bold;
            font-size: 7.5px;
            padding: 4px 1px;
            border: 1px solid #444;
            text-align: center;
            text-transform: uppercase;
        }

        .main-table td {
            padding: 3px 2px;
            border: 1px solid #666;
            font-size: 7.5px;
            text-align: center;
        }

        .calc-table {
            width: 55%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin-bottom: 12px;
        }

        .calc-table td {
            padding: 5px 8px;
            border: 1px solid #666;
            font-size: 9px;
        }

        .calc-table .amount {
            text-align: right;
            width: 40%;
        }

        .calc-table .deduction {
            color: #c0392b;
        }

        .calc-table .total {
            font-weight: bold;
            font-size: 10px;
            background-color: #e8f0fe;
        }

        .symbol-cell {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        .legend {
            font-size: 7.5px;
            color: #333;
            margin-bottom: 8px;
            font-style: italic;
        }

        .sig-table {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }

        .sig-box {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
        }

        .sig-title {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .sig-space {
            min-height: 45px;
            height: 45px;
        }

        .sig-name {
            font-size: 8.5px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    {{-- KOP --}}
    <table class="header-table">
        <tr>
            <td style="width: 22%;">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="PT. AGHITSNA KARYA INDAH" class="company-logo">
            </td>
            <td style="width: 56%;">
                <div class="doc-title">SLIP GAJI</div>
                <div class="doc-sub">Karyawan Bulanan — {{ $slip->formatted_period }}</div>
            </td>
            <td style="width: 22%; text-align: right; font-size: 8px;">
                @php
                    $monthName = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'][$slip->period_month] ?? $slip->period_month;
                @endphp
                No: SLIP/{{ $slip->id }}/{{ strtoupper($monthName) }}/{{ $slip->period_year }}
            </td>
        </tr>
    </table>

    {{-- IDENTITAS KARYAWAN --}}
    <table class="info-table">
        <tr>
            <td class="info-label">Nama</td>
            <td>: {{ $slip->employee->name ?? '-' }}</td>
            <td class="info-label">Jabatan</td>
            <td>: {{ $slip->employee->position ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Kode Karyawan</td>
            <td>: {{ $slip->employee_code }}</td>
            <td class="info-label">Divisi</td>
            <td>: {{ $slip->employee->division ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Periode</td>
            <td>: {{ $slip->formatted_period }} ({{ $slip->days_in_month }} hari)</td>
            <td class="info-label">Status</td>
            <td>: {{ $slip->status === 'paid' ? 'LUNAS / PAID' : 'DRAFT' }}
                @if ($slip->payment_date)
                    ({{ $slip->payment_date->format('d M Y') }})
                @endif
            </td>
        </tr>
    </table>

    {{-- REKAP ABSENSI 30 HARI --}}
    @php
        $matrix = $slip->attendance_matrix;
    @endphp
    <table class="main-table">
        <thead>
            <tr>
                <th colspan="{{ $slip->days_in_month }}">REKAP ABSENSI</th>
            </tr>
            <tr>
                @foreach ($matrix as $day => $status)
                    <th style="width: {{ 100 / $slip->days_in_month }}%;">{{ $day }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($matrix as $day => $status)
                    @php
                        $current = $status ?? 'H';
                        $bg = '';
                        if ($current === 'I') { $bg = '#fff3e0'; }
                        elseif ($current === 'S') { $bg = '#ffebee'; }
                        elseif ($current === 'C') { $bg = '#f3e5f5'; }
                        elseif ($current === 'A') { $bg = '#eceff1'; }
                        elseif ($current === 'L') { $bg = '#e8f0fe'; }
                    @endphp
                    <td class="symbol-cell" style="background-color: {{ $bg }};">{{ $current }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="legend">
        Keterangan: H = Hadir, I = Izin, S = Sakit, C = Cuti, A = Alpha, L = Libur (Minggu &amp; hari libur)
    </div>

    {{-- RINGKASAN ABSENSI --}}
    <table class="main-table" style="width: 62%; margin-bottom: 12px;">
        <thead>
            <tr>
                <th>HADIR</th>
                <th>IZIN</th>
                <th>SAKIT</th>
                <th>CUTI</th>
                <th>ALPHA</th>
                <th>LIBUR</th>
            </tr>
        </thead>
        <tbody>
            <tr class="font-bold">
                <td>{{ $slip->present_days }}</td>
                <td>{{ $slip->permission_days }}</td>
                <td>{{ $slip->sick_days }}</td>
                <td>{{ $slip->leave_days }}</td>
                <td>{{ $slip->absent_days }}</td>
                <td>{{ $slip->libur_days }}</td>
            </tr>
        </tbody>
    </table>

    {{-- PERHITUNGAN --}}
    <table class="calc-table">
        <tr>
            <td>Gaji Pokok</td>
            <td class="amount">Rp {{ number_format($slip->base_salary, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Uang Transport ({{ $slip->present_days }} hari × Rp {{ number_format($slip->transport_rate, 0, ',', '.') }})</td>
            <td class="amount">Rp {{ number_format($slip->transport_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Uang Makan ({{ $slip->present_days }} hari × Rp {{ number_format($slip->meal_rate, 0, ',', '.') }})</td>
            <td class="amount">Rp {{ number_format($slip->meal_total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Penerimaan</td>
            <td class="amount">Rp {{ number_format($slip->total_income, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>BPJS Kesehatan (1% × Gaji Pokok)</td>
            <td class="amount deduction">- Rp {{ number_format($slip->bpjs_kesehatan_employee, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>JHT (2% × UMP)</td>
            <td class="amount deduction">- Rp {{ number_format($slip->jht_employee, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>JPN (1% × UMP)</td>
            <td class="amount deduction">- Rp {{ number_format($slip->jpn_employee, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>PPh 21</td>
            <td class="amount deduction">- Rp {{ number_format($slip->pph21, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Kasbon</td>
            <td class="amount deduction">- Rp {{ number_format($slip->kasbon_deduction, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Potongan</td>
            <td class="amount deduction">- Rp {{ number_format($slip->total_deduction, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="total">THP (Take Home Pay)</td>
            <td class="amount total">Rp {{ number_format($slip->net_salary, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div style="font-size: 7.5px; margin-bottom: 10px; color: #333;">
        <strong>Iuran dibayar perusahaan (informasi, % dari UMP Rp {{ number_format($slip->ump, 0, ',', '.') }}):</strong><br>
        BPJS Kesehatan 4%: Rp {{ number_format($slip->bpjs_kesehatan_company, 0, ',', '.') }} &nbsp;·&nbsp;
        JHT 3,70%: Rp {{ number_format($slip->jht_company, 0, ',', '.') }} &nbsp;·&nbsp;
        JKK 0,24%: Rp {{ number_format($slip->jkk_company, 0, ',', '.') }} &nbsp;·&nbsp;
        JKM 0,30%: Rp {{ number_format($slip->jkm_company, 0, ',', '.') }}
    </div>

    @if ($slip->notes)
        <div style="font-size: 8px; margin-bottom: 10px;">
            <strong>Catatan:</strong> {{ $slip->notes }}
        </div>
    @endif

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
                        ( {{ $signatory['name'] ?? '___________________________________________' }} )
                    </div>
                    @if ($signatory && !empty($signatory['position']))
                        <div style="font-size: 7.5px;">{{ $signatory['position'] }}</div>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>

    <div style="text-align: center; margin-top: 22px; font-size: 7.5px; color: #888;">
        Dicetak otomatis oleh Sistem ERP PT. Aghitsna Karya Indah pada {{ date('d/m/Y H:i') }}
    </div>
</body>

</html>
