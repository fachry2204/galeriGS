# Jakarta Photo Gallery

Aplikasi dokumentasi foto dengan backend Laravel 11 / Sanctum dan frontend Vue 3 / Vite. **Proyek ini masih dalam pengembangan dan belum siap dipublikasikan.** Lihat keterbatasan di bawah sebelum digunakan.

## Persyaratan

- PHP 8.2 dengan ekstensi Laravel, PDO MySQL, GD, dan Fileinfo; Composer 2
- Node.js dan npm
- MySQL 8 (pengembangan lokal saat ini diuji dengan MariaDB 10.4, bukan MySQL 8)
- Web server dengan akses tulis ke `backend/storage` dan `backend/bootstrap/cache`

## Instalasi lokal

1. Buat database kosong `jakarta_photo_gallery` dengan charset `utf8mb4`.
2. Di `backend`, jalankan `composer install`, salin `.env.example` menjadi `.env`, lalu atur `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`. Atur `APP_URL` ke URL backend yang dapat diakses browser.
3. Atur `INITIAL_ADMIN_USERNAME`, `INITIAL_ADMIN_EMAIL`, dan `INITIAL_ADMIN_PASSWORD` pada `.env`. Gunakan password awal yang diminta dalam brief atau password sementara yang kuat. Seeder tidak membuat akun jika password tidak diisi. Password disimpan sebagai hash dan harus diganti setelah login pertama.
4. Jalankan `php artisan key:generate`, `php artisan migrate --seed`, dan `php artisan storage:link`.
5. Jalankan backend dengan `php artisan serve` (default `http://localhost:8000`). Di Windows XAMPP gunakan `D:\xampp\php\php.exe` jika PHP belum ada dalam PATH.
6. Di `frontend`, jalankan `npm install` dan `npm run dev`. Default API adalah `http://localhost:8000/api/v1`; tetapkan `VITE_API_URL` bila berbeda. Buka URL Vite yang ditampilkan. Login di `/admin/login`.
7. Untuk memeriksa hasil: `npm run build` di frontend dan `php artisan test` di backend. Tes menggunakan SQLite in-memory dan tidak mengubah database lokal.

Seeder tidak mengubah password akun yang sudah ada. Jangan commit `.env` atau kredensial. Setelah seeding, hapus password awal dari `.env`.

## Yang sudah tersedia

- Kategori default dan akun admin yang dipersiapkan dari environment; login Sanctum dengan rate limiting, logout API, dan penggantian password wajib.
- API dasar album, kategori, foto, pengaturan, statistik dan log login; tampilan publik album, filter, detail, preview, dan unduh file asli.
- Panel admin dasar untuk membuat dan mengubah album, mengunggah foto per batch maksimal 10, menghapus foto, mengatur cover, serta mengelola kategori. File original disimpan di disk public Laravel. Penambahan foto tidak mengganti cover.
- Pengujian dasar untuk login, penggantian password, upload tambahan, penghapusan, cover dan unduh.

## Pekerjaan yang belum selesai

Ini belum memenuhi seluruh acceptance criteria. Thumbnail dan pemrosesan antrean belum tersedia; gambar saat ini memakai file asli. Dashboard grafik dan aktivitas di UI, pengaturan website melalui UI, profil, unggah aset pengaturan, foto drag-and-drop/preview, pemilihan hapus massal dan pengurutan pada UI, pagination admin, filter tahun, lightbox zoom/swipe, statistik publik, API Resource/Form Request/policy, cache statistik, dan pengujian responsif serta skenario 100 foto belum selesai. Error handling dan konsistensi database/filesystem saat kegagalan juga masih perlu diperkuat. Endpoint bulk-delete dan reorder tersedia tetapi belum memiliki UI.

File menggunakan `storage/app/public`: siapa pun yang mengetahui URL langsung dapat mengakses file, termasuk file album draft. Pembatasan download API tidak mengamankan URL storage langsung. Gunakan disk privat dan endpoint berotorisasi sebelum menyimpan foto nonpublik. Jangan gunakan untuk produksi sebelum audit keamanan dan pengujian menyeluruh.

Laravel 11 yang disyaratkan brief memiliki advisory keamanan dan Composer saat ini memblokir instalasi defaultnya. Lockfile dibuat dengan `--no-security-blocking`; jangan menganggap itu mengatasi advisory. Tinjau `composer audit`, mitigasi, dan rencanakan upgrade ke versi Laravel yang didukung sebelum produksi. Build belum diverifikasi pada MySQL 8 atau server Linux.

## Deployment Linux (rancangan, belum diverifikasi)

Tempatkan backend di luar direktori publik dengan document root Nginx/Apache ke `backend/public`; layani hasil `frontend/dist` pada host frontend dan arahkan fallback SPA ke `index.html`. Buat `.env` produksi dengan `APP_DEBUG=false`, `APP_URL`, database, secret acak, HTTPS, serta konfigurasi CORS hanya untuk origin frontend. Jalankan migrasi, seeder sekali, `storage:link`, build frontend, dan berikan permission minimum ke direktori storage/cache. Atur batas ukuran upload PHP dan web server sesuai batch. Pastikan konfigurasi storage privat dan audit keamanan dituntaskan sebelum membuka akses publik.

### Foto contoh

Halaman utama menampilkan foto dari album yang dipublikasikan. Untuk memasang ulang data contoh, jalankan `php artisan db:seed --class=SampleGallerySeeder` dari direktori backend. Seeder menambahkan enam foto contoh dari Unsplash ke album **Galeri Contoh** tanpa menghapus album lain. Foto contoh merupakan ilustrasi suasana perkotaan dan tidak semuanya menggambarkan Jakarta. Foto dapat diganti melalui panel admin. Sumber berkas: [1](https://images.unsplash.com/photo-1555899434-94d1368aa7af), [2](https://images.unsplash.com/photo-1519501025264-65ba15a82390), [3](https://images.unsplash.com/photo-1449824913935-59a10b8d2000), [4](https://images.unsplash.com/photo-1519608487953-e999c86e7455), [5](https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b), [6](https://images.unsplash.com/photo-1477959858617-67f85cf4f1df).

Untuk menjalankan server lokal dengan batas foto 20 MB, jalankan skrip backend/start-local-server.ps1. Skrip ini menetapkan upload_max_filesize=21M dan post_max_size=22M. Pada server Apache atau Nginx, sesuaikan juga batas PHP dan ukuran request web server.

Lambang DKI Jakarta pada header menggunakan berkas "Coat of arms of Jakarta.svg" dari Wikimedia Commons (gambar ulang berdasarkan lambang resmi): https://commons.wikimedia.org/wiki/File:Coat_of_arms_of_Jakarta.svg
