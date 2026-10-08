<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Writer\Html as HtmlWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pratinjau dokumen sebelum diunduh (revisi klien: tampilkan preview dulu,
 * baru download saat pengguna menekan tombol Download).
 *
 * Bila request membawa `preview=1` dan response berupa file unduhan
 * (header Content-Disposition):
 * - PDF          → Content-Disposition diubah ke inline agar tampil di iframe.
 * - XLSX/XLS/CSV → dikonversi menjadi HTML (semua sheet) memakai PhpSpreadsheet.
 * - Word (HTML)  → ditampilkan langsung sebagai HTML.
 *
 * Tanpa `preview=1` response tidak disentuh, sehingga semua route export
 * yang sudah ada otomatis mendukung pratinjau tanpa perubahan controller.
 */
class PreviewDownload
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->boolean('preview')) {
            return $response;
        }

        $disposition = (string) $response->headers->get('Content-Disposition', '');
        if ($disposition === '' || !$response->isSuccessful()) {
            return $response;
        }

        $filename = $this->extractFilename($disposition);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        if ($extension === 'pdf' || str_contains($contentType, 'pdf')) {
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'inline; filename="' . addslashes($filename) . '"');

            return $response;
        }

        if (in_array($extension, ['xlsx', 'xls', 'csv'], true) || str_contains($contentType, 'spreadsheet')) {
            return $this->spreadsheetPreview($response, $extension ?: 'xlsx', $filename);
        }

        if (in_array($extension, ['doc', 'docx'], true) || str_contains($contentType, 'msword')) {
            $content = $this->readContent($response);

            // Export Word aplikasi ini berbasis HTML → bisa ditampilkan langsung.
            if (str_contains(substr(ltrim($content), 0, 200), '<')) {
                return response($content)->header('Content-Type', 'text/html; charset=UTF-8');
            }

            return $this->messagePage($filename, 'Pratinjau tidak tersedia untuk file ini. Silakan tekan Download.');
        }

        return $response;
    }

    /**
     * Konversi file spreadsheet hasil export menjadi halaman HTML.
     */
    private function spreadsheetPreview(Response $response, string $extension, string $filename): Response
    {
        $temporaryPath = null;

        try {
            if ($response instanceof BinaryFileResponse) {
                $path = $response->getFile()->getPathname();
            } else {
                $temporaryPath = tempnam(sys_get_temp_dir(), 'preview_') . '.' . $extension;
                file_put_contents($temporaryPath, $this->readContent($response));
                $path = $temporaryPath;
            }

            // Format angka gaya Indonesia (Rp. 50.000) pada sel numerik ber-format "Rp. "#,##0
            StringHelper::setThousandsSeparator('.');
            StringHelper::setDecimalSeparator(',');

            $spreadsheet = IOFactory::load($path);

            // Sembunyikan gridline bantu Excel agar pratinjau mirip hasil cetak
            // (hanya border tabel yang memang di-set export yang tampil).
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $sheet->setShowGridlines(false);
            }

            $writer = new HtmlWriter($spreadsheet);
            $writer->writeAllSheets();
            $writer->setEmbedImages(true);

            ob_start();
            $writer->save('php://output');
            $html = (string) ob_get_clean();

            // File sementara milik Excel::download (deleteFileAfterSend) tidak
            // akan pernah terkirim, jadi dibersihkan di sini.
            if ($response instanceof BinaryFileResponse && str_starts_with($path, sys_get_temp_dir())) {
                @unlink($path);
            }

            $style = '<style>body{margin:0;padding:16px;background:#fff;font-family:"Times New Roman",serif}'
                . 'ul.navigation{list-style:none;padding:0;margin:0 0 12px;display:flex;gap:8px;flex-wrap:wrap}'
                . 'ul.navigation li a{display:inline-block;padding:4px 10px;border:1px solid #cbd5e1;border-radius:6px;'
                . 'color:#1e3a8a;text-decoration:none;font-family:sans-serif;font-size:12px}</style>';
            $html = str_ireplace('</head>', $style . '</head>', $html);

            return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (\Throwable $e) {
            return $this->messagePage($filename, 'Pratinjau Excel gagal dibuat. Silakan tekan Download.');
        } finally {
            if ($temporaryPath) {
                @unlink($temporaryPath);
            }
        }
    }

    /**
     * Ambil isi body response (termasuk StreamedResponse).
     */
    private function readContent(Response $response): string
    {
        if ($response instanceof BinaryFileResponse) {
            return (string) file_get_contents($response->getFile()->getPathname());
        }

        if ($response instanceof StreamedResponse) {
            ob_start();
            $response->sendContent();

            return (string) ob_get_clean();
        }

        return (string) $response->getContent();
    }

    /**
     * Ambil nama file dari header Content-Disposition.
     */
    private function extractFilename(string $disposition): string
    {
        if (preg_match("/filename\\*=UTF-8''([^;]+)/i", $disposition, $matches)) {
            return rawurldecode(trim($matches[1], '"'));
        }

        if (preg_match('/filename="?([^";]+)"?/i', $disposition, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Halaman pesan sederhana di dalam iframe pratinjau.
     */
    private function messagePage(string $filename, string $message): Response
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head>'
            . '<body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:90vh;color:#475569">'
            . '<div style="text-align:center"><p style="font-weight:600">' . e($filename) . '</p><p>' . e($message) . '</p></div>'
            . '</body></html>';

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
