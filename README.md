# Product Claim

Aplikasi employee purchase untuk Aeris Beauté, From This Island, dan KIN + ALLY. Karyawan memesan produk dengan diskon per brand, pesanan otomatis dipisah menjadi satu invoice per brand, lalu berjalan melewati cek stok, pembayaran, verifikasi Finance, gudang, sampai selesai. Semua nominal dalam Rupiah.

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

`SESSION_DRIVER`, `CACHE_STORE`, dan `QUEUE_CONNECTION` memakai `redis`. Notifikasi dikirim langsung (tidak lewat queue), jadi tidak perlu worker.

## Akun seed

Password kedua akun ini `password`.

| Email                                           | Peran | Status                                 |
| ----------------------------------------------- | ----- | -------------------------------------- |
| [aeris@claimly.test](mailto:aeris@claimly.test) | Admin | Tetap, PT Citra Pasifik Kosmetik       |
| [dina@claimly.test](mailto:dina@claimly.test)   | User  | Kontrak, PT Kinerja Inovasi Nusantara  |

Seeder juga mengisi tiga brand beserta ketentuannya, produk, dan periode Oktober 2026.

## Kebijakan

| Brand            | PT                           | Diskon dari RSP |
| ---------------- | ---------------------------- | --------------- |
| Aeris Beauté     | PT Citra Pasifik Kosmetik    | 40%             |
| From This Island | PT Inovasi Alam Nusantara    | 30%             |
| KIN + ALLY       | PT Kinerja Inovasi Nusantara | 30%             |

Berlaku untuk karyawan tetap, kontrak, daily worker, dan intern. Setiap karyawan punya satu kuota 5 pcs per tahun yang dipakai bersama untuk semua brand dan produk, jadi barang yang sama boleh dibeli berulang selama kuotanya masih ada. Kuota reset otomatis setiap 1 Januari pukul 00.00 WIB, tanpa pengajuan. Diskon, ongkir, dan rekening tujuan tiap brand diatur admin di `/kelola/diskon`. Rekening masih kosong setelah migrasi, jadi isi dulu sebelum dipakai.

## Alur pesanan

1. Karyawan memasukkan produk ke keranjang. Sistem langsung menolak jika melewati limit SKU tahunan (pengecekan HR otomatis).
2. Karyawan memilih ambil di gudang (gratis) atau kirim (ongkir per invoice, default ke kantor), lalu mengajukan pesanan. Keranjang dipecah menjadi satu invoice per brand dengan nomor `INV/{brand}/{tahun}{bulan}/{urutan}`, misalnya `INV/AB/202610/0001`.
3. CS mengecek stok. Jika kosong, status menjadi `stok_kosong` dan karyawan menunggu. Jika ada, invoice terbit dan dikirim ke karyawan (cc Finance dan Warehouse).
4. Karyawan transfer ke rekening PT brand, lalu mengunggah bukti (JPG, PNG, WebP, atau PDF, maks. 5 MB) beserta nominalnya.
5. Finance memasukkan nominal yang diterima. Sistem membandingkan dengan tagihan: sesuai lanjut ke gudang, kurang menjadi `kurang_bayar` dan karyawan melunasi sisanya, lebih tetap lanjut ke gudang dan dicatat sebagai refund. Finance juga bisa menolak bukti yang tidak valid.
6. Gudang menyiapkan barang, lalu mengirim (dengan nomor resi opsional) atau menandai siap diambil.
7. Karyawan menekan "Barang sudah diterima", atau admin menandai selesai.

Sisa kuota tampil di header (5 balok: terpakai, di keranjang, dan sisa). Halaman Riwayat Produk menampilkan grafik kuota per brand, tanggal reset, dan semua produk yang pernah dipesan. Tombol tambah di katalog dan keranjang mati begitu kuota habis, termasuk barang yang masih di keranjang. Pesanan yang dibatalkan tidak dihitung ke kuota.

Invoice bisa dicetak atau disimpan sebagai PDF di `/invoices/{id}` setelah stok dicek. Bukti transfer disimpan di disk privat dan hanya bisa dibuka pemilik pesanan atau admin.

## Notifikasi

Karyawan mendapat notifikasi di lonceng header dan email di setiap perubahan status. Admin mendapat notifikasi lonceng, email opsional, dan pesan grup Lark untuk pekerjaan yang perlu ditindaklanjuti (pesanan baru, bukti transfer masuk, siapkan barang).

| Variabel                   | Fungsi                                             |
| -------------------------- | -------------------------------------------------- |
| `LARK_WEBHOOK_URL`         | Webhook bot grup Lark. Kosongkan untuk mematikan.  |
| `LARK_WEBHOOK_SECRET`      | Signature secret bot, jika diaktifkan di Lark.     |
| `CLAIM_NOTIFY_MAIL`        | Email ke karyawan (default `true`).                |
| `CLAIM_NOTIFY_OPS_MAIL`    | Email ke admin (default `true`).                   |
| `CLAIM_YEARLY_LIMIT`       | Kuota pcs per tahun, semua brand (default `5`).    |
| `CLAIM_WAREHOUSE_ADDRESS`  | Alamat pickup yang tampil di keranjang dan invoice. |

Email memakai pengaturan `MAIL_*` biasa. Dengan `MAIL_MAILER=log`, email ditulis ke `storage/logs/laravel.log`.

## Peran

User bisa masuk dengan password atau Google. Login Google hanya berhasil jika email Google itu sudah ada di data karyawan, jadi akun baru tidak dibuat otomatis. User bisa membuka katalog, memesan, mengunggah bukti transfer, melihat progres, dan melihat kuota pembeliannya. Admin bisa semua itu, plus menjalankan tahap CS, Finance, dan Warehouse serta mengelola katalog dan user. Admin yang ingin belanja tanpa menu admin bisa memilih "Belanja sebagai user" di menu akun.

| Halaman            | Siapa                                      |
| ------------------ | ------------------------------------------ |
| `/login`           | Tamu                                       |
| `/`                | Katalog dan keranjang                      |
| `/progres`         | Progres pesanan sendiri                    |
| `/profile/riwayat`    | Profil: grafik kuota dan riwayat produk |
| `/profile/pengaturan` | Profil: data akun dan ganti password    |
| `/kelola/klaim`    | Admin: pesanan per tahap                   |
| `/kelola/produk`   | Admin                                      |
| `/kelola/kategori` | Admin                                      |
| `/kelola/harga`    | Admin                                      |
| `/kelola/diskon`   | Admin: diskon, ongkir, dan rekening brand  |
| `/kelola/user`     | Admin                                      |

Gambar produk dilampirkan sebagai JPG, PNG, atau WebP (maksimal 5 MB) dan disimpan lewat disk `public`. Produk tanpa gambar menampilkan placeholder kotak. Logo brand ada di `public/logo-brand`.

## Deploy

Push ke branch `main` menjalankan GitHub Action `.github/workflows/deploy.yml`. Action membangun image `itaeris/product_claim_app`, mendorongnya ke Docker Hub, lalu lewat SSH: menghentikan container `product_claim_app` jika masih jalan, menghapus container dan image lama, menarik image baru, dan menjalankannya lagi dengan `docker run` di network `product-network`, port `2029`.

Tidak ada container MySQL baru. `DB_HOST` dan `REDIS_HOST` di secret `BACKEND_ENV` harus nama container yang sudah ada di `product-network`, bukan `127.0.0.1`.

Secret yang dipakai:

| Secret | Isi |
| --- | --- |
| `DOCKERHUB_USERNAME` | Username Docker Hub |
| `DOCKERHUB_TOKEN` | Access token Docker Hub |
| `BACKEND_ENV` | Isi file `.env` production, satu baris per variabel |
| `SERVER_HOST` | IP atau hostname server |
| `SERVER_USER` | User SSH yang boleh menjalankan Docker |
| `SERVER_SSH_KEY` | Private key SSH |

Saat container mulai, entrypoint menjalankan `php artisan migrate --force`. Migrasi yang sudah tercatat dilewati. Migrasi baru menambahkan tabel atau kolomnya. Data yang sudah ada tidak dihapus.

PDF bukti transfer dan gambar katalog disimpan di disk server, bukan di dalam container:

| Path di server | Isi |
| --- | --- |
| `/DATA/AppData/productclaim/pdf` | Bukti transfer (PDF dan gambar) |
| `/DATA/AppData/productclaim/katalog` | Gambar produk, disajikan lewat `/storage` |

Domain `https://product.aeris-fti.com` diarahkan dari Nginx di host ke container:

```nginx
server {
    listen 443 ssl;
    server_name product.aeris-fti.com;

    location / {
        proxy_pass http://127.0.0.1:2029;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

`APP_URL` di `BACKEND_ENV` diisi `https://product.aeris-fti.com`. File itu dipasang sebagai `.env` Laravel, jadi formatnya sama dengan `.env` lokal.

## Tes

Tes memakai SQLite di memori, jadi MySQL dan Redis tidak perlu hidup.

```sh
php artisan test
```
