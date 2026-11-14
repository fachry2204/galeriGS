<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
class Photo extends Model { protected $guarded=[]; protected $appends=['url','thumbnail_url']; public function album(){return $this->belongsTo(Album::class);} public function getUrlAttribute(){return url(Storage::disk('public')->url($this->filepath));} public function getThumbnailUrlAttribute(){return url(Storage::disk('public')->url($this->thumbnail_path ?: $this->filepath));} }

