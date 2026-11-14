# Galeri Foto Kelurahan Grogol Selatan

Aplikasi Laravel 11 dan Vue 3 dalam satu proyek. Laravel melayani halaman dan API; Vite membangun aset Vue dari resources/js/gallery ke public/build.

## Persyaratan

PHP 8.2 dengan PDO MySQL, GD, dan Fileinfo; Composer 2; Node.js 20.19 atau lebih baru; MySQL 8. Pengembangan lokal diuji dengan MariaDB 10.4. Direktori storage dan bootstrap/cache harus dapat ditulis.

## Instalasi

Jalankan semua perintah dari direktori proyek:

1. Buat database kosong jakarta_photo_gallery dengan charset utf8mb4.
2. Jalankan composer install dan salin .env.example menjadi .env. Isi pengaturan DB_*, APP_URL, INITIAL_ADMIN_USERNAME, INITIAL_ADMIN_EMAIL, dan INITIAL_ADMIN_PASSWORD. Seeder membuat akun hanya jika password disediakan; password di-hash dan wajib diganti setelah login pertama.
3. Jalankan php artisan key:generate, php artisan migrate --seed, dan php artisan storage:link.
4. Jalankan npm install dan npm run build.
5. Jalankan php artisan serve, lalu buka http://127.0.0.1:8000. Login admin tersedia pada /admin. Untuk hot reload, jalankan npm run dev di terminal kedua dan tetap buka URL Laravel di port 8000.
6. Pada XAMPP Windows, start-local-server.ps1 menjalankan server dengan PHP di D:\xampp\php dan batas 20 MB per foto. Pada server lain atur upload_max_filesize minimal 21M, post_max_size minimal 22M, dan batas request web server yang sesuai.

Seeder tidak mengubah password akun yang sudah ada. Hapus password awal dari .env setelah seeding. Jangan commit .env, database, atau foto pengguna.

## Pemeriksaan

Jalankan npm run build dan php artisan test. Tes backend menggunakan SQLite in-memory, bukan database lokal. Jalankan php artisan db:seed --class=SampleGallerySeeder untuk menambahkan album contoh; foto contoh adalah ilustrasi perkotaan dan tidak semuanya menggambarkan Jakarta.

## Deployment Linux (belum diverifikasi)

Atur document root web server ke public, dengan fallback request ke public/index.php. Buat .env produksi dengan APP_DEBUG=false, APP_URL, database, dan HTTPS. Jalankan instalasi dependensi, migrasi, build Vite, serta storage:link; berikan hak tulis minimum ke storage dan bootstrap/cache. Atur batas upload PHP dan web server.

## Batasan

Proyek masih dalam pengembangan. Thumbnail, antrean, pengaturan melalui UI, pengurutan dan hapus massal foto di UI, zoom/swipe, serta pengujian menyeluruh belum selesai. File pada storage/app/public dapat diakses siapa pun yang mengetahui URL, termasuk foto album draft. Gunakan penyimpanan privat dengan otorisasi sebelum menyimpan foto nonpublik.

Laravel 11 yang disyaratkan memiliki advisory keamanan. Lockfile dibuat dengan pengecualian pemeriksaan Composer; ini tidak memperbaiki advisory. Tinjau composer audit dan rencanakan upgrade ke versi yang didukung sebelum produksi.

Foto contoh bersumber dari Unsplash. Lambang DKI Jakarta pada header menggunakan gambar ulang berdasarkan lambang resmi dari Wikimedia Commons: https://commons.wikimedia.org/wiki/File:Coat_of_arms_of_Jakarta.svg
