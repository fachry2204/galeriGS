<?php
namespace Tests\Feature;
use App\Models\{Album,Category,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class AlbumCoverTest extends TestCase {
 use RefreshDatabase;
 private function admin(): void { Sanctum::actingAs(User::create(['name'=>'Admin','username'=>'admin','email'=>'admin@example.test','password'=>'password-123','role'=>'admin','must_change_password'=>false])); }
 private function album(): Album { $category=Category::create(['name'=>'Kegiatan','slug'=>'kegiatan']);return Album::create(['category_id'=>$category->id,'title'=>'Album','slug'=>'album','is_published'=>true]); }
 public function test_cover_can_be_uploaded_and_replaced_without_changing_album_photos(): void {
  Storage::fake('public');$this->admin();$album=$this->album();
  $response=$this->postJson("/api/v1/admin/albums/{$album->id}/cover",['cover'=>UploadedFile::fake()->image('cover.jpg',2400,1600)])->assertOk();
  $first=$response->json('cover_path');$this->assertNotEmpty($first);Storage::disk('public')->assertExists($first);$this->assertStringContainsString('/storage/'.$first,$response->json('cover_url'));
  [$w,$h]=getimagesize(Storage::disk('public')->path($first));$this->assertSame([1200,800],[$w,$h]);$this->assertSame(0,$album->photos()->count());
  $this->getJson('/api/v1/albums?root_only=1')->assertOk()->assertJsonPath('data.0.cover_path',$first);
  $second=$this->postJson("/api/v1/admin/albums/{$album->id}/cover",['cover'=>UploadedFile::fake()->image('another.png',800,600)])->assertOk()->json('cover_path');
  $this->assertNotSame($first,$second);Storage::disk('public')->assertMissing($first);Storage::disk('public')->assertExists($second);
  $this->deleteJson("/api/v1/admin/albums/{$album->id}")->assertNoContent();Storage::disk('public')->assertMissing($second);
 }
 public function test_album_photo_can_replace_uploaded_cover_without_deleting_photo(): void {
  Storage::fake('public');$this->admin();$album=$this->album();
  $old=$this->postJson("/api/v1/admin/albums/{$album->id}/cover",['cover'=>UploadedFile::fake()->image('cover.jpg')])->assertOk()->json('cover_path');
  $photo=$album->photos()->create(['filename'=>'photo.jpg','original_filename'=>'photo.jpg','filepath'=>'photos/photo.jpg','mime_type'=>'image/jpeg','file_size'=>3]);Storage::disk('public')->put('photos/photo.jpg','abc');
  $this->putJson("/api/v1/admin/albums/{$album->id}/cover-photo",['photo_id'=>$photo->id])->assertOk()->assertJsonPath('cover_photo_id',$photo->id)->assertJsonPath('cover_url',null);
  Storage::disk('public')->assertMissing($old);Storage::disk('public')->assertExists('photos/photo.jpg');$this->assertDatabaseHas('photos',['id'=>$photo->id]);
 }
 public function test_cover_validation_and_authentication(): void {
  Storage::fake('public');$album=$this->album();
  $this->postJson("/api/v1/admin/albums/{$album->id}/cover",['cover'=>UploadedFile::fake()->image('cover.jpg')])->assertUnauthorized();
  $this->admin();$this->postJson("/api/v1/admin/albums/{$album->id}/cover",['cover'=>UploadedFile::fake()->create('bad.txt',5,'text/plain')])->assertUnprocessable();
  $this->assertNull($album->fresh()->cover_path);
 }
}
