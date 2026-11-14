<?php
namespace Database\Seeders;
use App\Models\{User,Category};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder { public function run(): void {
 foreach (['Pemerintahan','Kegiatan Masyarakat','Event Jakarta','Budaya dan Tradisi','Pembangunan','Wisata Jakarta','Pendidikan','Infrastruktur','Dokumentasi Umum'] as $name) Category::firstOrCreate(['slug'=>Str::slug($name)],['name'=>$name]);
 $password=env('INITIAL_ADMIN_PASSWORD'); if (!$password) { $this->command?->warn('Set INITIAL_ADMIN_PASSWORD to create the initial administrator.'); return; }
 User::firstOrCreate(['username'=>env('INITIAL_ADMIN_USERNAME','admin')],['name'=>'Administrator','email'=>env('INITIAL_ADMIN_EMAIL','admin@example.test'),'role'=>'admin','must_change_password'=>true,'password'=>Hash::make($password)]);
} }
