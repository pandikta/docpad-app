# DocPad — Team Document Workspace

**DocPad** adalah aplikasi *workspace* manajemen dokumen teks internal berbasis tim untuk mengunggah, mengedit, mencari, dan melacak riwayat perubahan (*activity logs*) dokumen secara kolaboratif.

---

## Fitur Utama

- **Workspace Berbasis Tim & Role Superadmin**
  - Registrasi anggota menggunakan **Team Code** untuk bergabung ke *workspace* tim.
  - Isolasi dokumen antar-tim, dilengkapi akses penuh lintas tim untuk **Superadmin**.
- **Manajemen Dokumen (`.txt` & `.md`)**
  - **Bulk Upload**: Unggah satu atau banyak dokumen sekaligus.
  - **Rich Text / Document Editor**: Buka dan edit isi dokumen langsung dari *browser* melalui *dialog editor*.
  - **Rename dengan Validasi Ekstensi**: Ubah nama file dengan validasi *real-time* (wajib memiliki ekstensi yang diizinkan: `.txt` atau `.md`).
  - **Download & Delete**: Unduh dokumen asli atau hapus dokumen dengan dialog konfirmasi.
- **Document Activity Logs (Audit Trail)**
  - Mencatat secara otomatis siapa dan kapan sebuah dokumen diunggah, diubah isinya, atau diganti namanya dalam tampilan *timeline popup*.
- **Global Instant Search (`Ctrl + K` / `Cmd + K`)**
  - Pencarian *real-time* berdasarkan nama file, isi dokumen, maupun nama pembuat dokumen.
- **Modern UI dengan Light & Dark Mode**
  - Dibangun dengan **PrimeVue 4 (Aura Theme)** & **Tailwind CSS**, lengkap dengan *persistent Dark/Light mode*.

---

## Tech Stack

| Layer | Teknologi |
| :--- | :--- |
| **Backend** | PHP 8.3, Laravel 12 |
| **Frontend** | Vue 3 (Composition API), Inertia.js |
| **UI Component & Styling** | PrimeVue 4 (`@primeuix/themes/aura`), PrimeIcons, Tailwind CSS, Quill Editor |
| **Database** | MariaDB 11.4 |
| **Container & Web Server** | Docker, Docker Compose, Nginx (Alpine) |

---

## Struktur Proyek

```text
docpad-app/
├── docker/                     # Konfigurasi Dockerfile (PHP 8.3 + Node 22) & Nginx
├── docker-compose.yml          # Orkestrasi container (docpad_app, docpad_nginx, docpad_db)
└── src/                        # Source code utama Laravel 12 + Vue 3
    ├── app/
    │   ├── Helpers/            # Global helpers (helpers.php, AuthHelper.php)
    │   ├── Http/Controllers/   # Thin Controllers (Dashboard, Document, Profile, Auth)
    │   ├── Models/             # Eloquent Models (mst_teams, mst_users, doc_files, doc_logs)
    │   └── Services/           # Business logic layer (DocumentService, TeamService, UserService)
    ├── database/
    │   ├── migrations/         # Skema tabel dengan prefix domain (mst_*, doc_*)
    │   └── seeders/            # TeamSeeder & SuperAdminSeeder
    └── resources/js/
        ├── Components/         # Komponen modular (App, Dashboard, Documents)
        ├── Layouts/            # AppLayout (Sidebar + Topbar + Toast)
        ├── Pages/              # Halaman Inertia (Dashboard, Profile, Auth)
        ├── composables/        # Reusable state & actions (useDocumentActions, useDocumentSearch, useDarkMode)
        └── utils/              # Formatter tanggal, ukuran file, dan inisial
```

---

## Cara Menjalankan Proyek (Docker)

### 1. Jalankan Container Docker
Pastikan **Docker Desktop** sudah berjalan, lalu jalankan perintah berikut dari *root directory* proyek:

```bash
docker compose up -d --build
```

Container yang akan berjalan:
- `docpad_nginx` — Web Server di `http://localhost:8000`
- `docpad_app` — PHP 8.3-FPM + Composer + Node.js 22 (Vite port `5173`)
- `docpad_db` — MariaDB 11.4 (port `3306`)

### 2. Setup Environment & Dependencies Backend
Masuk ke proses instalasi di dalam container `docpad_app`:

```bash
# Install dependency PHP
docker exec -it docpad_app composer install

# Salin file .env (jika belum ada) dan sesuaikan konfigurasi database
docker exec -it docpad_app cp .env.example .env
docker exec -it docpad_app php artisan key:generate
```

Pastikan konfigurasi database pada `src/.env` sesuai dengan `docker-compose.yml`:

### 3. Migrasi Database & Seeder
Jalankan migrasi beserta *seeder* awal (Team & Superadmin):

```bash
docker exec -it docpad_app php artisan migrate --seed
```

### 4. Install Dependencies Frontend & Jalankan Vite
```bash
docker exec -it docpad_app npm install
docker exec -it docpad_app npm run dev -- --host
```
*(Atau gunakan `docker exec -it docpad_app npm run build` untuk mem-build aset produksi).*

### 5. Akses Aplikasi
Buka di *browser*:
- **URL Aplikasi**: [http://localhost:8000](http://localhost:8000)