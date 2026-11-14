<?php
namespace App\Console\Commands;

use App\Models\Photo;
use App\Services\ThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GeneratePhotoThumbnails extends Command
{
    protected $signature = 'photos:generate-thumbnails';
    protected $description = 'Buat thumbnail untuk foto yang belum memiliki thumbnail; file asli tetap disimpan.';

    public function handle(ThumbnailService $thumbnails): int
    {
        if (!extension_loaded('gd')) { $this->error('Ekstensi PHP GD belum aktif.'); return self::FAILURE; }
        $created = 0; $skipped = 0; $failed = 0;
        Photo::query()->orderBy('id')->chunkById(50, function ($photos) use ($thumbnails, &$created, &$skipped, &$failed) {
            foreach ($photos as $photo) {
                if ($photo->thumbnail_path && Storage::disk('public')->exists($photo->thumbnail_path)) { $skipped++; continue; }
                try { $thumbnails->generate($photo); $created++; }
                catch (Throwable $e) { $failed++; $this->warn("Foto #{$photo->id} gagal: {$e->getMessage()}"); }
            }
        });
        $this->info("Selesai: $created dibuat, $skipped sudah ada, $failed gagal.");
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
