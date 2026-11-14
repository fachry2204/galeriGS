<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Album extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['is_featured'=>'boolean','is_published'=>'boolean','event_date'=>'date','published_at'=>'datetime']; public function category(){return $this->belongsTo(Category::class);} public function photos(){return $this->hasMany(Photo::class)->orderBy('sort_order')->orderBy('id');} public function cover(){return $this->belongsTo(Photo::class,'cover_photo_id');} }
