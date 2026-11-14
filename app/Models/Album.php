<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
class Album extends Model {
 use SoftDeletes;
 protected $guarded=[];
 protected $appends=['cover_url'];
 public function getCoverUrlAttribute(){return $this->cover_path ? url(\Illuminate\Support\Facades\Storage::disk('public')->url($this->cover_path)) : null;}
 protected $casts=['is_featured'=>'boolean','is_published'=>'boolean','event_date'=>'date','published_at'=>'datetime'];
 public function category(){return $this->belongsTo(Category::class);}
 public function photos(){return $this->hasMany(Photo::class)->orderBy('sort_order')->orderBy('id');}
 public function cover(){return $this->belongsTo(Photo::class,'cover_photo_id');}
 public function previewPhoto(){return $this->hasOne(Photo::class)->oldestOfMany();}
 public function parent(){return $this->belongsTo(Album::class,'parent_id');}
 public function children(){return $this->hasMany(Album::class,'parent_id');}
 public function scopeVisible(Builder $query): Builder {return $query->where('is_published',true)->where(fn($q)=>$q->whereNull('parent_id')->orWhereHas('parent',fn($q)=>$q->where('is_published',true)));}
}
