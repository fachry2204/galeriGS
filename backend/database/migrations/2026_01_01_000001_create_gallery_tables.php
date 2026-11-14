<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::table('users', function(Blueprint $t) { $t->string('username')->unique(); $t->string('role')->default('admin'); $t->boolean('must_change_password')->default(true); });
  Schema::create('categories', function(Blueprint $t) { $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->text('description')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps(); });
  Schema::create('albums', function(Blueprint $t) { $t->id(); $t->foreignId('category_id')->constrained()->restrictOnDelete(); $t->string('title'); $t->string('slug')->unique(); $t->text('description')->nullable(); $t->date('event_date')->nullable()->index(); $t->string('location')->nullable(); $t->unsignedBigInteger('cover_photo_id')->nullable(); $t->boolean('is_featured')->default(false); $t->boolean('is_published')->default(false)->index(); $t->timestamp('published_at')->nullable(); $t->timestamps(); $t->softDeletes(); });
  Schema::create('photos', function(Blueprint $t) { $t->id(); $t->foreignId('album_id')->constrained()->cascadeOnDelete(); $t->string('filename'); $t->string('original_filename'); $t->string('filepath'); $t->string('thumbnail_path')->nullable(); $t->string('mime_type'); $t->unsignedBigInteger('file_size'); $t->unsignedInteger('width')->nullable(); $t->unsignedInteger('height')->nullable(); $t->unsignedInteger('sort_order')->default(0); $t->timestamps(); $t->index(['album_id','sort_order']); });
  Schema::table('albums', function(Blueprint $t) { $t->foreign('cover_photo_id')->references('id')->on('photos')->nullOnDelete(); });
  Schema::create('settings', function(Blueprint $t) { $t->id(); $t->string('key')->unique(); $t->text('value')->nullable(); $t->timestamps(); });
  Schema::create('activity_logs', function(Blueprint $t) { $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $t->string('action'); $t->text('description')->nullable(); $t->string('ip_address',45)->nullable(); $t->timestamps(); });
 }
 public function down(): void { Schema::dropIfExists('activity_logs'); Schema::dropIfExists('settings'); Schema::table('albums',fn(Blueprint $t)=>$t->dropForeign(['cover_photo_id'])); Schema::dropIfExists('photos'); Schema::dropIfExists('albums'); Schema::dropIfExists('categories'); Schema::table('users',function(Blueprint $t){$t->dropColumn(['username','role','must_change_password']);}); }
};
