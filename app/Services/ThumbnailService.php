<?php
namespace App\Services;

use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ThumbnailService
{
    public function generate(Photo $photo): string
    {
        $disk = Storage::disk('public');
        if ($photo->thumbnail_path && $disk->exists($photo->thumbnail_path)) return $photo->thumbnail_path;
        if (!extension_loaded('gd')) throw new RuntimeException('Ekstensi PHP GD tidak tersedia.');
        if (!$disk->exists($photo->filepath)) throw new RuntimeException('File asli tidak ditemukan.');
        $sourcePath = $disk->path($photo->filepath);
        $info = @getimagesize($sourcePath);
        if (!$info || empty($info[0]) || empty($info[1])) throw new RuntimeException('File gambar tidak valid.');
        [$width, $height] = $info;
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== false && $memoryLimit !== '-1') {
            $limit = (int) $memoryLimit;
            $suffix = strtolower(substr(trim($memoryLimit), -1));
            $limit *= match ($suffix) { 'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1 };
            if ($limit > 0 && memory_get_usage(true) + ($width * $height * 6) + 16777216 > $limit) throw new RuntimeException('Memori PHP tidak cukup untuk gambar ini.');
        }
        $source = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };
        if (!$source) throw new RuntimeException('Format gambar tidak didukung oleh PHP GD.');
        $thumb = null;
        $path = null;
        try {
            $scale = min(1, 1200 / max($width, $height));
            $thumbWidth = max(1, (int) round($width * $scale));
            $thumbHeight = max(1, (int) round($height * $scale));
            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            if (!$thumb) throw new RuntimeException('Gagal membuat gambar thumbnail.');
            $white = imagecolorallocate($thumb, 255, 255, 255);
            imagefill($thumb, 0, 0, $white);
            if (!imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height)) throw new RuntimeException('Gagal mengubah ukuran gambar.');
            if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($sourcePath);
                $orientation = (int) ($exif['Orientation'] ?? 1);
                if (in_array($orientation, [2, 4, 5, 7], true)) imageflip($thumb, IMG_FLIP_HORIZONTAL);
                $angle = match ($orientation) { 3, 4 => 180, 5, 6 => -90, 7, 8 => 90, default => 0 };
                if ($angle !== 0) {
                    $rotated = imagerotate($thumb, $angle, $white);
                    if (!$rotated) throw new RuntimeException('Gagal menyesuaikan orientasi gambar.');
                    imagedestroy($thumb);
                    $thumb = $rotated;
                }
            }
            ob_start();
            try { $encoded = imagejpeg($thumb, null, 78); $bytes = ob_get_contents(); }
            finally { ob_end_clean(); }
            if (!$encoded || !$bytes) throw new RuntimeException('Gagal mengkodekan thumbnail.');
            $path = 'thumbnails/'.$photo->album_id.'/'.Str::uuid().'.jpg';
            if (!$disk->put($path, $bytes, 'public')) throw new RuntimeException('Gagal menyimpan thumbnail.');
            $photo->update(['thumbnail_path' => $path]);
            return $path;
        } catch (Throwable $e) {
            if ($path) $disk->delete($path);
            throw $e;
        } finally {
            imagedestroy($source);
            if ($thumb) imagedestroy($thumb);
        }
    }
}
