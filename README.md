# Product Claim

Aplikasi klaim produk karyawan. User memilih produk, mengajukan klaim, dan melihat progres. Admin mengelola katalog, harga, diskon, user, dan status klaim. Semua nominal dalam Rupiah.

## Stack

- Laravel 13, PHP 8.3+
- Vue 3 dan Vue Router, di-build dengan Vite
- MySQL untuk data
- Redis untuk session, cache katalog, keranjang, dan queue

## Menjalankan

Butuh MySQL dan Redis di mesin lokal. Salin environment, lalu isi kredensial database di `.env`. Jangan menyimpan password di `.env.example`.

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Buat database `productclaim`, sesuaikan `DB_*` di `.env`, lalu:

```sh
php artisan migrate --force
php artisan storage:link
php artisan db:seed
npm install
npm run build
php artisan serve
```

Aplikasi terbuka di `http://localhost:8000`. `php artisan serve` memakai aset di `public/build`, jadi jalankan `npm run build` lagi setelah mengubah frontend. Untuk hot reload, jalankan `npm run dev` di terminal terpisah.

Redis memakai Predis, bukan phpredis. Di `.env`:

```
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_USERNAME=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

`SESSION_DRIVER`, `CACHE_STORE`, dan `QUEUE_CONNECTION` memakai `redis`.

## Akun seed

Password kedua akun ini `password`.


| Email                                           | Peran |
| ----------------------------------------------- | ----- |
| [aeris@claimly.test](mailto:aeris@claimly.test) | Admin |
| [dina@claimly.test](mailto:dina@claimly.test)   | User  |


Seeder juga mengisi tiga brand (Aeris Beauté, From This Island, KIN + ALLY), produknya, dan periode Oktober 2026 dengan diskon 30%.

## Peran

User bisa membuka katalog, menaruh produk di keranjang, mengajukan klaim, dan melihat progres klaimnya sendiri. Admin bisa semua itu, plus mengelola kategori, produk, harga, diskon, user, dan status klaim.

Halaman admin yang mengikuti brand (produk, kategori, harga, diskon) memakai kartu brand di atas filter. User dan klaim bersifat global.


| Halaman            | Siapa                 |
| ------------------ | --------------------- |
| `/login`           | Tamu                  |
| `/`                | Katalog dan keranjang |
| `/progres`         | Progres klaim sendiri |
| `/kelola/produk`   | Admin                 |
| `/kelola/kategori` | Admin                 |
| `/kelola/harga`    | Admin                 |
| `/kelola/diskon`   | Admin                 |
| `/kelola/user`     | Admin                 |
| `/kelola/klaim`    | Admin                 |




## Klaim

Harga klaim adalah harga retail setelah diskon periode. Contoh diskon 30% pada retail Rp 69.000 menjadi Rp 48.300.

Keranjang disimpan di Redis per user. Mengajukan klaim membuat nomor `CLM-{tahun}{bulan}-{urutan}` dan menyalin item ke klaim. Status berjalan dari `diajukan`, `diproses`, `disetujui`, lalu `selesai`. Admin juga bisa menandai `ditolak`.

Gambar produk dilampirkan sebagai JPG, PNG, atau WebP (maksimal 5 MB) dan disimpan lewat disk `public`. Produk tanpa gambar menampilkan placeholder kotak. Logo brand ada di `public/logo-brand`.

## Tes

Tes memakai SQLite di memori, jadi MySQL dan Redis tidak perlu hidup.

```sh
php artisan test
```

