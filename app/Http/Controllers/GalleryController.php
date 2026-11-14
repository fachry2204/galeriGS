<?php
namespace App\Http\Controllers;
use App\Models\{User,Album,Photo,Category,Setting,ActivityLog};
use App\Services\ThumbnailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash,Storage,DB};
use Illuminate\Validation\Rule;
class GalleryController extends Controller {
 public function login(Request $r) { $v=$r->validate(['username'=>'required|string','password'=>'required|string']); $u=User::where('username',$v['username'])->where('role','admin')->first(); if (!$u || !Hash::check($v['password'],$u->password)) return response()->json(['message'=>'Username atau password salah.'],422); ActivityLog::create(['user_id'=>$u->id,'action'=>'login','ip_address'=>$r->ip()]); return ['token'=>$u->createToken('admin')->plainTextToken,'user'=>$u]; }
 public function me(Request $r) { return $r->user(); }
 public function logout(Request $r) { $r->user()->currentAccessToken()->delete(); return response()->noContent(); }
 public function password(Request $r) { $v=$r->validate(['current_password'=>'required|current_password','password'=>'required|string|min:12|confirmed']); $r->user()->update(['password'=>Hash::make($v['password']),'must_change_password'=>false]); $r->user()->tokens()->where('id','!=',$r->user()->currentAccessToken()->id)->delete(); return ['message'=>'Password diperbarui']; }
 public function albums(Request $r) { $q=Album::visible()->with(['category','cover','previewPhoto','parent:id,title,slug'])->withCount(['photos','children'=>fn($q)=>$q->visible()]); if($r->boolean('root_only')) $q->whereNull('parent_id'); if($r->filled('search')) $q->where('title','like','%'.$r->search.'%'); if($r->filled('category')) $q->whereHas('category',fn($q)=>$q->where('slug',$r->category)); if($r->filled('year')) $q->whereYear('event_date',$r->year); return $q->orderBy('event_date',$r->sort==='oldest'?'asc':'desc')->orderBy('id',$r->sort==='oldest'?'asc':'desc')->paginate(12); }
 public function photos(Request $r) { $q=Photo::whereHas('album', fn($q)=>$q->visible())->with(['album.category']); if ($r->filled('category')) $q->whereHas('album.category', fn($q)=>$q->where('slug',$r->category)); if ($r->filled('search')) $q->where(fn($q)=>$q->where('original_filename','like','%'.$r->search.'%')->orWhereHas('album',fn($q)=>$q->where('title','like','%'.$r->search.'%'))); return $q->orderBy('original_filename')->orderBy('id')->paginate(24); }
 public function album(string $slug) { return Album::where('slug',$slug)->visible()->with(['category','cover','parent:id,title,slug'])->withCount('photos')->firstOrFail(); }
 public function albumPhotos(string $slug) { $a=Album::where('slug',$slug)->visible()->firstOrFail(); return $a->photos()->paginate(24); }
 public function albumChildren(string $slug) { $a=Album::where('slug',$slug)->visible()->firstOrFail(); return $a->children()->visible()->with(['category','cover','previewPhoto'])->withCount('photos')->orderByDesc('event_date')->orderBy('id')->paginate(12); }
 public function categories() { return Category::where('is_active',true)->withCount(['albums'=>fn($q)=>$q->visible()])->get(); }
 public function settings() { return Setting::pluck('value','key'); }
 public function download(Photo $photo) { abort_unless(Album::visible()->whereKey($photo->album_id)->exists(),404); abort_unless(Storage::disk('public')->exists($photo->filepath),404); return Storage::disk('public')->download($photo->filepath,basename($photo->original_filename)); }
 public function adminAlbums() { return Album::with(['category','cover','parent:id,title'])->withCount(['photos','children'])->latest()->paginate(20); }
 public function albumParents(){ return Album::whereNull('parent_id')->orderBy('title')->get(['id','title','category_id']); }
 public function saveAlbum(Request $r, ?Album $album=null) { $album ??= new Album; $v=$r->validate(['parent_id'=>['nullable','integer',Rule::exists('albums','id')->whereNull('parent_id')->whereNull('deleted_at'),Rule::notIn([$album->id])],'category_id'=>'required|exists:categories,id','title'=>'required|string|max:255','slug'=>['required','alpha_dash','max:255',Rule::unique('albums')->ignore($album->id)],'description'=>'nullable|string','event_date'=>'nullable|date','location'=>'nullable|string|max:255','is_featured'=>'boolean','is_published'=>'boolean','cover_photo_id'=>['nullable',Rule::exists('photos','id')->where('album_id',$album->id)]]); if (!empty($v['parent_id']) && $album->exists && $album->children()->exists()) return response()->json(['message'=>'Album yang memiliki subalbum tidak dapat dijadikan subalbum.'],422); if(($v['is_published']??false) && !$album->published_at) $v['published_at']=now(); $album->fill($v)->save(); return response()->json($album->load(['category','cover']),$album->wasRecentlyCreated?201:200); }
 public function uploadAlbumCover(Request $r, Album $album, ThumbnailService $thumbnails) {
  $v=$r->validate(['cover'=>'required|image|mimes:jpeg,jpg,png,webp|max:20480']);
  try { $path=$thumbnails->createFromFile($v['cover']->getRealPath(),'album-covers/'.$album->id); }
  catch (\Throwable $e) { report($e); return response()->json(['message'=>'Gambar sampul tidak dapat diproses. Periksa format file dan konfigurasi PHP GD.'],422); }
  $old=$album->cover_path;
  try { $album->update(['cover_path'=>$path,'cover_photo_id'=>null]); }
  catch (\Throwable $e) { Storage::disk('public')->delete($path); throw $e; }
  if ($old && $old!==$path) { try { $this->removeAlbumCoverFile($old,$album->id); } catch (\Throwable $e) { report($e); } }
  return $album->fresh()->load(['category','cover','parent']);
 }
 public function selectAlbumCover(Request $r, Album $album) {
  $v=$r->validate(['photo_id'=>['required','integer',Rule::exists('photos','id')->where('album_id',$album->id)]]);
  $old=$album->cover_path;
  $album->update(['cover_path'=>null,'cover_photo_id'=>$v['photo_id']]);
  if ($old) { try { $this->removeAlbumCoverFile($old,$album->id); } catch (\Throwable $e) { report($e); } }
  return $album->fresh()->load(['category','cover','parent']);
 }
 private function removeAlbumCoverFile(?string $path, int $albumId): void {
  if (!$path) return;
  $shared=Album::withTrashed()->whereKeyNot($albumId)->where('cover_path',$path)->exists() || Photo::where(fn($q)=>$q->where('filepath',$path)->orWhere('thumbnail_path',$path))->exists();
  $disk=Storage::disk('public');
  if (!$shared && $disk->exists($path) && !$disk->delete($path)) throw new \RuntimeException('Gagal menghapus sampul album.');
 }
 public function adminAlbum(Album $album) { return $album->load(['category','cover','photos','parent'])->loadCount('children'); }
 public function deleteAlbum(Album $album) {
  if ($album->children()->exists()) return response()->json(['message'=>'Album masih memiliki subalbum. Pindahkan atau hapus subalbum terlebih dahulu.'],409);
  try {
   $album->photos()->reorder()->chunkById(50, function ($photos) {
    foreach ($photos as $photo) $this->removePhoto($photo);
   });
   if ($album->photos()->exists()) return response()->json(['message'=>'Masih ada foto di album. Coba hapus kembali.'],409);
   $this->removeAlbumCoverFile($album->cover_path,$album->id);
   $album->delete();
   return response()->noContent();
  } catch (\Throwable $e) {
   report($e);
   return response()->json(['message'=>'Penghapusan tidak selesai. Sebagian foto mungkin sudah terhapus; periksa album dan izin penyimpanan sebelum mencoba kembali.'],500);
  }
 }
 private function removePhoto(Photo $photo) {
  DB::transaction(function() use ($photo) {
   $locked=Photo::whereKey($photo->id)->lockForUpdate()->firstOrFail();
   $paths=array_unique(array_filter([$locked->thumbnail_path,$locked->filepath]));
   $disk=Storage::disk('public');
   foreach ($paths as $path) {
    $shared=Photo::whereKeyNot($locked->id)->where(fn($q)=>$q->where('filepath',$path)->orWhere('thumbnail_path',$path))->exists();
    if (!$shared && $disk->exists($path) && !$disk->delete($path)) throw new \RuntimeException('Gagal menghapus file foto dari penyimpanan.');
   }
   Album::withTrashed()->where('cover_photo_id',$locked->id)->update(['cover_photo_id'=>null]);
   $locked->delete();
  });
 }
 public function deletePhoto(Photo $photo) { $this->removePhoto($photo); return response()->noContent(); }
 public function bulkDelete(Request $r) { $v=$r->validate(['ids'=>'required|array|max:100','ids.*'=>'required|integer|exists:photos,id']); foreach(Photo::whereIn('id',$v['ids'])->get() as $photo) $this->removePhoto($photo); return response()->noContent(); }
 public function reorder(Request $r) { $v=$r->validate(['album_id'=>'required|exists:albums,id','ids'=>'required|array|max:1000','ids.*'=>'integer|distinct']); $photos=Photo::where('album_id',$v['album_id'])->whereIn('id',$v['ids'])->count(); abort_unless($photos===count($v['ids']),422); foreach($v['ids'] as $i=>$id) Photo::where('id',$id)->update(['sort_order'=>$i]); return response()->noContent(); }
 public function upload(Request $r, Album $album) { $v=$r->validate(['photos'=>'required|array|max:10','photos.*'=>'required|image|mimes:jpeg,jpg,png,webp|max:20480']); $out=[]; foreach($v['photos'] as $file) { $path=$file->store('photos/'.$album->id,'public'); [$w,$h]=getimagesize($file->getRealPath()); $photo=$album->photos()->create(['filename'=>basename($path),'original_filename'=>$file->getClientOriginalName(),'filepath'=>$path,'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),'width'=>$w,'height'=>$h,'sort_order'=>($album->photos()->max('sort_order')??-1)+1]); try { app(ThumbnailService::class)->generate($photo); } catch (\Throwable $e) { report($e); } $out[]=$photo; } return response()->json($out,201); }
 public function adminCategories(){return Category::withCount(['albums', 'albums as albums_total_count'=>fn($q)=>$q->withTrashed()])->get();}
 public function saveCategory(Request $r, ?Category $category=null){$category ??= new Category; $v=$r->validate(['name'=>'required|string|max:255','slug'=>['required','alpha_dash',Rule::unique('categories')->ignore($category->id)],'description'=>'nullable|string','is_active'=>'boolean']); $category->fill($v)->save(); return $category;}
 public function deleteCategory(Request $r, Category $category) {
  $v=$r->validate(['replacement_category_id'=>['nullable','integer',Rule::exists('categories','id'),Rule::notIn([$category->id])]]);
  return DB::transaction(function() use($category,$v) {
   $category->refresh();
   $related=Album::withTrashed()->where('category_id',$category->id);
   if ($related->exists()) {
    if (empty($v['replacement_category_id'])) return response()->json(['message'=>'Kategori masih digunakan oleh album, termasuk album yang sudah dihapus. Pilih kategori pengganti.'],409);
    $replacement=Category::whereKey($v['replacement_category_id'])->lockForUpdate()->first();
    if (!$replacement) return response()->json(['message'=>'Kategori pengganti tidak ditemukan.'],422);
    $related->update(['category_id'=>$replacement->id]);
   }
   $category->delete();
   return response()->noContent();
  });
 }
 public function updateSettings(Request $r){$v=$r->validate(['settings'=>'required|array','settings.*'=>'nullable|string|max:2000']); foreach($v['settings'] as $key=>$value) { if(in_array($key,['site_name','description','copyright','email','instagram','facebook','theme_color','hero_image','logo','favicon'])) Setting::updateOrCreate(['key'=>$key],['value'=>$value]); } return $this->settings();}
 public function statistics(){return ['albums'=>Album::count(),'photos'=>Photo::count(),'categories'=>Category::count(),'recent'=>Album::with('category')->latest()->take(5)->get()];}
 public function activities(){return ActivityLog::with('user:id,name')->latest()->paginate(20);}
}
