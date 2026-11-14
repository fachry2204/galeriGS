<?php
namespace Database\Seeders;
use App\Models\{Album, Category, Photo};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
class SampleGallerySeeder extends Seeder {
 public function run(): void {
  $category=Category::firstOrCreate(['slug'=>'dokumentasi-umum'],['name'=>'Dokumentasi Umum']);
  $album=Album::firstOrCreate(['slug'=>'galeri-contoh'],['category_id'=>$category->id,'title'=>'Galeri Contoh','description'=>'Foto contoh untuk memperkenalkan tampilan galeri. Koleksi ini dapat diganti melalui panel admin.','is_published'=>true,'published_at'=>now()]);
  for($i=1;$i<=6;$i++) { $name="contoh-$i.jpg"; $source=database_path("seeders/sample_photos/$name"); if(!is_file($source)) { $this->command?->warn("Berkas tidak ditemukan: $name"); continue; } $path="sample-gallery/$name"; if(!Storage::disk('public')->exists($path)) Storage::disk('public')->put($path,file_get_contents($source)); [$width,$height]=getimagesize($source); $photo=Photo::firstOrCreate(['album_id'=>$album->id,'filepath'=>$path],['filename'=>$name,'original_filename'=>$name,'mime_type'=>'image/jpeg','file_size'=>filesize($source),'width'=>$width,'height'=>$height,'sort_order'=>$i]); if($i===1 && !$album->cover_photo_id) $album->update(['cover_photo_id'=>$photo->id]); }
 }
}
