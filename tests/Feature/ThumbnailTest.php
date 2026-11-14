<?php
namespace Tests\Feature;

use App\Models\{Album, Category, Photo, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ThumbnailTest extends TestCase
{
    use RefreshDatabase;

    private function album(): Album
    {
        $category = Category::create(['name'=>'Kegiatan', 'slug'=>'kegiatan']);
        return Album::create(['category_id'=>$category->id, 'title'=>'Test', 'slug'=>'test', 'is_published'=>true]);
    }

    public function test_upload_creates_smaller_thumbnail_and_preserves_original_download(): void
    {
        Storage::fake('public');
        $user = User::create(['name'=>'Admin','username'=>'admin','email'=>'admin@example.test','password'=>'password-123','role'=>'admin','must_change_password'=>false]);
        Sanctum::actingAs($user);
        $album = $this->album();
        $file = UploadedFile::fake()->image('large.jpg', 2400, 1600);
        $original = file_get_contents($file->getRealPath());
        $this->postJson("/api/v1/admin/albums/{$album->id}/photos", ['photos'=>[$file]])->assertCreated();
        $photo = Photo::firstOrFail();
        $this->assertNotNull($photo->thumbnail_path);
        $this->assertNotSame($photo->filepath, $photo->thumbnail_path);
        Storage::disk('public')->assertExists($photo->thumbnail_path);
        $dimensions = getimagesize(Storage::disk('public')->path($photo->thumbnail_path));
        $this->assertSame([1200,800], [$dimensions[0],$dimensions[1]]);
        $this->assertSame($original, Storage::disk('public')->get($photo->filepath));
        $response = $this->get("/api/v1/photos/{$photo->id}/download")->assertOk();
        $this->assertSame($original, $response->streamedContent());
        $this->deleteJson("/api/v1/admin/photos/{$photo->id}")->assertNoContent();
        Storage::disk('public')->assertMissing($photo->thumbnail_path);
    }

    public function test_backfill_is_repeatable_and_does_not_change_original(): void
    {
        Storage::fake('public');
        $album = $this->album();
        $file = UploadedFile::fake()->image('old.jpg', 1600, 1000);
        $path = $file->store('photos/'.$album->id, 'public');
        $before = Storage::disk('public')->get($path);
        $photo = $album->photos()->create(['filename'=>basename($path), 'original_filename'=>'old.jpg', 'filepath'=>$path, 'mime_type'=>'image/jpeg', 'file_size'=>strlen($before)]);
        $this->artisan('photos:generate-thumbnails')->assertExitCode(0);
        $thumbnail = $photo->fresh()->thumbnail_path;
        Storage::disk('public')->assertExists($thumbnail);
        $this->artisan('photos:generate-thumbnails')->assertExitCode(0);
        $this->assertSame($thumbnail, $photo->fresh()->thumbnail_path);
        $this->assertSame($before, Storage::disk('public')->get($path));
    }

    public function test_backfill_reports_missing_original_without_deleting_record(): void
    {
        Storage::fake('public');
        $album = $this->album();
        $photo = $album->photos()->create(['filename'=>'missing.jpg', 'original_filename'=>'missing.jpg', 'filepath'=>'missing.jpg', 'mime_type'=>'image/jpeg', 'file_size'=>1]);
        $this->artisan('photos:generate-thumbnails')->assertExitCode(1);
        $this->assertDatabaseHas('photos', ['id'=>$photo->id, 'thumbnail_path'=>null]);
    }
}
