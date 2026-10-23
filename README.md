# Sistem Manajemen Kamar Kost (UTS Pemrograman Lanjut)

[![CI/CD Pipeline](https://github.com/Yoloez/uts-pl-24-538946-SV-24555/actions/workflows/ci.yml/badge.svg)](https://github.com/Yoloez/uts-pl-24-538946-SV-24555/actions)
![Docker Compose](https://img.shields.io/badge/Orchestration-Docker%20Compose-blue?logo=docker)
![Laravel](https://img.shields.io/badge/Backend-Laravel%2012-FF2D20?logo=laravel)
![FrankenPHP](https://img.shields.io/badge/Server-FrankenPHP%20🐘-00ADD8)
![Vue 3](https://img.shields.io/badge/Frontend-Vue%203%20+%20Vite-4FC08D?logo=vuedotjs)
![MySQL](https://img.shields.io/badge/Database-MySQL%208.0-4479A1?logo=mysql)

Proyek ini merupakan implementasi **Sistem Manajemen dan Pemesanan Kamar Kost** berbasis *multi-container architecture* yang dibangun untuk memenuhi Ujian Tengah Semester (UTS) mata kuliah **Pemrograman Lanjut / Evolusi dan Konstruksi Perangkat Lunak**.

Sistem ini memfasilitasi pengelolaan ketersediaan kamar kost bagi Administrator serta proses penjelajahan dan pemesanan kamar bagi Penghuni Kost.

---

## 🌟 Fitur Utama

1. **Autentikasi & Otorisasi Berbasis Peran (RBAC)**:
   - **Administrator**: Hak akses penuh untuk manajemen kamar (CRUD), monitoring status kamar, dan verifikasi pemesanan.
   - **Penghuni (Tenant)**: Melihat daftar kamar yang tersedia, melihat rincian fasilitas, serta mengajukan pemesanan kamar (*booking*).
2. **Manajemen Entitas Kamar Kost (CRUD)**:
   - Pengelolaan data kamar meliputi nomor kamar, tipe kamar, harga sewa bulanan, deskripsi, fasilitas, serta status (*Tersedia*, *Terisi*, *Perbaikan*).
3. **Pemesanan Kamar Kost (Booking)**:
   - Pencatatan transaksi pemesanan kamar yang terintegrasi langsung antara data akun pengguna dan kamar yang dipilih.
4. **Otomatisasi Lingkungan (*Self-Healing & Auto-Setup*)**:
   - Skrip entrypoint backend secara otomatis menunggu kesiapan database, mengenerate `APP_KEY`, mengeksekusi migrasi tabel, serta mengisi data seeder awal.

---

## 🏗️ Arsitektur Sistem & Teknologi

Sistem dirancang menggunakan arsitektur *microservice-ready* yang diorkestrasi menggunakan **Docker Compose**:

```text
[ Browser / Client: http://localhost:3000 ]
                     │
                     ▼
       ┌───────────────────────────┐
       │   kost_frontend (Port 80) │  (Vue 3 + Vite + Nginx Reverse Proxy)
       │      Static Assets Web    │
       └─────────────┬─────────────┘
                     │ Proxy /api/*
                     ▼
       ┌───────────────────────────┐
       │   kost_backend (Port 8000)│  (Laravel 12 RESTful API + FrankenPHP)
       │    Autentikasi & Logika   │
       └─────────────┬─────────────┘
                     │ internal port 3306 (kost_network)
                     ▼
       ┌───────────────────────────┐
       │  kost_database (Port 3307)│  (MySQL 8.0 / MariaDB Engine)
       │    Volume: mysql_data     │
       └───────────────────────────┘
```

| Layanan | Komponen / Framework | Peran | Port Host |
| :--- | :--- | :--- | :--- |
| **`frontend`** | Vue 3, Vite, TypeScript, Nginx | Antarmuka pengguna responsif & Reverse Proxy endpoint `/api` | `3000` |
| **`backend`** | Laravel 12, PHP 8.2, FrankenPHP | REST API, Sanctum Auth, Business Logic | `8000` |
| **`database`**| MySQL 8.0 / MariaDB 10.11 | Penyimpanan basis data relasional persisten | `3307` |

---

## 🚀 Panduan Menjalankan Sistem (Docker Compose)

### 1. Prasyarat
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) telah terpasang dan aktif.
- Git.

### 2. Kloning Repositori & Setup Konfigurasi
```bash
git clone https://github.com/Yoloez/uts-pl-24-538946-SV-24555.git
cd uts-pl-24-538946-SV-24555
```

Salin template variabel lingkungan:
```bash
cp .env.example .env
```

### 3. Menjalankan Seluruh Kontainer
Cukup jalankan satu perintah berikut di root repositori:
```bash
docker compose up -d --build
```

Docker Compose akan secara otomatis:
1. Membangun image frontend (Vue 3 di-compile ke Nginx).
2. Membangun image backend (FrankenPHP + ekstensi PHP lengkap).
3. Mengunduh dan menginisialisasi basis data MySQL/MariaDB.
4. Menjalankan migrasi database dan pengisian data dummy awal (*seeder*).

Periksa status seluruh kontainer:
```bash
docker compose ps
```

---

## 🌐 Akses Aplikasi & Akun Uji Coba

Setelah semua kontainer berstatus `Up (healthy)`, aplikasi dapat diakses melalui:

- **Frontend Web UI**: [http://localhost:3000](http://localhost:3000)
- **Backend API Direct**: [http://localhost:8000/api](http://localhost:8000/api)
- **Database MySQL**: `localhost:3307` (Database: `uts-kepl`, User: `root`, Password: *(kosong)*)

### Kredensial Akun Bawaan (Hasil Seeder):

| Role | Email | Password | Hak Akses |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@kost.com` | `password123` | Tambah/Edit/Hapus Kamar, Ubah Status Kamar |
| **Penghuni** | `penghuni@kost.com` | `password123` | Eksplorasi Kamar & Mengajukan Booking |

---

## 🛡️ Otomasi CI/CD (GitHub Actions)

Repositori ini telah dilengkapi dengan pipeline *Continuous Integration* & *Continuous Deployment* pada `.github/workflows/ci.yml`:
- **CI**: Menjalankan linting, static analysis, unit/feature test backend (PHPUnit) dan build checking frontend secara otomatis pada setiap `push` dan `pull_request`.
- **CD**: Membangun image kontainer Docker dan mempublikasikannya ke Docker Hub secara terotomasi.

---

## 🛑 Menghentikan Sistem

Untuk menghentikan kontainer tanpa menghapus data basis data:
```bash
docker compose down
```
> Data kamar kost dan pengguna tetap aman tersimpan berkat penggunaan Docker *named volume* (`mysql_data`).
