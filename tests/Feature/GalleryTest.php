<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\{User,Category,Album,Photo};
use Laravel\Sanctum\Sanctum;
class GalleryTest extends TestCase {
 use RefreshDatabase;
 private function admin(bool $change=false): User { return User::create(['name'=>'Admin','username'=>'admin','email'=>'admin@example.test','password'=>'correct-password','role'=>'admin','must_change_password'=>$change]); }
 private function album(): Album { $c=Category::create(['name'=>'Event','slug'=>'event']); return Album::create(['category_id'=>$c->id,'title'=>'Jakarta','slug'=>'jakarta','is_published'=>true]); }
 public function test_login_and_password_change_are_required(): void { $u=$this->admin(true); $this->postJson('/api/v1/admin/login',['username'=>'admin','password'=>'bad'])->assertStatus(422); $login=$this->postJson('/api/v1/admin/login',['username'=>'admin','password'=>'correct-password'])->assertOk(); $token=$login->json('token'); $this->withToken($token)->getJson('/api/v1/admin/albums')->assertForbidden(); $this->withToken($token)->postJson('/api/v1/admin/change-password',['current_password'=>'correct-password','password'=>'new-password-123','password_confirmation'=>'new-password-123'])->assertOk(); $this->withToken($token)->getJson('/api/v1/admin/albums')->assertOk(); $this->withToken($token)->postJson('/api/v1/admin/logout')->assertNoContent(); }
 public function test_upload_append_delete_and_download_original(): void { Storage::fake('public'); Sanctum::actingAs($this->admin()); $a=$this->album(); $this->postJson("/api/v1/admin/albums/{$a->id}/photos",['photos'=>[UploadedFile::fake()->image('first.jpg')]])->assertCreated(); $first=Photo::first(); $a->update(['cover_photo_id'=>$first->id]); $this->postJson("/api/v1/admin/albums/{$a->id}/photos",['photos'=>[UploadedFile::fake()->image('second.jpg')]])->assertCreated(); $this->assertCount(2,Photo::all()); $this->assertEquals($first->id,$a->fresh()->cover_photo_id); $this->get("/api/v1/photos/{$first->id}/download")->assertOk(); $second=Photo::whereKeyNot($first->id)->first(); $this->deleteJson("/api/v1/admin/photos/{$second->id}")->assertNoContent(); Storage::disk('public')->assertMissing($second->filepath); Storage::disk('public')->assertExists($first->filepath); $this->assertEquals($first->id,$a->fresh()->cover_photo_id); $this->postJson('/api/v1/admin/photos/bulk-delete',['ids'=>[$first->id]])->assertNoContent(); $this->assertNull($a->fresh()->cover_photo_id); }
 public function test_album_crud_and_category_updates(): void { Sanctum::actingAs($this->admin()); $c=Category::create(['name'=>'Event','slug'=>'event']); $r=$this->postJson('/api/v1/admin/albums',['title'=>'Cerita','slug'=>'cerita','category_id'=>$c->id,'is_published'=>false])->assertCreated(); $id=$r->json('id'); $this->putJson("/api/v1/admin/albums/$id",['title'=>'Cerita Baru','slug'=>'cerita-baru','category_id'=>$c->id,'is_published'=>true])->assertOk(); $this->assertDatabaseHas('albums',['id'=>$id,'title'=>'Cerita Baru','is_published'=>true]); $this->putJson("/api/v1/admin/categories/{$c->id}",['name'=>'Acara','slug'=>'acara','is_active'=>true])->assertOk(); $this->assertDatabaseHas('categories',['id'=>$c->id,'name'=>'Acara']); }
 public function test_gallery_lists_only_published_photos_and_filters(): void { $a=$this->album(); $a->photos()->create(['filename'=>'visible.jpg','original_filename'=>'visible.jpg','filepath'=>'visible.jpg','mime_type'=>'image/jpeg','file_size'=>3]); $draft=Album::create(['category_id'=>$a->category_id,'title'=>'Draft','slug'=>'draft','is_published'=>false]); $draft->photos()->create(['filename'=>'hidden.jpg','original_filename'=>'hidden.jpg','filepath'=>'hidden.jpg','mime_type'=>'image/jpeg','file_size'=>3]); $this->getJson('/api/v1/photos')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.original_filename','visible.jpg'); $this->getJson('/api/v1/photos?category=event&search=Jakarta')->assertJsonCount(1,'data'); $this->getJson('/api/v1/photos?search=hidden')->assertJsonCount(0,'data'); }
 public function test_category_deletion_moves_active_and_soft_deleted_albums_without_losing_photos(): void {
  Storage::fake('public'); Sanctum::actingAs($this->admin()); $album=$this->album(); $category=$album->category; $target=Category::create(['name'=>'Lainnya','slug'=>'lainnya']);
  $photo=$album->photos()->create(['filename'=>'a.jpg','original_filename'=>'a.jpg','filepath'=>'a.jpg','mime_type'=>'image/jpeg','file_size'=>3]); Storage::disk('public')->put('a.jpg','abc');
  $archived=Album::create(['category_id'=>$category->id,'title'=>'Arsip','slug'=>'arsip','is_published'=>false]); $archived->delete();
  $this->getJson('/api/v1/admin/categories')->assertOk()->assertJsonFragment(['albums_total_count'=>2]);
  $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertStatus(409);
  $this->deleteJson("/api/v1/admin/categories/{$category->id}",['replacement_category_id'=>$category->id])->assertUnprocessable();
  $this->deleteJson("/api/v1/admin/categories/{$category->id}",['replacement_category_id'=>$target->id])->assertNoContent();
  $this->assertDatabaseMissing('categories',['id'=>$category->id]); $this->assertDatabaseHas('albums',['id'=>$album->id,'category_id'=>$target->id]); $this->assertDatabaseHas('albums',['id'=>$archived->id,'category_id'=>$target->id]); $this->assertDatabaseHas('photos',['id'=>$photo->id]); Storage::disk('public')->assertExists('a.jpg');
 }
 public function test_unused_category_can_be_deleted(): void { Sanctum::actingAs($this->admin()); $c=Category::create(['name'=>'Kosong','slug'=>'kosong']); $this->deleteJson("/api/v1/admin/categories/{$c->id}")->assertNoContent(); $this->assertDatabaseMissing('categories',['id'=>$c->id]); }
 public function test_album_with_photos_cannot_be_deleted(): void {
  Storage::fake('public'); Sanctum::actingAs($this->admin()); $album=$this->album();
  $photo=$album->photos()->create(['filename'=>'a.jpg','original_filename'=>'a.jpg','filepath'=>'a.jpg','mime_type'=>'image/jpeg','file_size'=>3]); Storage::disk('public')->put('a.jpg','abc');
  $this->deleteJson("/api/v1/admin/albums/{$album->id}")->assertStatus(409);
  $this->assertDatabaseHas('albums',['id'=>$album->id,'deleted_at'=>null]); $this->assertDatabaseHas('photos',['id'=>$photo->id]); Storage::disk('public')->assertExists('a.jpg');
  $this->deleteJson("/api/v1/admin/photos/{$photo->id}")->assertNoContent(); $this->deleteJson("/api/v1/admin/albums/{$album->id}")->assertNoContent(); $this->assertSoftDeleted('albums',['id'=>$album->id]);
 }
 public function test_deleting_photo_keeps_shared_file_and_other_photos(): void {
  Storage::fake('public'); Sanctum::actingAs($this->admin()); $album=$this->album();
  $attributes=['filename'=>'shared.jpg','original_filename'=>'shared.jpg','filepath'=>'shared.jpg','mime_type'=>'image/jpeg','file_size'=>3];
  $one=$album->photos()->create($attributes); $two=$album->photos()->create($attributes); Storage::disk('public')->put('shared.jpg','abc');
  $this->deleteJson("/api/v1/admin/photos/{$one->id}")->assertNoContent(); $this->assertDatabaseHas('photos',['id'=>$two->id]); Storage::disk('public')->assertExists('shared.jpg');
  $this->deleteJson("/api/v1/admin/photos/{$two->id}")->assertNoContent(); Storage::disk('public')->assertMissing('shared.jpg');
 }
 public function test_gallery_orders_filenames_before_paginating(): void {
  $album=$this->album();
  foreach (range(25,1) as $number) { $name=sprintf('DSC%05d.jpg',$number); $album->photos()->create(['filename'=>$name,'original_filename'=>$name,'filepath'=>'photos/'.$name,'mime_type'=>'image/jpeg','file_size'=>1]); }
  $this->getJson('/api/v1/photos?page=1')->assertOk()->assertJsonCount(24,'data')->assertJsonPath('data.0.original_filename','DSC00001.jpg')->assertJsonPath('data.23.original_filename','DSC00024.jpg');
  $this->getJson('/api/v1/photos?page=2')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.original_filename','DSC00025.jpg');
 }
 public function test_private_photos_cannot_be_downloaded(): void { Storage::fake('public'); $a=$this->album(); $a->update(['is_published'=>false]); $p=$a->photos()->create(['filename'=>'a.jpg','original_filename'=>'a.jpg','filepath'=>'a.jpg','mime_type'=>'image/jpeg','file_size'=>3]); Storage::disk('public')->put('a.jpg','abc'); $this->get("/api/v1/photos/{$p->id}/download")->assertNotFound(); }
}
