<!DOCTYPE html>
<html lang="id">

<head>
<meta charset="UTF-8">
<title>Slip Gaji - {{ ($slips->first() ?? $slip)->employee->name ?? ($slips->first() ?? $slip)->employee_code }}</title>
<style>
@page {
    size: A4 landscape;
    margin: 0.8cm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #000;
    line-height: 1.3;
    background-color: #fff;
}

.slip-page {
    border: 2px solid #000;
    padding: 15px;
    page-break-inside: avoid;
    box-sizing: border-box;
}

/* ── HEADER ── */
.header-table {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
    margin-bottom: 8px;
}

.header-table td {
    vertical-align: middle;
}

.company-logo {
    max-height: 45px;
    width: auto;
}

.company-name {
    font-size: 15px;
    font-weight: bold;
    letter-spacing: 0.5px;
}

.company-tagline {
    font-size: 11px;
    color: #333;
}

.date-text {
    text-align: right;
    font-size: 11px;
}

/* ── JUDUL ── */
.slip-title {
    text-align: center;
    font-weight: bold;
    font-size: 15px;
    margin: 8px 0 12px 0;
    letter-spacing: 1px;
}

/* ── DATA KARYAWAN ── */
.info-table {
    width: 350px;
    border-collapse: collapse;
    margin-bottom: 12px;
}

.info-table td {
    padding: 2px 0;
    font-size: 11px;
    vertical-align: top;
}

/* ── TABEL UTAMA PENERIMAAN & POTONGAN ── */
.main-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #000;
}

.main-table th, 
.main-table td {
    border: 1px solid #000;
    padding: 5px 8px;
    font-size: 11px;
}

.main-table th {
    font-weight: bold;
    text-align: left;
}

.bg-yellow-header {
    background-color: #ffff00 !important;
    text-align: center !important;
    font-weight: bold;
}

.text-blue-header {
    color: #0066cc;
    text-align: center !important;
    font-weight: bold;
}

.bg-yellow-total {
    background-color: #ffff00 !important;
    text-align: center;
    font-weight: bold;
}

.red-text {
    color: red;
}

.red-italic {
    color: red;
    font-style: italic;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

/* ── BARIS TOTAL ── */
.total-row td {
    font-weight: bold;
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
}

/* ── THP ── */
.thp-section {
    margin: 18px 0 25px 120px;
    font-size: 11px;
}

.thp-label {
    font-weight: bold;
    display: inline-block;
    width: 60px;
}

.thp-value {
    color: red;
    font-weight: bold;
    font-style: italic;
    border-bottom: 3px double #000;
    padding-bottom: 1px;
}

/* ── FOOTER & TANDA TANGAN ── */
.footer-table {
    width: 100%;
    border-collapse: collapse;
}

.footer-table td {
    vertical-align: top;
}

.note-box {
    background-color: #b8daff;
    color: #004080;
    font-size: 10.5px;
    padding: 2px 6px;
    display: inline-block;
    margin-bottom: 3px;
    font-weight: bold;
}

.received-box {
    background-color: #d4edda;
    padding: 3px 8px;
    display: inline-block;
    font-size: 11px;
}

.sig-space {
    height: 50px;
}

.sig-name {
    font-weight: bold;
    text-decoration: underline;
}
</style>
</head>

<body>
@php
    $slipCollection = isset($slips) ? collect($slips) : collect([$slip ?? null]);
@endphp

@foreach ($slipCollection->filter() as $slip)
@php
    $slipDate = $slip->payment_date
        ? $slip->payment_date->format('l , d F Y')
        : date('l , d F Y');
    $jpnCompany = (int) round($slip->ump * 0.02);
    $totalCompanyPaid = ($slip->bpjs_kesehatan_company ?? 0) + 
                        ($slip->jht_company ?? 0) + 
                        ($slip->jkk_company ?? 0) + 
                        $jpnCompany + 
                        ($slip->jkm_company ?? 0);
    
    $signature = is_array($slip->signatures) ? ($slip->signatures['dibuat'] ?? null) : null;
    $payrollName = $signature['name'] ?? 'KAMILA';
@endphp

<div class="slip-page">

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td width="18%">
                <img src="{{ public_path('images/logo.jpeg') }}" alt="PT. AGHITSNA KARYA INDAH" class="company-logo">
            </td>
            <td width="47%">
                <div class="company-name">PT AGHITSNA KARYA INDAH</div>
                <div class="company-tagline">Design & Built</div>
            </td>
            <td width="35%" class="date-text">
                Tanggal &nbsp;&nbsp;&nbsp;&nbsp; : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $slipDate }}
            </td>
        </tr>
    </table>

    <!-- Judul Dokumen -->
    <div class="slip-title">SLIP GAJI</div>

    <!-- Data Karyawan -->
    <table class="info-table">
        <tr>
            <td width="30%">ID</td>
            <td width="5%">:</td>
            <td>{{ $slip->employee_code }}</td>
        </tr>
        <tr>
            <td>NAMA</td>
            <td>:</td>
            <td>{{ $slip->employee->name ?? 'XXXX' }}</td>
        </tr>
        <tr>
            <td>JABATAN</td>
            <td>:</td>
            <td>{{ $slip->employee->position ?? 'Staff' }}</td>
        </tr>
        <tr>
            <td style="padding-left: 20px;">STATUS</td>
            <td>:</td>
            <td>{{ $slip->employee->status ?? 'K/2' }}</td>
        </tr>
    </table>

    <!-- Tabel Utama -->
    <table class="main-table">
        <thead>
            <tr>
                <th width="36%">PENERIMAAN</th>
                <th width="20%">POTONGAN</th>
                <th width="22%" class="bg-yellow-header">Dibayar Perusahaan</th>
                <th width="22%" class="text-blue-header">Dibayar Karyawan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gaji Pokok &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: Rp {{ number_format($slip->base_salary, 0, ',', '.') }}</td>
                <td>Kasbon :</td>
                <td class="text-right"></td>
                <td class="text-right">Rp {{ number_format($slip->kasbon_deduction, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Transport / {{ $slip->present_days }} Hari &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="red-italic">Rp {{ number_format($slip->transport_total, 0, ',', '.') }}</span></td>
                <td>BPJS KESEHATAN :</td>
                <td class="text-right red-italic">Rp {{ number_format($slip->bpjs_kesehatan_company, 0, ',', '.') }}</td>
                <td class="text-right red-italic">Rp {{ number_format($slip->bpjs_kesehatan_employee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Uang Makan / {{ $slip->present_days }} Hari &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span class="red-italic">Rp {{ number_format($slip->meal_total, 0, ',', '.') }}</span></td>
                <td>JHT :</td>
                <td class="text-right red-italic">Rp {{ number_format($slip->jht_company, 0, ',', '.') }}</td>
                <td class="text-right red-italic">Rp {{ number_format($slip->jht_employee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td></td>
                <td>JKK :</td>
                <td class="text-right red-italic">Rp {{ number_format($slip->jkk_company, 0, ',', '.') }}</td>
                <td class="text-center">-</td>
            </tr>
            <tr>
                <td></td>
                <td>JPN :</td>
                <td class="text-right red-italic">Rp {{ number_format($jpnCompany, 0, ',', '.') }}</td>
                <td class="text-right red-italic">Rp {{ number_format($slip->jpn_employee, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td></td>
                <td>JKM :</td>
                <td class="text-right red-italic">Rp {{ number_format($slip->jkm_company, 0, ',', '.') }}</td>
                <td class="text-center">-</td>
            </tr>
            <tr>
                <td></td>
                <td>PPH 21 :</td>
                <td></td>
                <td class="text-right red-italic">Rp {{ number_format($slip->pph21, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td class="bg-yellow-total">Rp {{ number_format($totalCompanyPaid, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td>TOTAL PENERIMAAN &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: Rp {{ number_format($slip->total_income, 0, ',', '.') }}</td>
                <td class="text-center"></td>
                <td>TOTAL POTONGAN &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:</td>
                <td class="text-right red-text" style="font-weight: bold;">Rp {{ number_format($slip->total_deduction, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Ringkasan THP -->
    <div class="thp-section">
        <span class="thp-label">THP</span>
        <span class="thp-value">Rp {{ number_format($slip->net_salary, 0, ',', '.') }}</span>
    </div>

    <!-- Catatan Tambahan & Tanda Tangan -->
    <table class="footer-table">
        <tr>
            <td width="20%">
                Payroll,<br>
                <div class="sig-space">
                    @if (!empty($signature['signature_image']))
                        <img src="{{ storage_path('app/public/' . $signature['signature_image']) }}" alt="Signature" style="max-height: 40px;">
                    @endif
                </div>
                <div class="sig-name">{{ $payrollName }}</div>
            </td>
            <td width="60%" style="padding-left: 10px;">
                <span class="note-box">*Bpjs Kesehatan : 1% dari Gaji Pokok</span><br>
                <span class="note-box">*JHT (Jaminan Hari Tua) : 2% dari Rp. {{ number_format($slip->ump, 0, ',', '.') }} (UMK Depok)</span><br>
                <span class="note-box">*JPN (Jaminan Pensiun) : 1% dari Rp. {{ number_format($slip->ump, 0, ',', '.') }} (UMK Depok)</span>
            </td>
            <td width="20%" class="text-right">
                <span class="received-box">Diterima Oleh,</span><br>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $slip->employee->name ?? 'XXXX' }}</div>
            </td>
        </tr>
    </table>

</div>

@if (!$loop->last)
<div style="page-break-after: always;"></div>
@endif
@endforeach

</body>
</html>