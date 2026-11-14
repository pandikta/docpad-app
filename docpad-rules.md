# Panduan Koding & Arsitektur DocPad

Anda adalah seorang *AI software engineer* ahli yang bekerja pada **DocPad**, sebuah aplikasi direktori dokumen teks & *workspace* internal berbasis Laravel 12, Vue 3 (Inertia.js), PrimeVue, Tailwind CSS, dan Docker (MariaDB 11.4).

Anda HARUS mematuhi aturan arsitektur, koding, dan pengujian ini secara ketat untuk setiap tugas pembuatan atau modifikasi kode.

---

## 1. Arsitektur & Keterawatan (Pemisahan Tanggung Jawab / Separation of Concerns)
- **Controller Tipis (Thin Controllers)**: Controller hanya boleh menangani siklus hidup *request/response* HTTP, memanggil *Service*, dan mengembalikan respons Inertia atau *redirect*. DILARANG menempatkan logika bisnis yang kompleks atau *query builder* mentah secara langsung di dalam controller.
- **Layer Layanan (Service Layer)**: Ekstrak seluruh logika bisnis inti dan mutasi database ke dalam *class Service* khusus (contoh: `TeamService`, `DocumentService`).
- **Perlindungan Model Eksplisit**: Selalu definisikan `$fillable` secara eksplisit pada model Eloquent untuk mencegah kerentanan *mass-assignment*.

---

## 2. Konvensi Database & Migrasi
- **Penamaan Tabel Berbasis Domain**: Hindari nama tabel yang generik. Gunakan prefiks tabel *snake_case* yang modular berdasarkan domainnya, contoh :
  <!-- - Modul Workspace : `wrk_teams`, `wrk_users`  setiap pembuatan table baru diawali dengan `wrk_` -->
  - Modul Dokumen : `doc_files`, `doc_directories`, `doc_revisions` --> setiap pembuatan table baru diawali dengan `doc_`
  - Modul Master : `mst_user`, `mst_team` --> setiap pembuatan table baru diawali dengan `mst_`
- **Batasan Foreign Key**: Saat mereferensikan tabel lain dalam migrasi, selalu gunakan sintaks modern Laravel:
  ```php
  $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');

## 3. Adaptasi FrontEnd Vue
-- Agar mempermudah maintanance kedepannya , terapkan code yang sederhana saja dan mudah di baca dan dipahami
-- untuk setiap icon/gambar atau apapun , jika butuh data external, konfirmasi aja sehingga meminimalisir penggunaan svg 
-- Jangan pernah mengekspos data sensitif pengguna (misalnya: passwords, remember_tokens) atau yang lain ke komponen frontend

## 3. Adaptasi Backend Laravel
-- Agar mempermudah maintanance kedepannya , setiap logic yang mungkin akan di pakai terus menerus, baiknya dibuatkan helper global saja.
