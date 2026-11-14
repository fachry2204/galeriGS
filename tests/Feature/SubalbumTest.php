<?php
namespace Tests\Feature;
use App\Models\{Album,Category,Photo,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class SubalbumTest extends TestCase {
 use RefreshDatabase;
 private function admin(): void { Sanctum::actingAs(User::create(['name'=>'Admin','username'=>'admin','email'=>'admin@example.test','password'=>'password-123','role'=>'admin','must_change_password'=>false])); }
 private function album(Category $category,string $slug,bool $published=true,?int $parent=null): Album { return Album::create(['category_id'=>$category->id,'title'=>$slug,'slug'=>$slug,'is_published'=>$published,'parent_id'=>$parent]); }
 public function test_admin_can_create_subalbum_but_not_nested_or_cyclic(): void {
  $this->admin();$category=Category::create(['name'=>'Test','slug'=>'test']);$root=$this->album($category,'utama');
  $response=$this->postJson('/api/v1/admin/albums',['title'=>'Anak','slug'=>'anak','category_id'=>$category->id,'parent_id'=>$root->id,'is_published'=>true])->assertCreated();$child=Album::findOrFail($response->json('id'));$this->assertSame($root->id,$child->parent_id);
  $this->postJson('/api/v1/admin/albums',['title'=>'Cucu','slug'=>'cucu','category_id'=>$category->id,'parent_id'=>$child->id])->assertUnprocessable();
  $this->putJson("/api/v1/admin/albums/{$root->id}",['title'=>'Utama','slug'=>'utama','category_id'=>$category->id,'parent_id'=>$root->id])->assertUnprocessable();
  $other=$this->album($category,'lain');$this->putJson("/api/v1/admin/albums/{$root->id}",['title'=>'Utama','slug'=>'utama','category_id'=>$category->id,'parent_id'=>$other->id])->assertUnprocessable();
  $this->deleteJson("/api/v1/admin/albums/{$root->id}")->assertStatus(409);
 }
 public function test_only_published_children_of_published_parents_are_visible(): void {
  Storage::fake('public');$category=Category::create(['name'=>'Test','slug'=>'test']);$root=$this->album($category,'utama');$child=$this->album($category,'anak',true,$root->id);$draft=$this->album($category,'draft',false,$root->id);
  $photo=$child->photos()->create(['filename'=>'a.jpg','original_filename'=>'a.jpg','filepath'=>'a.jpg','mime_type'=>'image/jpeg','file_size'=>3]);Storage::disk('public')->put('a.jpg','abc');
  $this->getJson('/api/v1/albums/utama/children')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.slug','anak');
  $this->getJson('/api/v1/albums')->assertOk()->assertJsonFragment(['slug'=>'utama','children_count'=>1]);
  $this->getJson('/api/v1/albums/anak')->assertOk()->assertJsonPath('parent.slug','utama');
  $this->getJson('/api/v1/albums/draft')->assertNotFound();$this->getJson('/api/v1/photos')->assertJsonCount(1,'data');
  $root->update(['is_published'=>false]);$this->getJson('/api/v1/albums')->assertJsonCount(0,'data');$this->getJson('/api/v1/albums/anak')->assertNotFound();$this->getJson('/api/v1/albums/anak/photos')->assertNotFound();$this->getJson('/api/v1/photos')->assertJsonCount(0,'data');$this->get("/api/v1/photos/{$photo->id}/download")->assertNotFound();
 }
 public function test_home_album_listing_only_returns_roots_with_preview_photo(): void {
  $category=Category::create(['name'=>'Test','slug'=>'test']);$root=$this->album($category,'utama');$child=$this->album($category,'anak',true,$root->id);$this->album($category,'draft',false);
  $root->photos()->create(['filename'=>'one.jpg','original_filename'=>'one.jpg','filepath'=>'photos/one.jpg','thumbnail_path'=>'thumbnails/one.jpg','mime_type'=>'image/jpeg','file_size'=>3]);
  $this->getJson('/api/v1/albums?root_only=1')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.slug','utama')->assertJsonPath('data.0.children_count',1)->assertJsonPath('data.0.preview_photo.thumbnail_path','thumbnails/one.jpg');
  $this->getJson('/api/v1/albums')->assertOk()->assertJsonCount(2,'data');
  $root->update(['is_published'=>false]);$this->getJson('/api/v1/albums?root_only=1')->assertOk()->assertJsonCount(0,'data');
 }
 public function test_upload_returns_thumbnail_for_immediate_preview(): void {
  Storage::fake('public');$this->admin();$category=Category::create(['name'=>'Test','slug'=>'test']);$root=$this->album($category,'utama');$child=$this->album($category,'anak',true,$root->id);
  $response=$this->postJson("/api/v1/admin/albums/{$child->id}/photos",['photos'=>[UploadedFile::fake()->image('foto.jpg',1600,1000)]])->assertCreated();
  $path=$response->json('0.thumbnail_path');$this->assertNotEmpty($path);Storage::disk('public')->assertExists($path);$this->assertStringContainsString('/storage/'.$path,$response->json('0.thumbnail_url'));
 }
}
