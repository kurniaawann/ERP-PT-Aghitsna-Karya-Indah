<?php

namespace App\Services\Sdm;

use App\Models\Sdm\Executive;
use GdImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Service untuk mengelola bisnis logika petinggi (executive).
 *
 * Menangani daftar petinggi, pembuatan, pembaruan, penghapusan,
 * dan penyimpanan/penghapusan file gambar tanda tangan.
 */
class ExecutiveService
{
    /**
     * Mendapatkan daftar petinggi dengan paginasi dan pencarian opsional.
     *
     * Logika:
     * - Hanya data milik user login (created_by) yang ditampilkan.
     * - Pencarian dibatasi pada kolom name dan position dengan pengelompokan
     *   yang tepat agar OR tidak membatalkan kondisi created_by.
     *
     * @param  string|null  $search
     * @param  int          $perPage
     * @return LengthAwarePaginator
     */
    public function getPaginatedExecutives(?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return Executive::where('created_by', auth()->id())
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Membuat petinggi baru beserta gambar tanda tangan (opsional).
     *
     * Logika:
     * - created_by selalu di-set dari user yang login.
     * - Jika gambar diunggah, file disimpan terlebih dahulu. Jika penyimpanan
     *   data gagal, file yang baru tersimpan ikut dihapus (cleanup).
     *
     * @param  array<string, mixed>        $data  Data petinggi yang sudah divalidasi
     * @param  UploadedFile|null           $image File gambar tanda tangan
     * @return array{success: bool, message: string}
     */
    public function createExecutive(array $data, ?UploadedFile $image): array
    {
        $storedImage = null;

        try {
            if ($image) {
                $storedImage = $this->storeImage($image);
                $data['signature_image'] = $storedImage['file_path'];
            }

            $data['created_by'] = auth()->id();
            Executive::create($data);
        } catch (Throwable $throwable) {
            Log::error('Executive store failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            if (isset($storedImage['file_path'])) {
                $this->deleteImage($storedImage['file_path']);
            }

            return ['success' => false, 'message' => 'Gagal menyimpan data petinggi. Silakan coba lagi.'];
        }

        return ['success' => true, 'message' => 'Data petinggi berhasil ditambahkan!'];
    }

    /**
     * Memperbarui petinggi yang sudah ada.
     *
     * Logika:
     * - Gambar tanda tangan hanya diganti jika ada file baru yang diunggah;
     *   gambar lama dihapus setelah data berhasil disimpan.
     * - Jika flag remove_signature aktif, tanda tangan yang ada dihapus dan
     *   kolom signature_image di-set null (prioritas tertinggi, di atas upload).
     * - Jika penyimpanan gagal, file baru dihapus agar tidak ada file yatim.
     *
     * @param  Executive                    $executive
     * @param  array<string, mixed>         $data     Data petinggi yang sudah divalidasi
     * @param  UploadedFile|null            $image    File gambar tanda tangan baru
     * @return array{success: bool, message: string}
     */
    public function updateExecutive(Executive $executive, array $data, ?UploadedFile $image): array
    {
        $oldFilePath = $executive->signature_image;
        $storedImage = null;
        $removeSignature = !empty($data['remove_signature']);

        try {
            if ($removeSignature) {
                $data['signature_image'] = null;
            } elseif ($image) {
                $storedImage = $this->storeImage($image);
                $data['signature_image'] = $storedImage['file_path'];
            }

            $executive->update($data);

            if ($removeSignature) {
                if ($oldFilePath) {
                    $this->deleteImage($oldFilePath);
                }
            } elseif (isset($storedImage['file_path']) && $oldFilePath && $oldFilePath !== $storedImage['file_path']) {
                $this->deleteImage($oldFilePath);
            }
        } catch (Throwable $throwable) {
            Log::error('Executive update failed', ['error' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]);

            if (isset($storedImage['file_path'])) {
                $this->deleteImage($storedImage['file_path']);
            }

            return ['success' => false, 'message' => 'Gagal memperbarui data petinggi. Silakan coba lagi.'];
        }

        return ['success' => true, 'message' => 'Data petinggi berhasil diperbarui!'];
    }

    /**
     * Menghapus petinggi berdasarkan ID-nya beserta file tanda tangan.
     *
     * @param  array<int>  $ids
     * @return array{success: bool, message: string}
     */
    public function deleteExecutives(array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => 'Tidak ada data yang dipilih!'];
        }

        $executives = Executive::whereIn('id', $ids)->get();

        foreach ($executives as $executive) {
            $executive->delete();
            $this->deleteImage($executive->signature_image);
        }

        return ['success' => true, 'message' => 'Data petinggi berhasil dihapus!'];
    }

    /**
     * Menyimpan file gambar tanda tangan.
     *
     * Logika:
     * - Jika GD tersedia: resize gambar maksimal 800×800 (proporsional, tidak
     *   pernah diperbesar) namun tetap mempertahankan format asli sehingga
     *   transparansi PNG tidak hilang.
     * - Jika GD tidak tersedia: simpan file apa adanya.
     * - File disimpan dengan nama UUID di Storage::disk('public') dengan path
     *   RELATIF agar portabel antar server.
     *
     * @param  UploadedFile  $file
     * @return array{file_path: string}
     *
     * @throws RuntimeException
     */
    private function storeImage(UploadedFile $file): array
    {
        $relativeDirectory = 'images/signatures';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');

        if (!function_exists('imagecreatetruecolor')) {
            $fileName = Str::uuid()->toString() . '.' . $extension;
            $relativePath = $relativeDirectory . '/' . $fileName;

            $file->storeAs($relativeDirectory, $fileName, ['disk' => 'public']);

            return ['file_path' => $relativePath];
        }

        $imageInfo = @getimagesize($file->getPathname());

        if ($imageInfo === false) {
            throw new RuntimeException('File yang diunggah bukan gambar yang valid.');
        }

        [$sourceWidth, $sourceHeight] = $imageInfo;
        $sourceImage = $this->createImageResource($file->getPathname(), $file->getMimeType());

        $maxWidth = 800;
        $maxHeight = 800;
        $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
        $targetWidth = (int) round($sourceWidth * $ratio);
        $targetHeight = (int) round($sourceHeight * $ratio);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (in_array($file->getMimeType(), ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);
        }

        imagecopyresampled($canvas, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        $fileName = Str::uuid()->toString() . '.' . $extension;
        $relativePath = $relativeDirectory . '/' . $fileName;

        $tempPath = tempnam(sys_get_temp_dir(), 'sign_');

        if (!$this->saveImage($canvas, $tempPath, $file->getMimeType(), $extension)) {
            imagedestroy($sourceImage);
            imagedestroy($canvas);
            @unlink($tempPath);
            throw new RuntimeException('Gagal menyimpan file tanda tangan.');
        }

        imagedestroy($sourceImage);
        imagedestroy($canvas);

        Storage::disk('public')->put($relativePath, file_get_contents($tempPath));
        @unlink($tempPath);

        return ['file_path' => $relativePath];
    }

    /**
     * Menyimpan image resource ke file temp sesuai format aslinya.
     *
     * @param  GdImage       $canvas
     * @param  string        $tempPath
     * @param  string|null   $mimeType
     * @param  string        $extension
     * @return bool
     */
    private function saveImage(GdImage $canvas, string $tempPath, ?string $mimeType, string $extension): bool
    {
        return match ($mimeType) {
            'image/png'              => imagepng($canvas, $tempPath),
            'image/gif'              => imagegif($canvas, $tempPath),
            'image/webp'             => function_exists('imagewebp') ? imagewebp($canvas, $tempPath) : false,
            default                  => $extension === 'png' ? imagepng($canvas, $tempPath) : imagejpeg($canvas, $tempPath, 80),
        };
    }

    /**
     * Menghapus file gambar tanda tangan berdasarkan path relatif.
     *
     * @param  string|null  $relativePath
     * @return void
     */
    private function deleteImage(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        Storage::disk('public')->delete($relativePath);
    }

    /**
     * Membuat image resource dari file path.
     *
     * @param  string       $path
     * @param  string|null  $mimeType
     * @return GdImage
     *
     * @throws RuntimeException
     */
    private function createImageResource(string $path, ?string $mimeType): GdImage
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png'               => imagecreatefrompng($path),
            'image/gif'               => imagecreatefromgif($path),
            'image/webp'              => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : throw new RuntimeException('Format WEBP tidak didukung di server ini.'),
            default                   => throw new RuntimeException('Format gambar tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.'),
        };
    }
}
