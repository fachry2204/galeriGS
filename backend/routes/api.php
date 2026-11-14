<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GalleryController as G;
use Illuminate\Http\Request;
Route::prefix('v1')->group(function () {
 Route::get('photos',[G::class,'photos']); Route::get('albums',[G::class,'albums']); Route::get('albums/{slug}',[G::class,'album']); Route::get('albums/{slug}/photos',[G::class,'albumPhotos']); Route::get('categories',[G::class,'categories']); Route::get('settings',[G::class,'settings']); Route::get('photos/{photo}/download',[G::class,'download']);
 Route::post('admin/login',[G::class,'login'])->middleware('throttle:5,1');
 Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
  Route::get('me',[G::class,'me']); Route::post('logout',[G::class,'logout']); Route::post('change-password',[G::class,'password']);
  Route::middleware(\App\Http\Middleware\ReadyAdmin::class)->group(function() {
   Route::get('dashboard/statistics',[G::class,'statistics']); Route::get('dashboard/activities',[G::class,'activities']);
   Route::get('albums',[G::class,'adminAlbums']); Route::post('albums',[G::class,'saveAlbum']); Route::get('albums/{album}',[G::class,'adminAlbum']); Route::put('albums/{album}',[G::class,'saveAlbum']); Route::delete('albums/{album}',[G::class,'deleteAlbum']); Route::post('albums/{album}/photos',[G::class,'upload']);
   Route::post('photos/bulk-delete',[G::class,'bulkDelete']); Route::put('photos/reorder',[G::class,'reorder']); Route::delete('photos/{photo}',[G::class,'deletePhoto']);
   Route::get('categories',[G::class,'adminCategories']); Route::post('categories',[G::class,'saveCategory']); Route::put('categories/{category}',[G::class,'saveCategory']); Route::delete('categories/{category}',[G::class,'deleteCategory']); Route::put('settings',[G::class,'updateSettings']);
  });
 });
});


