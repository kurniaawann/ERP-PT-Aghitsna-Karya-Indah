<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Semen</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #000000;
            margin: 0;
            padding: 0;
        }

        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #000000;
        }

        th {
            background-color: #d9d9d9;
            color: #000000;
            font-weight: bold;
            text-align: center;
            padding: 4px;
            font-size: 8.5pt;
            text-transform: uppercase;
        }

        td {
            padding: 3px 5px;
            font-size: 8.5pt;
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-red {
            color: #ff0000;
            font-weight: bold;
        }

        /* Header DO (Teks Merah di Tengah) */
        .do-header td {
            text-align: center;
            font-weight: bold;
            color: #ff0000;
            font-size: 8.5pt;
            padding: 3px;
            background-color: #ffffff;
        }

        /* Baris Subtotal per DO */
        .subtotal-row td {
            background-color: #d9d9d9;
            font-weight: bold;
        }

        /* Format Rata Kiri-Kanan untuk Nilai Mata Uang (Rp) */
        .currency {
            width: 100%;
        }

        .currency span:first-child {
            float: left;
        }

        .currency span:last-child {
            float: right;
        }

        .currency::after {
            content: "";
            display: table;
            clear: both;
        }

        /* Highlight Warna Kuning untuk Proyek GENTENG IJO */
        .bg-yellow {
            background-color: #ffff00 !important;
        }
    </style>
</head>

<body>

    <div class="header-title">LAPORAN SEMEN {{ $year ?? '2026' }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%">NO</th>
                <th style="width: 9%">TANGGAL</th>
                <th style="width: 22%">PROYEK</th>
                <th style="width: 7%">VOLUME</th>
                <th style="width: 7%">SATUAN</th>
                <th style="width: 11%">HARGA</th>
                <th style="width: 12%">JUMLAH</th>
                <th style="width: 10%">TGL LUNAS</th>
                <th style="width: 9%">HARGA MODAL</th>
                <th style="width: 9%">PROFIT</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryOrders as $do)
                @if ($do->cements && $do->cements->isNotEmpty())
                    {{-- Header DO --}}
                    <tr class="do-header">
                        <td colspan="10">DO KE {{ $loop->iteration }}</td>
                    </tr>

                    {{-- Detail Item Semen --}}
                    @foreach ($do->cements as $index => $cement)
                        <tr class="{{ strtolower($cement->nama_proyek) == 'genteng ijo' ? 'bg-yellow' : '' }}">
                            <td class="text-center">{{ $index + 1 }}</td>

                            {{-- Tanggal (Rowspan digabung vertikal per DO) --}}
                            @if ($index === 0)
                                <td rowspan="{{ $do->cements->count() }}" class="text-center">
                                    {{ $do->tanggal_datang ? \Carbon\Carbon::parse($do->tanggal_datang)->format('d.m.y') : '-' }}
                                </td>
                            @endif

                            <td>{{ strtoupper($cement->nama_proyek) }}</td>
                            <td class="text-center">{{ number_format($cement->jumlah, 0, ',', '.') }}</td>
                            <td class="text-center">ZAK</td>
                            <td>
                                <div class="currency">
                                    <span>Rp</span>
                                    <span>{{ number_format($cement->harga, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="currency">
                                    <span>Rp</span>
                                    <span>{{ number_format($cement->total, 0, ',', '.') }}</span>
                                </div>
                            </td>

                            {{-- Tanggal Lunas per item --}}
                            <td class="text-center">
                                @php
                                    $tglLunas = $cement->tanggal_lunas ?? $cement->tgl_lunas ?? null;
                                @endphp
                                {{ $tglLunas ? \Carbon\Carbon::parse($tglLunas)->format('d/m/Y') : '-' }}
                            </td>

                            {{-- Menggabungkan kolom HARGA MODAL & PROFIT (menghapus garis vertikal & horizontal) --}}
                            @if ($index === 0)
                                <td rowspan="{{ $do->cements->count() }}" colspan="2"></td>
                            @endif
                        </tr>
                    @endforeach

                    {{-- Subtotal DO --}}
                    <tr class="subtotal-row">
                        <td></td> {{-- Col 1: NO --}}
                        <td></td> {{-- Col 2: TANGGAL --}}
                        <td></td> {{-- Col 3: PROYEK --}}
                        <td class="text-center text-red">{{ number_format($do->cements->sum('jumlah'), 0, ',', '.') }}</td> {{-- Col 4: VOLUME --}}
                        <td colspan="2"></td> {{-- Col 5 & 6: SATUAN & HARGA --}}
                        <td colspan="2"> {{-- Col 7 & 8: JUMLAH & TGL LUNAS --}}
                            <div class="currency">
                                <span>Rp</span>
                                <span>{{ number_format($do->subtotal ?? $do->cements->sum('total'), 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td> {{-- Col 9: HARGA MODAL --}}
                            <div class="currency">
                                <span>Rp</span>
                                <span>{{ number_format($do->harga_modal, 0, ',', '.') }}</span>
                            </div>
                        </td>
                        <td> {{-- Col 10: PROFIT --}}
                            <div class="currency">
                                <span>Rp</span>
                                <span>{{ number_format($do->profit, 0, ',', '.') }}</span>
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

</body>

</html>