# Panduan Menjalankan Aplikasi DocPad

Dokumen ini berisi langkah-langkah lengkap untuk menjalankan lingkungan lokal **DocPad** (Laravel 12 + Inertia Vue + MariaDB) menggunakan Docker.

---

## 1. Menyalakan Container Docker

Pastikan aplikasi **Docker Desktop** sudah dalam keadaan menyala (*Running*), lalu buka terminal dan masuk ke folder root project (direktori tempat file `docker-compose.yml` berada).

Jalankan seluruh service (`app`, `nginx`, dan `db`) di latar belakang:

```bash
docker compose up -d
```

*(Catatan: Jika suatu saat ada perubahan pada `Dockerfile`, gunakan `docker compose up -d --build`).*

Untuk memastikan ketiga container (`docpad_app`, `docpad_nginx`, `docpad_db`) sudah berjalan dengan normal:

```bash
docker compose ps
```

---

## 2. Verifikasi Koneksi Database & Migrasi

Pastikan container MariaDB (`docpad_db`) sudah siap menerima koneksi dari Laravel:

```bash
docker compose exec app php artisan db:show
```

Jika belum melakukan migrasi atau pengisian akun Admin awal, jalankan:

```bash
docker compose exec app php artisan migrate --seed
```

---

## 3. Menjalankan Frontend Dev Server (Vite HMR)

Agar perubahan pada tampilan Vue (`src/resources/js/...`) dan CSS langsung ter-compile serta ter-refresh otomatis di browser, jalankan Vite dev server:

```bash
docker compose exec app npm run dev
```

> **Penting:** Biarkan tab terminal ini **tetap terbuka dan berjalan** selama kamu mengakses atau mengembangkan aplikasi. Jika ingin menjalankan perintah `php artisan` atau `composer` lainnya, buka tab terminal baru.

*(Alternatif: Jika kamu hanya ingin membuka aplikasi tanpa melakukan perubahan kode frontend dan tidak ingin menyalakan `npm run dev` terus-menerus, jalankan sekali perintah `docker compose exec app npm run build`).*

---

## 4. Mengakses Aplikasi di Browser

Setelah container menyala dan Vite berjalan, buka browser dan akses alamat berikut:

- **URL Aplikasi:** `http://localhost:8000`
- **Halaman Login:** `http://localhost:8000/login`

### Kredensial Akun Admin Awal (Hasil Seeder)
- **Email:** `admin@docpad.local`
- **Password:** `password123`

---

## 5. Menghentikan Aplikasi Setelah Selesai

Jika sudah selesai bekerja dan ingin mematikan container tanpa menghapus data di database:

```bash
docker compose stop
```

*(Hindari menggunakan `docker compose down -v` kecuali kamu memang ingin menghapus seluruh volume database dan mengulang migrasi dari nol).*

---

## Perintah Bantuan (Troubleshooting)

- **Membersihkan cache konfigurasi/route Laravel:**
  ```bash
  docker compose exec app php artisan optimize:clear
  ```
- **Memperbaiki permission folder storage (jika muncul error *Permission Denied*):**
  ```bash
  docker compose exec app chown -R www-data:www-data storage bootstrap/cache
  ```