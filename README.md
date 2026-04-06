# E-Disiplin - Sistem Poin Pelanggaran Siswa

Sistem manajemen disiplin siswa berbasis web untuk mencatat pelanggaran, mengelola surat peringatan berjenjang (SP1/SP2/SP3/LE), dan menghasilkan dokumen surat secara otomatis.

## Fitur

- **Autentikasi & Otorisasi** - Session-based auth dengan CSRF protection, rate limiting, dan role-based access control
- **Role Management** - Admin, Guru, BK, Orang Tua, Siswa (5 role)
- **Data Siswa** - CRUD data siswa lengkap dengan info orang tua, CSV import
- **Pencatatan Pelanggaran** - CRUD pelanggaran dengan jenis, poin, bulk delete, dan keterangan
- **Jenis Pelanggaran** - CRUD master jenis pelanggaran beserta bobot poin
- **Surat Pelanggaran Berjenjang** - SP1, SP2, SP3, LE dengan auto-generate nomor surat
- **Surat Pernyataan Siswa** - Surat pernyataan dengan tanda tangan guru BK, wali kelas, dan wakasek
- **Surat Pindah** - Surat keterangan pindah sekolah
- **Daftar Pelanggaran** - Laporan pelanggaran dengan filter kelas dan rentang tanggal
- **Dashboard** - Statistik, grafik tren 7 hari, dan ringkasan pelanggaran terbaru
- **Dashboard Orang Tua** - Pantau pelanggaran dan surat anak (ownership-verified)
- **Dashboard Siswa** - Lihat pelanggaran dan surat pribadi, progress bar poin
- **User Management** - CRUD user, reset password oleh admin
- **Pengaturan Akun** - Ganti nama, email, dan password
- **Pengaturan Sistem** - Konfigurasi nama sekolah, alamat, email, pejabat penandatangan
- **Audit Log** - Pencatatan otomatis setiap aktivitas CRUD (Create, Read, Update, Delete)
- **Print** - Cetak semua jenis surat dalam format print-friendly
- **Dokumentasi** - Panduan penggunaan terintegrasi per role

## Struktur Project

```
E-Disiplin/
├── app/
│   ├── config/
│   │   ├── Database.php         # Koneksi database MySQLi
│   │   └── bootstrap.php        # Inisialisasi app, session, security headers
│   ├── controllers/
│   │   └── AuthController.php   # Login/logout logic
│   ├── helpers/
│   │   ├── SecurityHelper.php   # CSRF, rate limiting, sanitasi input, audit log
│   │   └── SettingsHelper.php   # Manajemen app_settings (key-value)
│   ├── middleware/
│   │   ├── AccessControl.php    # Verifikasi kepemilikan data & role access
│   │   ├── AuthMiddleware.php   # Inisialisasi session
│   │   └── RoleMiddleware.php   # Enforcement role requirement
│   ├── models/
│   │   └── User.php             # Model user (find, verify, create)
│   ├── templates/
│   │   └── SuratTemplate.php    # Template surat (SP, Pernyataan, Pindah, Daftar)
│   └── views/
│       ├── layouts/
│       │   ├── header.php       # Header & navigasi
│       │   └── footer.php       # Footer & dock sidebar
│       └── login.php            # Halaman login
├── database/
│   └── seed_dummy.php           # Seeder data dummy untuk testing
├── public/                      # Web-accessible root
│   ├── index.php                # Login page / front controller
│   ├── dashboard.php            # Dashboard admin/staff (statistik & chart)
│   ├── siswa_dashboard.php      # Dashboard siswa (poin, riwayat, progress bar)
│   ├── ortu_dashboard.php       # Dashboard orang tua (data anak)
│   ├── data_siswa.php           # CRUD data siswa + CSV import
│   ├── pelanggaran.php          # CRUD pelanggaran (search, pagination, bulk delete)
│   ├── jenis_pelanggaran.php    # CRUD jenis pelanggaran & bobot poin
│   ├── surat_pelanggaran.php    # Kelola surat SP, pernyataan, pindah (3 tab)
│   ├── surat_print.php          # Print surat pelanggaran/pernyataan
│   ├── surat_pernyataan_print.php  # Print surat pernyataan siswa
│   ├── surat_pindah_print.php   # Print surat pindah by ID
│   ├── daftar_pelanggaran_print.php # Print laporan pelanggaran (filter)
│   ├── cek_surat.php            # Cek surat by NIS (ortu/siswa)
│   ├── user_management.php      # CRUD user + reset password (admin only)
│   ├── reset_password.php       # Pengaturan akun (nama, email, password)
│   ├── pengaturan_sistem.php    # Konfigurasi sekolah & pejabat (admin only)
│   ├── dokumentasi.php          # Panduan penggunaan per role
│   ├── endpoint/
│   │   └── auth/
│   │       ├── login.php        # JSON login endpoint (CSRF, rate-limited)
│   │       └── logout.php       # JSON logout endpoint
│   ├── css/                     # Stylesheets
│   ├── js/                      # JavaScript
│   └── images/                  # Gambar (kop_surat.png, dll)
├── .htaccess                    # Redirect ke public/
└── README.md
```

## Database Schema (9 Tabel)

Database: `e_disiplin`

| Tabel | Fungsi | Kolom Utama |
|-------|--------|-------------|
| **users** | Kredensial akun | id, username, password, email, name, role, is_active |
| **siswa** | Data identitas siswa & orang tua | id_siswa, nama, nis, kelas, alamat, nama_orang_tua, kontak_orang_tua, level_sp |
| **jenis_pelanggaran** | Master kategori & bobot poin | id_jenis, nama_jenis, poin, deskripsi |
| **pelanggaran** | Catatan historis pelanggaran | id_pelanggaran, id_siswa, id_jenis, tanggal, keterangan |
| **surat_orang_tua** | Surat peringatan berjenjang | id_surat_orang_tua, id_pelanggaran, level_sp, nomor_surat, status_kirim |
| **surat_perjanjian** | Surat pernyataan komitmen siswa | id_perjanjian, id_siswa, isi_perjanjian, nomor_surat, nama_guru_bk |
| **surat_pindah** | Administrasi pindah sekolah | id_surat_pindah, id_siswa, alasan_pindah, sekolah_tujuan |
| **app_settings** | Konfigurasi global sistem | setting_key, setting_value, updated_at |
| **audit_log** | Rekam jejak aktivitas pengguna | id, user_id, username, action, table_name, record_id, ip_address |

## Role & Hak Akses (5 Role)

| Role | Akses |
|------|-------|
| **admin** | Semua fitur (user, siswa, pelanggaran, surat, jenis, pengaturan sistem) |
| **guru** | Input pelanggaran, buat surat, lihat data siswa |
| **bk** | Sama dengan guru (Bimbingan Konseling) |
| **orangtua** | Dashboard orang tua (data anak saja, ownership-verified) |
| **siswa** | Dashboard siswa (data sendiri saja) |

## Sistem Surat Pelanggaran Berjenjang

| Level | Nama | Deskripsi |
|-------|------|-----------|
| SP1 | Surat Pelanggaran Pertama | Pelanggaran Ringan - Notifikasi & pembinaan |
| SP2 | Surat Pelanggaran Kedua | Pelanggaran Sedang - Peringatan lebih tegas |
| SP3 | Surat Pelanggaran Ketiga | Pelanggaran Berat - Peringatan akhir |
| LE | Level Ekstensif | Tindakan Final - Rapat keputusan |

Flow: Pelanggaran dicatat -> Guru/BK buat surat (pilih level) -> Auto-generate nomor surat -> Update level SP siswa -> Cetak surat -> Orang tua terima

## Pengaturan Sistem (App Settings)

| Key | Fungsi |
|-----|--------|
| `nama_sekolah` | Nama resmi sekolah |
| `alamat_sekolah` | Alamat lengkap sekolah |
| `email_sekolah` | Email resmi sekolah |
| `nama_kepala_sekolah` | Nama kepala sekolah (untuk tanda tangan surat) |
| `nama_guru_bk` | Nama guru BK default |
| `nama_wakasek` | Nama wakil kepala sekolah default |

## Test Accounts

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Administrator |
| guru1 | admin123 | Guru/BK |
| guru2 | admin123 | Guru/BK |
| orangtua1 | admin123 | Orang Tua |
| siswa1 | admin123 | Siswa |

> Ubah password di production!

## Setup & Run

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Laragon / XAMPP / WAMP

### Steps

1. **Database Setup**
   ```bash
   mysql -u root < e-disiplin.sql
   mysql -u root < create_users_table.sql
   ```

2. **Konfigurasi**
   - Edit `app/config/Database.php` atau buat file `.env` di root:
     ```
     DB_HOST=localhost
     DB_NAME=e_disiplin
     DB_USER=root
     DB_PASS=
     APP_ENV=development
     APP_DEBUG=true
     ```

3. **Seed Data Dummy** (opsional)
   ```bash
   php database/seed_dummy.php
   ```

4. **Jalankan**
   - Akses via browser: `http://localhost/E-Disiplin`

> **Note:** Document root sebaiknya mengarah ke `public/`. Jika tidak bisa diubah, `.htaccess` akan forward request ke `public/` secara otomatis.

## Keamanan

- CSRF Protection pada semua form POST
- Prepared Statements (SQL injection prevention)
- Password hashing dengan bcrypt + complexity enforcement (min 6, huruf & angka)
- Rate limiting pada login (5 percobaan / 15 menit)
- Security headers (X-Frame-Options, CSP, X-XSS-Protection, dll)
- Input sanitization (htmlspecialchars, trim)
- Session security (HTTP-only cookies, SameSite strict)
- Ownership verification / IDOR protection (surat & data siswa)
- Audit logging CRUD ke tabel audit_log (user, action, tabel, IP, timestamp)
- Cascade delete (hapus siswa otomatis hapus data terkait dalam transaction)

## Tech Stack

- **Backend**: PHP 7.4+ (Native OOP, tanpa framework)
- **Database**: MySQL 5.7+ (MySQLi, Prepared Statements)
- **Frontend**: HTML5, Tailwind CSS (CDN), Vanilla JavaScript
- **Chart**: Chart.js 4.4.1 (CDN)
- **Font**: Google Fonts - Inter
- **Auth**: Session-based, bcrypt password hashing

---

**Version**: 1.2.0
**Last Updated**: April 6, 2026
