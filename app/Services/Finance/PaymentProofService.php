<?php

namespace App\Services\Finance;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentProofService
{
    public function store(UploadedFile $file, string $moduleType, string $invoiceType, string $invoiceNumber): array
    {
        $relativeDirectory = $this->buildRelativeDirectory($moduleType, $invoiceType, $invoiceNumber);
        $absoluteDirectory = public_path($relativeDirectory);

        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0755, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('Gagal membuat folder penyimpanan bukti pembayaran.');
        }

        $fileName = Str::uuid()->toString() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
        $relativePath = $relativeDirectory . '/' . $fileName;
        $absolutePath = public_path($relativePath);

        if (!function_exists('imagecreatetruecolor')) {
            $mimeType = $file->getClientMimeType() ?: $file->getMimeType();
            try {
                $file->move($absoluteDirectory, $fileName);
            } catch (\Exception $e) {
                throw new RuntimeException('Gagal menyimpan file bukti pembayaran.');
            }

            return [
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $relativePath,
                'mime_type' => $mimeType,
                'file_size' => file_exists($absolutePath) ? filesize($absolutePath) : null,
            ];
        }

        $imageInfo = @getimagesize($file->getPathname());

        if ($imageInfo === false) {
            throw new RuntimeException('File yang diunggah bukan gambar yang valid.');
        }

        [$sourceWidth, $sourceHeight] = $imageInfo;
        $sourceImage = $this->createImageResource($file->getPathname(), $file->getMimeType());

        $maxWidth = 1200;
        $maxHeight = 1200;
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = (int) round($sourceWidth * $ratio);
        $targetHeight = (int) round($sourceHeight * $ratio);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        // Save resized image as jpeg
        $fileName = Str::uuid()->toString() . '.jpg';
        $relativePath = $relativeDirectory . '/' . $fileName;
        $absolutePath = public_path($relativePath);

        if (!imagejpeg($canvas, $absolutePath, 80)) {
            imagedestroy($sourceImage);
            imagedestroy($canvas);
            throw new RuntimeException('Gagal menyimpan file bukti pembayaran.');
        }

        imagedestroy($sourceImage);
        imagedestroy($canvas);

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'mime_type' => 'image/jpeg',
            'file_size' => file_exists($absolutePath) ? filesize($absolutePath) : null,
        ];
    }

    public function delete(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $absolutePath = public_path($relativePath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function createImageResource(string $path, ?string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : throw new RuntimeException('Format WEBP tidak didukung di server ini.'),
            default => throw new RuntimeException('Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.'),
        };
    }

    private function buildRelativeDirectory(string $moduleType, string $invoiceType, string $invoiceNumber): string
    {
        return 'images/proof_payment/'
            . $this->sanitizeSegment($moduleType) . '/'
            . $this->sanitizeSegment($invoiceType) . '/'
            . $this->sanitizeSegment($invoiceNumber);
    }

    private function sanitizeSegment(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '_', $value) ?? $value;

        return trim($value, '_') ?: 'default';
    }
}