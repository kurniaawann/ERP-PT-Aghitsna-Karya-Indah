<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota - PT. Aghitsna Karya Indah</title>
    <style>
        @page {
            size: A4;
            margin: 0.6cm 0.8cm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 10px;
            color: #0f386b;
            background: #fff;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body {
                padding: 0;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>

<body>
    @foreach ($notas as $nota)
        @if ($nota->tipe_nota === \App\Models\Administrasi\Nota::TIPE_PROYEK)
            {{-- Layout Nota Proyek --}}
            @include('exports.administrasi.partials.nota-proyek', ['nota' => $nota])
        @else
            {{-- Layout Nota Sewa/Jual (existing) --}}
            @include('exports.administrasi.partials.nota-sewa-jual', ['nota' => $nota])
        @endif

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>